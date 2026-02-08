<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/handlers/GoogleAuthenticator.php';
require_once __DIR__ . '/../../includes/models/User.php';
require_once __DIR__ . '/../../includes/models/Alumni.php';

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$user = Auth::user();
$userRole = $user['role'] ?? ''; // Retrieve role from Auth
$message = '';
$error = '';
$showQRCode = false;
$qrCodeUrl = '';
$secret = '';

// Check for session messages from email confirmation
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Load user's profile from alumni_profiles table
$profile = Alumni::getProfileByUserId($user['id']);

// Handle 2FA setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Handle profile update
        try {
            $profileData = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['profile_email'] ?? ''),
                'mobile_phone' => trim($_POST['mobile_phone'] ?? ''),
                'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
                'xing_url' => trim($_POST['xing_url'] ?? ''),
                'about_me' => trim($_POST['about_me'] ?? ''),
                'image_path' => $profile['image_path'] ?? '' // Keep existing image by default
            ];
            
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/profile/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0750, true);
                }
                
                // Validate file extension
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($extension, $allowedExtensions)) {
                    throw new Exception('Ungültiger Dateityp. Nur JPG, PNG, GIF und WEBP sind erlaubt.');
                }
                
                // Validate actual file type using finfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
                finfo_close($finfo);
                
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mimeType, $allowedMimeTypes)) {
                    throw new Exception('Ungültiger Dateityp. Die Datei ist kein gültiges Bild.');
                }
                
                // Validate file size (max 5MB)
                if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('Datei ist zu groß. Maximale Größe ist 5MB.');
                }
                
                // Verify it's a valid image
                $imageInfo = @getimagesize($_FILES['profile_picture']['tmp_name']);
                if ($imageInfo === false) {
                    throw new Exception('Die Datei ist kein gültiges Bild.');
                }
                
                // Generate unique filename with validated extension
                $filename = 'profile_' . $user['id'] . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                    // Delete old profile picture if exists
                    if (!empty($profile['image_path'])) {
                        $oldFilePath = __DIR__ . '/../../' . $profile['image_path'];
                        // Validate that the old file is within the uploads directory
                        $realUploadDir = realpath(__DIR__ . '/../../uploads/');
                        $realOldFile = realpath($oldFilePath);
                        
                        if ($realOldFile && $realUploadDir && strpos($realOldFile, $realUploadDir) === 0 && file_exists($realOldFile)) {
                            if (!unlink($realOldFile)) {
                                error_log("Failed to delete old profile picture: " . $oldFilePath);
                            }
                        }
                    }
                    
                    $profileData['image_path'] = 'uploads/profile/' . $filename;
                } else {
                    throw new Exception('Fehler beim Hochladen der Datei.');
                }
            }
            
            // Add role-specific fields based on user role
            // Student View: member, candidate, head, board -> Show study fields
            if (in_array($userRole, ['candidate', 'member', 'board', 'head'])) {
                // Fields for students (candidates, members, board, and heads)
                $profileData['studiengang'] = trim($_POST['studiengang'] ?? '');
                // study_program: Database column alias for legacy schema compatibility
                $profileData['study_program'] = trim($_POST['studiengang'] ?? '');
                $profileData['semester'] = trim($_POST['semester'] ?? '');
                $profileData['angestrebter_abschluss'] = trim($_POST['angestrebter_abschluss'] ?? '');
                // Note: Arbeitgeber (company) fields are optional/hidden for students
            } elseif ($userRole === 'alumni') {
                // Alumni View: Show employment fields
                $profileData['company'] = trim($_POST['company'] ?? '');
                $profileData['industry'] = trim($_POST['industry'] ?? '');
                $profileData['position'] = trim($_POST['position'] ?? '');
            }
            
            // Update or create profile (only for the current user)
            if (Alumni::updateOrCreateProfile($user['id'], $profileData)) {
                $message = 'Profil erfolgreich aktualisiert';
                $profile = Alumni::getProfileByUserId($user['id']); // Reload profile
            } else {
                $error = 'Fehler beim Aktualisieren des Profils';
            }
        } catch (PDOException $e) {
            // Database protection: Graceful error handling for database issues
            error_log("Profile update database error: " . $e->getMessage());
            $error = 'Datenbank nicht aktuell. Bitte Admin kontaktieren.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email'] ?? '');
        
        // Check if email has changed
        if ($newEmail !== $user['email']) {
            try {
                // Generate token and save to email_change_requests
                $token = User::createEmailChangeRequest($user['id'], $newEmail);
                
                // Create confirmation link using BASE_URL for security
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                $confirmLink = $baseUrl . '/api/confirm_email.php?token=' . urlencode($token);
                
                // TODO: Send email to new address with confirmation link
                // For now, we'll just show a message
                // In production, use PHPMailer or similar to send email
                
                $message = 'Bestätigungslink an neue E-Mail gesendet. Bitte überprüfen Sie Ihr Postfach.';
                
                // Log the action
                error_log("Email change confirmation link: $confirmLink");
            } catch (Exception $e) {
                // Catch exceptions like 'E-Mail vergeben' or validation errors
                $error = $e->getMessage();
            }
        }
        // If email hasn't changed, just do nothing (user will see no message)
    } else if (isset($_POST['enable_2fa'])) {
        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user['email'], $secret, 'IBC Intranet');
        $showQRCode = true;
    } else if (isset($_POST['confirm_2fa'])) {
        $secret = $_POST['secret'] ?? '';
        $code = $_POST['code'] ?? '';
        
        $ga = new PHPGangsta_GoogleAuthenticator();
        if ($ga->verifyCode($secret, $code, 2)) {
            if (User::enable2FA($user['id'], $secret)) {
                $message = '2FA erfolgreich aktiviert';
                $user = Auth::user(); // Reload user
            } else {
                $error = 'Fehler beim Aktivieren von 2FA';
            }
        } else {
            $error = 'Ungültiger Code. Bitte versuchen Sie es erneut.';
            $secret = $_POST['secret'];
            $ga = new PHPGangsta_GoogleAuthenticator();
            $qrCodeUrl = $ga->getQRCodeGoogleUrl($user['email'], $secret, 'IBC Intranet');
            $showQRCode = true;
        }
    } else if (isset($_POST['disable_2fa'])) {
        if (User::disable2FA($user['id'])) {
            $message = '2FA erfolgreich deaktiviert';
            $user = Auth::user(); // Reload user
        } else {
            $error = 'Fehler beim Deaktivieren von 2FA';
        }
    } else if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $fullUser = User::getByEmail($user['email']);
        
        if (!password_verify($currentPassword, $fullUser['password'])) {
            $error = 'Aktuelles Passwort ist falsch';
        } else if ($newPassword !== $confirmPassword) {
            $error = 'Neue Passwörter stimmen nicht überein';
        } else if (strlen($newPassword) < 8) {
            $error = 'Neues Passwort muss mindestens 8 Zeichen lang sein';
        } else {
            if (User::changePassword($user['id'], $newPassword)) {
                $message = 'Passwort erfolgreich geändert';
            } else {
                $error = 'Fehler beim Ändern des Passworts';
            }
        }
    } else if (isset($_POST['update_notifications'])) {
        $notifyNewProjects = isset($_POST['notify_new_projects']) ? true : false;
        $notifyNewEvents = isset($_POST['notify_new_events']) ? true : false;
        
        if (User::updateNotificationPreferences($user['id'], $notifyNewProjects, $notifyNewEvents)) {
            $message = 'Benachrichtigungseinstellungen erfolgreich aktualisiert';
            $user = Auth::user(); // Reload user data
        } else {
            $error = 'Fehler beim Aktualisieren der Benachrichtigungseinstellungen';
        }
    } else if (isset($_POST['update_theme'])) {
        $theme = $_POST['theme'] ?? 'auto';
        
        // Validate theme value
        if (!in_array($theme, ['auto', 'light', 'dark'])) {
            $error = 'Ungültiger Theme-Wert';
        } else {
            if (User::updateThemePreference($user['id'], $theme)) {
                $message = 'Theme-Einstellungen erfolgreich aktualisiert';
                $user = Auth::user(); // Reload user data
            } else {
                $error = 'Fehler beim Aktualisieren der Theme-Einstellungen';
            }
        }
    }
}

