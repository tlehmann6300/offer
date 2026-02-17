<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/models/User.php';
require_once __DIR__ . '/../../includes/models/Event.php';
require_once __DIR__ . '/../../includes/models/MailQueue.php';
require_once __DIR__ . '/../../src/CalendarService.php';

// Access control: exclusively for board_finance, board_internal, board_external
if (!Auth::check() || !Auth::isBoardMember()) {
    header('Location: ../auth/login.php');
    exit;
}

$queueMessage = '';
$queueError = '';
$queuedCount = 0;

// Handle email queue form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['queue_emails'])) {
    $userRole = $_SESSION['user_role'] ?? '';
    if (!in_array($userRole, ['board_finance', 'board_internal', 'board_external'])) {
        $queueError = 'Keine Berechtigung. Nur Vorstandsmitglieder können Massen-E-Mails versenden.';
    } else {
        $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $targetGroups = $_POST['target_groups'] ?? [];
        $templateFile = $_POST['template_file'] ?? '';

        if ($eventId <= 0) {
            $queueError = 'Bitte wählen Sie ein Event aus.';
        } elseif (empty($targetGroups)) {
            $queueError = 'Bitte wählen Sie mindestens eine Zielgruppe aus.';
        } elseif (empty($templateFile)) {
            $queueError = 'Bitte wählen Sie eine Vorlage aus.';
        } else {
            // Validate template file name (only allow alphanumeric, underscores, hyphens)
            $safeTemplateName = basename($templateFile);
            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_\-]*\.json$/', $safeTemplateName)) {
                $queueError = 'Ungültiger Vorlagenname.';
            } else {
                $templatePath = __DIR__ . '/../../assets/mail_vorlage/' . $safeTemplateName;
                if (!file_exists($templatePath)) {
                    $queueError = 'Die gewählte Vorlage existiert nicht.';
                } else {
                    try {
                        // Load event
                        $event = Event::getById($eventId);
                        if (!$event) {
                            throw new Exception('Event nicht gefunden.');
                        }

                        // Load template content
                        $jsonContent = file_get_contents($templatePath);
                        if ($jsonContent === false) {
                            throw new Exception('Vorlage konnte nicht geladen werden.');
                        }

                        // Determine sender name from session
                        $senderName = '';
                        if (!empty($_SESSION['first_name']) && !empty($_SESSION['last_name'])) {
                            $senderName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
                        } elseif (!empty($_SESSION['email'])) {
                            $senderName = formatEntraName(explode('@', $_SESSION['email'])[0]);
                        } else {
                            $senderName = 'Vorstand';
                        }

                        // Collect target roles based on selected groups
                        $roles = [];
                        if (in_array('alumni', $targetGroups)) {
                            $roles = array_merge($roles, ['alumni', 'alumni_auditor', 'alumni_board', 'honorary_member']);
                        }
                        if (in_array('members', $targetGroups)) {
                            $roles = array_merge($roles, ['head', 'member', 'candidate', 'board_finance', 'board_internal', 'board_external']);
                        }
                        $roles = array_unique($roles);

                        if (empty($roles)) {
                            throw new Exception('Keine gültigen Zielgruppen ausgewählt.');
                        }

                        // Fetch users by roles
                        $recipients = User::getUsersByRoles($roles);

                        if (empty($recipients)) {
                            throw new Exception('Keine Empfänger in den gewählten Zielgruppen gefunden.');
                        }

                        // Loop over all recipients
                        $queueFailCount = 0;
                        foreach ($recipients as $user) {
                            // Parse template for this user
                            $parsed = parseEmailTemplate($jsonContent, $user, $event, $senderName);
                            if (empty($parsed['subject']) || empty($parsed['body'])) {
                                error_log("parseEmailTemplate: Skipped user " . ($user['email'] ?? 'unknown') . " - empty subject or body");
                                continue;
                            }

                            // Generate ICS content
                            $icsContent = CalendarService::generateICS($event);

                            // Build recipient name
                            $recipientName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                            if (empty($recipientName)) {
                                $recipientName = $user['email'];
                            }

                            // Add to mail queue (do NOT send immediately)
                            $queued = MailQueue::addToQueue(
                                $eventId,
                                $user['email'],
                                $recipientName,
                                $parsed['subject'],
                                $parsed['body'],
                                $icsContent
                            );

                            if ($queued) {
                                $queuedCount++;
                            } else {
                                $queueFailCount++;
                            }
                        }

                        if ($queueFailCount > 0) {
                            $queueMessage = sprintf(
                                '%d E-Mails erfolgreich in die Warteschlange eingereiht, %d fehlgeschlagen.',
                                $queuedCount,
                                $queueFailCount
                            );
                        } else {
                            $queueMessage = sprintf(
                                '%d E-Mails erfolgreich in die Warteschlange eingereiht.',
                                $queuedCount
                            );
                        }

                    } catch (Exception $e) {
                        $queueError = 'Fehler: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch data for the dashboard
$stats = MailQueue::getStats();
$hourlyUsage = MailQueue::getHourlyUsage();
$hourlyLimit = 500;
$hourlyPercent = $hourlyLimit > 0 ? min(100, round(($hourlyUsage / $hourlyLimit) * 100)) : 0;

// Fetch future events (start_time > NOW())
$futureEvents = Event::getEvents(['start_date' => date('Y-m-d H:i:s')]);

// Scan template files from assets/mail_vorlage using scandir
$templateDir = __DIR__ . '/../../assets/mail_vorlage/';
$templateFiles = [];
if (is_dir($templateDir)) {
    $allFiles = scandir($templateDir);
    foreach ($allFiles as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $templateFiles[] = $file;
        }
    }
    sort($templateFiles);
}

$title = 'Massen-E-Mail Dashboard - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                <i class="fas fa-mail-bulk text-indigo-600 dark:text-indigo-400 mr-2"></i>
                Massen-E-Mail Dashboard
            </h1>
            <p class="text-gray-600 dark:text-gray-300">Einladungen generieren, versenden und den Versandstatus überwachen</p>
        </div>
        <a href="index.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Zurück zum Admin-Dashboard
        </a>
    </div>
</div>

<?php if ($queueMessage): ?>
<div class="mb-6 p-4 bg-green-100 dark:bg-green-900/50 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($queueMessage); ?>
</div>
<?php endif; ?>

<?php if ($queueError): ?>
<div class="mb-6 p-4 bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($queueError); ?>
</div>
<?php endif; ?>

<!-- Live Monitor: Statistics Cards + Progress Bar -->
<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
        <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400 mr-2"></i>
        Live-Monitor
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <!-- Pending (Yellow) -->
        <div class="card p-6 border-l-4 border-yellow-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">In Warteschlange</p>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo (int)($stats['pending'] ?? 0); ?></p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-full">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Sent Today (Green) -->
        <div class="card p-6 border-l-4 border-green-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Versendet (Heute)</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo (int)($stats['sent_today'] ?? 0); ?></p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-full">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Failed (Red) -->
        <div class="card p-6 border-l-4 border-red-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fehlgeschlagen</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400"><?php echo (int)($stats['failed'] ?? 0); ?></p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-full">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Hourly Limit Progress Bar -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fas fa-tachometer-alt mr-1"></i>
                Stündliches Limit
            </span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                <?php echo (int)$hourlyUsage; ?> / <?php echo (int)$hourlyLimit; ?>
            </span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
            <div class="h-4 rounded-full transition-all duration-300 <?php echo $hourlyPercent >= 90 ? 'bg-red-500' : ($hourlyPercent >= 60 ? 'bg-yellow-500' : 'bg-green-500'); ?>"
                 style="width: <?php echo $hourlyPercent; ?>%"></div>
        </div>
    </div>
</div>

<!-- Dashboard: Form -->
<div class="card p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
        <i class="fas fa-envelope-open-text text-indigo-600 dark:text-indigo-400 mr-2"></i>
        Einladungen generieren
    </h2>

    <form method="POST" id="queueForm" class="space-y-6">
        <!-- Event Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-calendar-alt mr-1"></i>
                Event-Auswahl
            </label>
            <select name="event_id" required
                class="w-full px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">-- Event auswählen --</option>
                <?php foreach ($futureEvents as $ev): ?>
                <option value="<?php echo (int)$ev['id']; ?>" <?php echo (isset($_POST['event_id']) && (int)$_POST['event_id'] === (int)$ev['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ev['title'] . ' (' . formatDate($ev['start_time'], 'd.m.Y H:i') . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Target Groups -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-users mr-1"></i>
                Zielgruppen
            </label>
            <div class="space-y-2">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="target_groups[]" value="alumni"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                        <?php echo (isset($_POST['target_groups']) && in_array('alumni', $_POST['target_groups'])) ? 'checked' : ''; ?>>
                    <span class="text-gray-700 dark:text-gray-300">Alumni &amp; Ehrenmitglieder</span>
                </label>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="target_groups[]" value="members"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                        <?php echo (isset($_POST['target_groups']) && in_array('members', $_POST['target_groups'])) ? 'checked' : ''; ?>>
                    <span class="text-gray-700 dark:text-gray-300">Aktive Mitglieder &amp; Vorstand</span>
                </label>
            </div>
        </div>

        <!-- Template Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-file-alt mr-1"></i>
                E-Mail-Vorlage (Template)
            </label>
            <select name="template_file" required
                class="w-full px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">-- Vorlage auswählen --</option>
                <?php foreach ($templateFiles as $tplFile):
                    $tplLabel = pathinfo($tplFile, PATHINFO_FILENAME);
                ?>
                <option value="<?php echo htmlspecialchars($tplFile); ?>" <?php echo (isset($_POST['template_file']) && $_POST['template_file'] === $tplFile) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($tplLabel); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Submit Button with Loading Indicator -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-info-circle mr-1 text-indigo-600 dark:text-indigo-400"></i>
                E-Mails werden in die Warteschlange eingereiht und im Hintergrund versendet.
            </div>
            <button type="submit" name="queue_emails" id="queueBtn" class="btn-primary">
                <span id="queueBtnText">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Einladungen generieren &amp; in Warteschlange stellen
                </span>
                <span id="queueBtnLoading" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Wird verarbeitet...
                </span>
            </button>
        </div>
    </form>
</div>

<!-- Info Text -->
<div class="card p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-3">
        <i class="fas fa-info-circle mr-2"></i>
        Hinweis
    </h3>
    <p class="text-sm text-blue-800 dark:text-blue-300">
        <i class="fas fa-arrow-right text-blue-600 dark:text-blue-400 mr-2"></i>
        Das System versendet maximal 500 E-Mails pro Stunde automatisch im Hintergrund.
    </p>
</div>

<script>
document.getElementById('queueForm').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        return;
    }
    var btn = document.getElementById('queueBtn');
    var btnText = document.getElementById('queueBtnText');
    var btnLoading = document.getElementById('queueBtnLoading');
    btn.disabled = true;
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
