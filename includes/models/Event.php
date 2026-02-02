<?php
/**
 * Event Model
 * Manages events, helper slots, signups, and locking mechanism
 */

class Event {
    
    // Lock timeout in seconds (15 minutes)
    const LOCK_TIMEOUT = 900;
    
    /**
     * Create new event
     */
    public static function create($data, $userId) {
        $db = Database::getContentDB();
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Insert event
            $stmt = $db->prepare("
                INSERT INTO events (title, description, location, start_time, end_time, 
                                  contact_person, status, is_external, external_link, needs_helpers)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['location'] ?? null,
                $data['start_time'],
                $data['end_time'],
                $data['contact_person'] ?? null,
                $data['status'] ?? 'planned',
                $data['is_external'] ?? false,
                $data['external_link'] ?? null,
                $data['needs_helpers'] ?? false
            ]);
            
            $eventId = $db->lastInsertId();
            
            // Insert allowed roles if provided
            if (!empty($data['allowed_roles']) && is_array($data['allowed_roles'])) {
                self::setEventRoles($eventId, $data['allowed_roles']);
            }
            
            // Process helper types and slots if needs_helpers is enabled
            if (!empty($data['needs_helpers']) && !empty($data['helper_types'])) {
                self::processHelperTypesAndSlots(
                    $eventId, 
                    $data['helper_types'], 
                    $data['start_time'], 
                    $data['end_time'],
                    $userId
                );
            }
            
            // Log creation
            self::logHistory($eventId, $userId, 'create', [
                'action' => 'Event created',
                'title' => $data['title']
            ]);
            