$title = 'Profil - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-user text-purple-600 mr-2"></i>
                Mein Profil
            </h1>
            <p class="text-gray-600">Verwalten Sie Ihre Kontoinformationen und Sicherheitseinstellungen</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="../inventory/my_rentals.php" class="btn-primary inline-block">
                <i class="fas fa-clipboard-list mr-2"></i>
                Meine Ausleihen
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Account Info -->
    <div class="card p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            Kontoinformationen
        </h2>
        <div class="space-y-4">
            <div>
                <label class="text-sm text-gray-500">E-Mail</label>
                <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Rolle</label>
                <p class="text-lg">
                    <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full">
                        <?php echo translateRole($user['role']); ?>
                    </span>
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Letzter Login</label>
                <p class="text-lg text-gray-800">
                    <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Mitglied seit</label>
                <p class="text-lg text-gray-800"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Update Email -->
    <div class="card p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-envelope text-blue-600 mr-2"></i>
            E-Mail-Adresse ändern
        </h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">E-Mail-Adresse</label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    value="<?php echo htmlspecialchars($user['email']); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <button type="submit" name="update_email" class="w-full btn-primary">
                <i class="fas fa-save mr-2"></i>E-Mail-Adresse aktualisieren
            </button>
        </form>
    </div>

    <!-- Profile Information -->
    <div class="lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-user-edit text-purple-600 mr-2"></i>
                Profilangaben
            </h2>
            <p class="text-gray-600 mb-6">
                Aktualisieren Sie Ihre persönlichen Informationen und Kontaktdaten
            </p>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Common Fields -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vorname *</label>
                        <input 
                            type="text" 
                            name="first_name" 
                            required 
                            value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nachname *</label>
                        <input 
                            type="text" 
                            name="last_name" 
                            required 
                            value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">E-Mail (Profil) *</label>
                        <input 
                            type="email" 
                            name="profile_email" 
                            required 
                            value="<?php echo htmlspecialchars($profile['email'] ?? $user['email']); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefon</label>
                        <input 
                            type="text" 
                            name="mobile_phone" 
                            value="<?php echo htmlspecialchars($profile['mobile_phone'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="+49 123 456789"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">LinkedIn URL</label>
                        <input 
                            type="url" 
                            name="linkedin_url" 
                            value="<?php echo htmlspecialchars($profile['linkedin_url'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="https://linkedin.com/in/..."
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Xing URL</label>
                        <input 
                            type="url" 
                            name="xing_url" 
                            value="<?php echo htmlspecialchars($profile['xing_url'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="https://xing.com/profile/..."
                        >
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profilbild</label>
                        <?php if (!empty($profile['image_path'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo asset($profile['image_path']); ?>" alt="Profilbild" class="w-32 h-32 object-cover rounded-lg border-2 border-gray-300">
                        </div>
                        <?php endif; ?>
                        <input 
                            type="file" 
                            name="profile_picture" 
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF oder WEBP (Max. 5MB)</p>
                    </div>
                    
                    <?php if (in_array($userRole, ['candidate', 'member', 'board', 'head'])): ?>
                    <!-- Fields for Students: Candidates, Members, Board, and Heads -->
                    <!-- Student View: Show Studiengang, Semester, Abschluss -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Studiengang</label>
                        <input 
                            type="text" 
                            name="studiengang" 
                            value="<?php echo htmlspecialchars($profile['studiengang'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="z.B. Wirtschaftsingenieurwesen"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                        <input 
                            type="text" 
                            name="semester" 
                            value="<?php echo htmlspecialchars($profile['semester'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="z.B. 5"
                        >
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Angestrebter Abschluss</label>
                        <input 
                            type="text" 
                            name="angestrebter_abschluss" 
                            value="<?php echo htmlspecialchars($profile['angestrebter_abschluss'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="z.B. Bachelor of Science"
                        >
                    </div>
                    <?php elseif ($userRole === 'alumni'): ?>
                    <!-- Fields for Alumni -->
                    <!-- Alumni View: Show Arbeitgeber, Position, Branche -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Aktueller Arbeitgeber</label>
                        <input 
                            type="text" 
                            name="company" 
                            value="<?php echo htmlspecialchars($profile['company'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Firmenname"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                        <input 
                            type="text" 
                            name="position" 
                            value="<?php echo htmlspecialchars($profile['position'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="z.B. Senior Consultant"
                        >
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Branche</label>
                        <input 
                            type="text" 
                            name="industry" 
                            value="<?php echo htmlspecialchars($profile['industry'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="z.B. Beratung, IT, Finanzen"
                        >
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- About Me - Full Width -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Über mich</label>
                    <textarea 
                        name="about_me" 
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Erzählen Sie etwas über sich..."
                    ><?php echo htmlspecialchars($profile['about_me'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="w-full btn-primary">
                    <i class="fas fa-save mr-2"></i>Profil speichern
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-key text-yellow-600 mr-2"></i>
            Passwort ändern
        </h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Aktuelles Passwort</label>
                <input 
                    type="password" 
                    name="current_password" 
                    required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Neues Passwort</label>
                <input 
                    type="password" 
                    name="new_password" 
                    required 
                    minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Passwort bestätigen</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    required 
                    minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <button type="submit" name="change_password" class="w-full btn-primary">
                <i class="fas fa-save mr-2"></i>Passwort ändern
            </button>
        </form>
    </div>

    <!-- 2FA Settings -->
    <div class="lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                Zwei-Faktor-Authentifizierung (2FA)
            </h2>

            <?php if (!$showQRCode): ?>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-gray-700 mb-2">
                        Status: 
                        <?php if ($user['tfa_enabled']): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-semibold">
                            <i class="fas fa-check-circle mr-1"></i>Aktiviert
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full font-semibold">
                            <i class="fas fa-times-circle mr-1"></i>Deaktiviert
                        </span>
                        <?php endif; ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        Schützen Sie Ihr Konto mit einer zusätzlichen Sicherheitsebene
                    </p>
                </div>
                <div>
                    <?php if ($user['tfa_enabled']): ?>
                    <form method="POST" onsubmit="return confirm('Möchten Sie 2FA wirklich deaktivieren?');">
                        <button type="submit" name="disable_2fa" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-times mr-2"></i>2FA deaktivieren
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST">
                        <button type="submit" name="enable_2fa" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i>2FA aktivieren
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Empfehlung:</strong> Aktivieren Sie 2FA für zusätzliche Sicherheit. Sie benötigen eine Authenticator-App wie Google Authenticator oder Authy.
                </p>
            </div>
            <?php else: ?>
            <!-- QR Code Setup -->
            <div class="max-w-md mx-auto">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">2FA einrichten</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Scannen Sie den QR-Code mit Ihrer Authenticator-App und geben Sie den generierten Code ein
                    </p>
                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="mx-auto mb-4 border-4 border-gray-200 rounded-lg">
                    <p class="text-xs text-gray-500 mb-4">
                        Geheimer Schlüssel (manuell): <code class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($secret); ?></code>
                    </p>
                </div>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="secret" value="<?php echo htmlspecialchars($secret); ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">6-stelliger Code</label>
                        <input 
                            type="text" 
                            name="code" 
                            required 
                            maxlength="6"
                            pattern="[0-9]{6}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-center text-2xl tracking-widest"
                            placeholder="000000"
                            autofocus
                        >
                    </div>
                    <div class="flex space-x-4">
                        <a href="profile.php" class="flex-1 text-center px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Abbrechen
                        </a>
                        <button type="submit" name="confirm_2fa" class="flex-1 btn-primary">
                            <i class="fas fa-check mr-2"></i>Bestätigen
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-bell text-orange-600 mr-2"></i>
                Benachrichtigungen
            </h2>
            <p class="text-gray-600 mb-6">
                Wählen Sie aus, über welche Ereignisse Sie per E-Mail benachrichtigt werden möchten
            </p>
            
            <form method="POST" class="space-y-4">
                <div class="space-y-4">
                    <!-- New Projects Notification -->
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <input 
                            type="checkbox" 
                            name="notify_new_projects" 
                            id="notify_new_projects"
                            <?php echo ($user['notify_new_projects'] ?? true) ? 'checked' : ''; ?>
                            class="mt-1 h-5 w-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                        >
                        <label for="notify_new_projects" class="ml-3 flex-1 cursor-pointer">
                            <span class="block text-sm font-medium text-gray-900">Neue Projekte</span>
                            <span class="block text-sm text-gray-600">
                                Erhalten Sie eine E-Mail-Benachrichtigung, wenn ein neues Projekt veröffentlicht wird
                            </span>
                        </label>
                    </div>

                    <!-- New Events Notification -->
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <input 
                            type="checkbox" 
                            name="notify_new_events" 
                            id="notify_new_events"
                            <?php echo ($user['notify_new_events'] ?? false) ? 'checked' : ''; ?>
                            class="mt-1 h-5 w-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                        >
                        <label for="notify_new_events" class="ml-3 flex-1 cursor-pointer">
                            <span class="block text-sm font-medium text-gray-900">Neue Events</span>
                            <span class="block text-sm text-gray-600">
                                Erhalten Sie eine E-Mail-Benachrichtigung, wenn ein neues Event erstellt wird
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="update_notifications" class="w-full btn-primary">
                    <i class="fas fa-save mr-2"></i>Benachrichtigungseinstellungen speichern
                </button>
            </form>
        </div>
    </div>
    
    <!-- Theme Settings -->
    <div class="lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-palette text-purple-600 mr-2"></i>
                Theme-Einstellungen
            </h2>
            <p class="text-gray-600 mb-6">
                Wählen Sie Ihr bevorzugtes Farbschema für die Anwendung
            </p>
            
            <form method="POST" class="space-y-4">
                <div class="space-y-4">
                    <!-- Auto Theme -->
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-purple-400 transition cursor-pointer">
                        <input 
                            type="radio" 
                            name="theme" 
                            value="auto" 
                            id="theme_auto"
                            <?php echo ($user['theme_preference'] ?? 'auto') === 'auto' ? 'checked' : ''; ?>
                            class="mt-1 h-5 w-5 text-purple-600 border-gray-300 focus:ring-purple-500"
                        >
                        <label for="theme_auto" class="ml-3 flex-1 cursor-pointer">
                            <span class="block text-sm font-medium text-gray-900">
                                <i class="fas fa-adjust mr-2"></i>Automatisch
                            </span>
                            <span class="block text-sm text-gray-600">
                                Verwendet die Systemeinstellungen Ihres Geräts
                            </span>
                        </label>
                    </div>

                    <!-- Light Theme -->
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-purple-400 transition cursor-pointer">
                        <input 
                            type="radio" 
                            name="theme" 
                            value="light" 
                            id="theme_light"
                            <?php echo ($user['theme_preference'] ?? 'auto') === 'light' ? 'checked' : ''; ?>
                            class="mt-1 h-5 w-5 text-purple-600 border-gray-300 focus:ring-purple-500"
                        >
                        <label for="theme_light" class="ml-3 flex-1 cursor-pointer">
                            <span class="block text-sm font-medium text-gray-900">
                                <i class="fas fa-sun mr-2"></i>Hell
                            </span>
                            <span class="block text-sm text-gray-600">
                                Immer den hellen Modus verwenden
                            </span>
                        </label>
                    </div>

                    <!-- Dark Theme -->
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-purple-400 transition cursor-pointer">
                        <input 
                            type="radio" 
                            name="theme" 
                            value="dark" 
                            id="theme_dark"
                            <?php echo ($user['theme_preference'] ?? 'auto') === 'dark' ? 'checked' : ''; ?>
                            class="mt-1 h-5 w-5 text-purple-600 border-gray-300 focus:ring-purple-500"
                        >
                        <label for="theme_dark" class="ml-3 flex-1 cursor-pointer">
                            <span class="block text-sm font-medium text-gray-900">
                                <i class="fas fa-moon mr-2"></i>Dunkel
                            </span>
                            <span class="block text-sm text-gray-600">
                                Immer den dunklen Modus verwenden
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="update_theme" class="w-full btn-primary">
                    <i class="fas fa-save mr-2"></i>Theme-Einstellungen speichern
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
