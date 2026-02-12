<?php
// 1. Konfiguration laden
require_once __DIR__ . '/../../config/config.php';

// 2. Error reporting is configured in config.php based on ENVIRONMENT constant
// Detailed error display is only enabled in non-production environments
// This prevents information leakage (file paths, stack traces) in production

// 3. Weitere Abhängigkeiten laden
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

try {
    // Redirect if already authenticated
    if (Auth::check()) {
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Check for error message from OAuth
    $error = isset($_GET['error']) ? urldecode($_GET['error']) : '';

} catch (Throwable $e) {
    // 5. Display error information based on environment
    // Detailed error information is only shown in non-production environments to prevent information leakage
    echo '<div style="background-color: #fee2e2; border: 2px solid #ef4444; color: #991b1b; padding: 20px; font-family: sans-serif; margin: 20px; border-radius: 8px;">';
    echo '<h2 style="margin-top:0">Kritischer Fehler aufgetreten</h2>';
    
    if (ENVIRONMENT !== 'production') {
        // Show detailed error information only in non-production environments
        echo '<p><strong>Fehlermeldung:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Datei:</strong> ' . htmlspecialchars($e->getFile()) . ' (Zeile ' . $e->getLine() . ')</p>';
        echo '<pre style="background: #fff; padding: 10px; overflow: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Log error details to server logs in production, but show generic message to user
        // This prevents information leakage while still allowing developers to debug issues
        error_log(sprintf(
            'Login error: %s in %s:%d. Stack trace: %s',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));
        
        // Show generic error message in production to prevent information leakage
        echo '<p>Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es später erneut oder wenden Sie sich an den Administrator.</p>';
    }
    
    echo '</div>';
    exit;
}

$title = 'Login - IBC Intranet';
ob_start();
?>

<div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-2xl transition-all duration-300">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 via-blue-600 to-emerald-500 rounded-2xl mb-5 shadow-lg shadow-blue-200/50 hover:shadow-xl hover:scale-105 transition-all duration-300">
            <i class="fas fa-building text-3xl text-white"></i>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-800 mb-2 tracking-tight">Willkommen zurück</h1>
        <p class="text-gray-500 font-medium">Melde dich bei deinem Konto an</p>
    </div>

    <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg text-yellow-800">
        <i class="fas fa-clock mr-2"></i>Aus Sicherheitsgründen wurdest du automatisch ausgeloggt.
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-300 rounded-lg text-green-800">
        <i class="fas fa-check-circle mr-2"></i>Erfolgreich abgemeldet.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-300 rounded-lg text-red-800">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Microsoft Login Button -->
    <a href="<?php echo BASE_URL; ?>/auth/login_start.php" class="group relative flex items-center justify-center w-full py-4 px-6 bg-white hover:bg-gray-50 text-gray-800 rounded-xl font-semibold transition-all duration-300 transform hover:scale-[1.02] hover:-translate-y-0.5 shadow-md hover:shadow-xl tracking-wide border-2 border-gray-200 hover:border-blue-400 overflow-hidden">
        <!-- Subtle gradient overlay on hover -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50/0 via-blue-50/50 to-blue-50/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        
        <!-- Microsoft logo with better sizing -->
        <img src="<?php echo asset('assets/img/microsoft-logo.svg'); ?>" alt="Microsoft" class="relative w-6 h-6 mr-3 transition-transform duration-300 group-hover:scale-110">
        
        <!-- Button text -->
        <span class="relative">Mit Microsoft anmelden</span>
        
        <!-- Decorative shine effect -->
        <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
    </a>
    
    <div class="mt-6 text-center">
        <p class="text-gray-500 text-sm font-medium">Verwende dein Microsoft-Konto zum Anmelden.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
// Benutze require statt include für Layout, damit Fehler sichtbar werden
require __DIR__ . '/../../includes/templates/auth_layout.php';
?>