            $db->commit();
            return $eventId;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Failed to create event: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get event by ID
     */
    public static function getById($id, $includeHelperSlots = true) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            return null;
        }
        
        // Get allowed roles
        $event['allowed_roles'] = self::getEventRoles($id);
        
        // Get helper types and slots if needed and event needs helpers
        if ($includeHelperSlots && $event['needs_helpers']) {
            $event['helper_types'] = self::getHelperTypes($id);
        }
        
        return $event;
    }
    
    /**
     * Update event
     */
    public static function update($id, $data, $userId) {
        $db = Database::getContentDB();
        
        // Check if event is locked by another user
        $lockInfo = self::checkLock($id, $userId);
        if ($lockInfo['is_locked'] && $lockInfo['locked_by'] !== $userId) {
            throw new Exception("Event is locked by another user");
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            $fields = [];
            $values = [];
            
            // Build update query dynamically
            foreach ($data as $key => $value) {
                if ($key !== 'id' && $key !== 'allowed_roles' && $key !== 'helper_types') {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (!empty($fields)) {
                $values[] = $id;
                $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            }
            
            // Update allowed roles if provided
            if (isset($data['allowed_roles']) && is_array($data['allowed_roles'])) {
                self::setEventRoles($id, $data['allowed_roles']);
            }
            
            // Process helper types and slots if needs_helpers is enabled
            if (!empty($data['needs_helpers']) && isset($data['helper_types'])) {
                // Delete old helper types and slots (Clean Slate approach)
                $stmt = $db->prepare("DELETE FROM event_helper_types WHERE event_id = ?");
                $stmt->execute([$id]);
                
                // Process new helper types and slots
                self::processHelperTypesAndSlots(
                    $id, 
                    $data['helper_types'], 
                    $data['start_time'] ?? null, 
                    $data['end_time'] ?? null,
                    $userId
                );
            }
            
            // Log update
            self::logHistory($id, $userId, 'update', [
                'action' => 'Event updated',
                'changes' => $data
            ]);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Failed to update event: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete event
     */
    public static function delete($id, $userId) {
        $db = Database::getContentDB();
        
        // Log deletion before deleting
        self::logHistory($id, $userId, 'delete', [
            'action' => 'Event deleted'
        ]);
        
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get events with filters
     * Respects user role for visibility
     * Alumni users NEVER see helper slots or helper-related information
     */
    public static function getEvents($filters = [], $userRole = null) {
        $db = Database::getContentDB();
        
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];
        
        // Filter by status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = str_repeat('?,', count($filters['status']) - 1) . '?';
                $sql .= " AND status IN ($placeholders)";
                $params = array_merge($params, $filters['status']);
            } else {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
        }
        
        // Filter by needs_helpers
        if (isset($filters['needs_helpers'])) {
            $sql .= " AND needs_helpers = ?";
            $params[] = $filters['needs_helpers'] ? 1 : 0;
        }
        
        // Filter by date range
        if (!empty($filters['start_date'])) {
            $sql .= " AND start_time >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND end_time <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Filter by external/internal
        if (isset($filters['is_external'])) {
            $sql .= " AND is_external = ?";
            $params[] = $filters['is_external'] ? 1 : 0;
        }
        
        // Order by start time
        $sql .= " ORDER BY start_time DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        // Filter events by role visibility and remove helper information for alumni
        $filteredEvents = [];
        foreach ($events as $event) {
            // Get allowed roles for this event
            $allowedRoles = self::getEventRoles($event['id']);
            
            // Check if user has access
            if ($userRole && !empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
                continue; // Skip this event
            }
            
            // IMPORTANT: Alumni must NEVER see helper slots or needs_helpers
            if ($userRole === 'alumni') {
                $event['needs_helpers'] = false;
                $event['helper_types'] = [];
            } else if ($event['needs_helpers']) {
                // For non-alumni users, include helper information if requested
                if (!empty($filters['include_helpers'])) {
                    $event['helper_types'] = self::getHelperTypes($event['id']);
                }
            }
            
            $event['allowed_roles'] = $allowedRoles;
            $filteredEvents[] = $event;
        }
        
        return $filteredEvents;
    }
    
    /**
     * Get event roles (allowed roles for participation)
     */
    private static function getEventRoles($eventId) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("SELECT role FROM event_roles WHERE event_id = ?");
        $stmt->execute([$eventId]);
        return array_column($stmt->fetchAll(), 'role');
    }
    
    /**
     * Set event roles (replace existing roles)
     */
    private static function setEventRoles($eventId, $roles) {
        $db = Database::getContentDB();
        
        // Delete existing roles
        $stmt = $db->prepare("DELETE FROM event_roles WHERE event_id = ?");
        $stmt->execute([$eventId]);
        
        // Insert new roles
        if (!empty($roles)) {
            $stmt = $db->prepare("INSERT INTO event_roles (event_id, role) VALUES (?, ?)");
            foreach ($roles as $role) {
                $stmt->execute([$eventId, $role]);
            }
        }
    }
    
    /**
     * Process helper types and slots for an event
     * This method handles creating helper types and their associated time slots
     * within the same transaction context as the event create/update
     * 
     * @param int $eventId Event ID
     * @param array $helperTypes Array of helper types with their slots
     * @param string|null $eventStartTime Event start time for validation
     * @param string|null $eventEndTime Event end time for validation
     * @param int $userId User ID for logging
     */
    private static function processHelperTypesAndSlots($eventId, $helperTypes, $eventStartTime, $eventEndTime, $userId) {
        if (empty($helperTypes) || !is_array($helperTypes)) {
            return;
        }
        
        $db = Database::getContentDB();
        
        foreach ($helperTypes as $helperType) {
            // Skip if no title provided
            if (empty($helperType['title'])) {
                continue;
            }
            
            // Create helper type
            $helperTypeId = self::createHelperType(
                $eventId,
                $helperType['title'],
                $helperType['description'] ?? null,
                $userId
            );
            
            // Process slots for this helper type
            if (!empty($helperType['slots']) && is_array($helperType['slots'])) {
                foreach ($helperType['slots'] as $slot) {
                    // Skip if required fields are missing
                    if (empty($slot['start_time']) || empty($slot['end_time'])) {
                        continue;
                    }
                    
                    // Validate slot times if event times are provided
                    if ($eventStartTime && $eventEndTime) {
                        // Parse times first
                        $slotStart = strtotime($slot['start_time']);
                        $slotEnd = strtotime($slot['end_time']);
                        $eventStart = strtotime($eventStartTime);
                        $eventEnd = strtotime($eventEndTime);
                        
                        // Validate parsing was successful
                        if ($slotStart === false) {
                            throw new Exception('Ungültige Slot-Startzeit');
                        }
                        if ($slotEnd === false) {
                            throw new Exception('Ungültige Slot-Endzeit');
                        }
                        if ($eventStart === false) {
                            throw new Exception('Ungültige Event-Startzeit');
                        }
                        if ($eventEnd === false) {
                            throw new Exception('Ungültige Event-Endzeit');
                        }
                        
                        // Validate slot times are within event timeframe
                        if ($slotStart < $eventStart || $slotEnd > $eventEnd) {
                            throw new Exception('Zeitslots müssen innerhalb des Event-Zeitraums liegen');
                        }
                        
                        // Validate slot start is before end
                        if ($slotStart >= $slotEnd) {
                            throw new Exception('Slot-Startzeit muss vor der Endzeit liegen');
                        }
                    }
                    
                    // Create slot
                    self::createSlot(
                        $helperTypeId,
                        $slot['start_time'],
                        $slot['end_time'],
                        intval($slot['quantity'] ?? 1),
                        $userId,
                        $eventId
                    );
                }
            }
        }
    }
    
    /**
     * Create helper type for event
     */
    public static function createHelperType($eventId, $title, $description = null, $userId = null) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            INSERT INTO event_helper_types (event_id, title, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$eventId, $title, $description]);
        
        $helperTypeId = $db->lastInsertId();
        
        if ($userId) {
            self::logHistory($eventId, $userId, 'helper_type_created', [
                'helper_type_id' => $helperTypeId,
                'title' => $title
            ]);
        }
        
        return $helperTypeId;
    }
    
    /**
     * Get helper types for event
     */
    public static function getHelperTypes($eventId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("SELECT * FROM event_helper_types WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $helperTypes = $stmt->fetchAll();
        
        // Get slots for each helper type
        foreach ($helperTypes as &$helperType) {
            $helperType['slots'] = self::getSlots($helperType['id']);
        }
        
        return $helperTypes;
    }
    
    /**
     * Create slot for helper type
     */
    public static function createSlot($helperTypeId, $startTime, $endTime, $quantityNeeded, $userId = null, $eventId = null) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            INSERT INTO event_slots (helper_type_id, start_time, end_time, quantity_needed)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$helperTypeId, $startTime, $endTime, $quantityNeeded]);
        
        $slotId = $db->lastInsertId();
        
        if ($userId && $eventId) {
            self::logHistory($eventId, $userId, 'slot_created', [
                'slot_id' => $slotId,
                'helper_type_id' => $helperTypeId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'quantity_needed' => $quantityNeeded
            ]);
        }
        
        return $slotId;
    }
    
    /**
     * Get slots for helper type
     */
    public static function getSlots($helperTypeId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            SELECT s.*, 
                   (SELECT COUNT(*) FROM event_signups WHERE slot_id = s.id AND status = 'confirmed') as signups_count
            FROM event_slots s
            WHERE s.helper_type_id = ?
            ORDER BY s.start_time
        ");
        $stmt->execute([$helperTypeId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Sign up for event or slot
     * IMPORTANT: Alumni users are NOT allowed to sign up for helper slots
     */
    public static function signup($eventId, $userId, $slotId = null, $userRole = null) {
        $db = Database::getContentDB();
        
        // Get event to check if it needs helpers
        $event = self::getById($eventId, false);
        if (!$event) {
            throw new Exception("Event not found");
        }
        
        // CRITICAL: Alumni users must NOT be able to sign up for helper slots
        if ($slotId !== null && $userRole === 'alumni') {
            throw new Exception("Alumni users are not allowed to sign up for helper slots");
        }
        
        // If slot_id is provided, verify the event needs helpers
        if ($slotId !== null && !$event['needs_helpers']) {
            throw new Exception("This event does not have helper slots");
        }
        
        // Check if user has permission to participate
        $allowedRoles = self::getEventRoles($eventId);
        if (!empty($allowedRoles) && $userRole && !in_array($userRole, $allowedRoles)) {
            throw new Exception("You do not have permission to participate in this event");
        }
        
        // Check if slot is full (if signing up for a slot)
        if ($slotId !== null) {
            $stmt = $db->prepare("
                SELECT s.quantity_needed,
                       (SELECT COUNT(*) FROM event_signups WHERE slot_id = s.id AND status = 'confirmed') as signups_count
                FROM event_slots s
                WHERE s.id = ?
            ");
            $stmt->execute([$slotId]);
            $slot = $stmt->fetch();
            
            if ($slot && $slot['signups_count'] >= $slot['quantity_needed']) {
                // Add to waitlist instead
                $status = 'waitlist';
            } else {
                $status = 'confirmed';
            }
        } else {
            $status = 'confirmed';
        }
        
        // Insert signup
        $stmt = $db->prepare("
            INSERT INTO event_signups (event_id, user_id, slot_id, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$eventId, $userId, $slotId, $status]);
        
        $signupId = $db->lastInsertId();
        
        // Log signup
        self::logHistory($eventId, $userId, 'signup', [
            'signup_id' => $signupId,
            'slot_id' => $slotId,
            'status' => $status
        ]);
        
        return ['id' => $signupId, 'status' => $status];
    }
    
    /**
     * Cancel signup
     */
    public static function cancelSignup($signupId, $userId) {
        $db = Database::getContentDB();
        
        // Get signup info
        $stmt = $db->prepare("SELECT * FROM event_signups WHERE id = ? AND user_id = ?");
        $stmt->execute([$signupId, $userId]);
        $signup = $stmt->fetch();
        
        if (!$signup) {
            throw new Exception("Signup not found");
        }
        
        // Update status to cancelled
        $stmt = $db->prepare("UPDATE event_signups SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$signupId]);
        
        // Log cancellation
        self::logHistory($signup['event_id'], $userId, 'signup_cancelled', [
            'signup_id' => $signupId,
            'slot_id' => $signup['slot_id']
        ]);
        
        // If this was a confirmed slot signup, check if someone on waitlist can be promoted
        $promotedUserId = null;
        if ($signup['slot_id'] && $signup['status'] === 'confirmed') {
            $promotedUserId = self::promoteWaitlistUser($signup['event_id'], $signup['slot_id']);
        }
        
        return [
            'success' => true,
            'promoted_user_id' => $promotedUserId,
            'event_id' => $signup['event_id'],
            'slot_id' => $signup['slot_id']
        ];
    }
    
    /**
     * Promote a waitlisted user to confirmed for a slot
     * Called when a confirmed user cancels
     * 
     * @param int $eventId Event ID
     * @param int $slotId Slot ID
     * @return int|null User ID that was promoted, or null if no one was promoted
     */
    private static function promoteWaitlistUser($eventId, $slotId) {
        $db = Database::getContentDB();
        
        // Get the first waitlisted user for this slot (oldest signup first)
        $stmt = $db->prepare("
            SELECT * FROM event_signups 
            WHERE event_id = ? AND slot_id = ? AND status = 'waitlist'
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$eventId, $slotId]);
        $waitlistSignup = $stmt->fetch();
        
        if (!$waitlistSignup) {
            return null; // No one on waitlist
        }
        
        // Promote to confirmed
        $stmt = $db->prepare("UPDATE event_signups SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$waitlistSignup['id']]);
        
        // Log promotion
        self::logHistory($eventId, $waitlistSignup['user_id'], 'waitlist_promoted', [
            'signup_id' => $waitlistSignup['id'],
            'slot_id' => $slotId
        ]);
        
        return $waitlistSignup['user_id'];
    }
    
    /**
     * Get signups for event
     */
    public static function getSignups($eventId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            SELECT s.*, es.title as slot_title
            FROM event_signups s
            LEFT JOIN event_slots es_slots ON s.slot_id = es_slots.id
            LEFT JOIN event_helper_types es ON es_slots.helper_type_id = es.id
            WHERE s.event_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user signups
     */
    public static function getUserSignups($userId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            SELECT s.*, e.title as event_title, e.start_time, e.end_time, e.location
            FROM event_signups s
            JOIN events e ON s.event_id = e.id
            WHERE s.user_id = ? AND s.status != 'cancelled'
            ORDER BY e.start_time DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if event is locked
     * Returns: ['is_locked' => bool, 'locked_by' => user_id or null, 'locked_at' => timestamp or null]
     */
    public static function checkLock($eventId, $userId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("SELECT locked_by, locked_at FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            return ['is_locked' => false, 'locked_by' => null, 'locked_at' => null];
        }
        
        // No lock
        if (!$event['locked_by'] || !$event['locked_at']) {
            return ['is_locked' => false, 'locked_by' => null, 'locked_at' => null];
        }
        
        // Check if lock has expired (15 minutes)
        $lockTime = strtotime($event['locked_at']);
        $now = time();
        
        if (($now - $lockTime) > self::LOCK_TIMEOUT) {
            // Lock expired, release it
            self::releaseLock($eventId, $event['locked_by']);
            return ['is_locked' => false, 'locked_by' => null, 'locked_at' => null];
        }
        
        // Lock is active
        return [
            'is_locked' => true,
            'locked_by' => $event['locked_by'],
            'locked_at' => $event['locked_at']
        ];
    }
    
    /**
     * Acquire lock on event (max 15 minutes valid)
     */
    public static function acquireLock($eventId, $userId) {
        $db = Database::getContentDB();
        
        // Check current lock status
        $lockInfo = self::checkLock($eventId, $userId);
        
        // If locked by another user, cannot acquire
        if ($lockInfo['is_locked'] && $lockInfo['locked_by'] !== $userId) {
            return [
                'success' => false,
                'message' => 'Event is currently locked by another user',
                'locked_by' => $lockInfo['locked_by']
            ];
        }
        
        // Acquire or refresh lock
        $stmt = $db->prepare("
            UPDATE events 
            SET locked_by = ?, locked_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$userId, $eventId]);
        
        // Log lock acquisition
        self::logHistory($eventId, $userId, 'lock_acquired', [
            'action' => 'Lock acquired',
            'timeout' => self::LOCK_TIMEOUT . ' seconds'
        ]);
        
        return [
            'success' => true,
            'message' => 'Lock acquired successfully',
            'expires_in' => self::LOCK_TIMEOUT
        ];
    }
    
    /**
     * Release lock on event
     */
    public static function releaseLock($eventId, $userId) {
        $db = Database::getContentDB();
        
        // Only the user who locked it can release it (or if lock expired)
        $lockInfo = self::checkLock($eventId, $userId);
        
        if ($lockInfo['is_locked'] && $lockInfo['locked_by'] !== $userId) {
            return [
                'success' => false,
                'message' => 'Cannot release lock held by another user'
            ];
        }
        
        $stmt = $db->prepare("
            UPDATE events 
            SET locked_by = NULL, locked_at = NULL 
            WHERE id = ? AND (locked_by = ? OR locked_by IS NULL)
        ");
        $stmt->execute([$eventId, $userId]);
        
        // Log lock release
        self::logHistory($eventId, $userId, 'lock_released', [
            'action' => 'Lock released'
        ]);
        
        return [
            'success' => true,
            'message' => 'Lock released successfully'
        ];
    }
    
    /**
     * Log event history entry
     */
    private static function logHistory($eventId, $userId, $changeType, $changeDetails) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            INSERT INTO event_history (event_id, user_id, change_type, change_details)
            VALUES (?, ?, ?, ?)
        ");
        
        $detailsJson = json_encode($changeDetails);
        $stmt->execute([$eventId, $userId, $changeType, $detailsJson]);
    }
    
    /**
     * Get event history
     */
    public static function getHistory($eventId, $limit = 50) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            SELECT * FROM event_history
            WHERE event_id = ?
            ORDER BY timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$eventId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update event statuses based on current time (pseudo-cron)
     * This should be called on page loads to keep statuses current
     * 
     * Updates:
     * - "planned" events to "open" when registration should open
     * - Events to "past" when they have ended
     * 
     * @return array Summary of updates made
     */
    public static function updateEventStatuses() {
        $db = Database::getContentDB();
        $now = new DateTime();
        $updates = [
            'planned_to_open' => 0,
            'to_past' => 0
        ];
        
        try {
            // Update "planned" events to "open" when registration period starts
            // Assuming registration opens when current time is past the event start time minus some buffer
            // For simplicity, we'll open registration as soon as the event is not in the past
            // In a real system, you might have a separate registration_opens_at field
            
            $stmt = $db->prepare("
                UPDATE events 
                SET status = 'open' 
                WHERE status = 'planned' 
                AND start_time > NOW()
            ");
            $stmt->execute();
            $updates['planned_to_open'] = $stmt->rowCount();
            
            // Update events to "past" when end_time has passed
            $stmt = $db->prepare("
                UPDATE events 
                SET status = 'past' 
                WHERE status IN ('open', 'running', 'closed', 'planned')
                AND end_time < NOW()
            ");
            $stmt->execute();
            $updates['to_past'] = $stmt->rowCount();
            
            // Log if any updates were made
            if ($updates['planned_to_open'] > 0 || $updates['to_past'] > 0) {
                error_log("Event status update: " . 
                         $updates['planned_to_open'] . " events opened, " . 
                         $updates['to_past'] . " events moved to past");
            }
            
            return $updates;
        } catch (Exception $e) {
            error_log("Error updating event statuses: " . $e->getMessage());
            return $updates;
        }
    }
}
