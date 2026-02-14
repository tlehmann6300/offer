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

<div class="w-full max-w-md p-10 bg-white rounded-2xl shadow-2xl transition-all duration-500 hover:shadow-3xl">
    <!-- Enhanced Header with Gradient Icon -->
    <div class="text-center mb-10">
        <div class="relative inline-flex items-center justify-center mb-6">
            <!-- Animated Glow Ring -->
            <div class="absolute inset-0 w-20 h-20 bg-gradient-to-br from-blue-400 via-blue-500 to-emerald-400 rounded-2xl opacity-20 animate-pulse blur-xl"></div>
            
            <!-- Icon Container with 3D Effect -->
            <div class="relative inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 via-blue-600 to-emerald-500 rounded-2xl shadow-2xl shadow-blue-500/50 hover:shadow-emerald-500/50 hover:scale-110 transition-all duration-500 hover:rotate-6">
                <i class="fas fa-building text-4xl text-white drop-shadow-lg"></i>
                
                <!-- Sparkle Effects -->
                <div class="absolute -top-1 -right-1 w-3 h-3 bg-white rounded-full animate-ping opacity-75"></div>
                <div class="absolute -bottom-1 -left-1 w-2 h-2 bg-emerald-300 rounded-full animate-ping opacity-75" style="animation-delay: 0.5s;"></div>
            </div>
        </div>
        
        <h1 class="text-4xl font-black bg-gradient-to-r from-gray-800 via-gray-900 to-gray-800 bg-clip-text text-transparent mb-3 tracking-tight animate-fade-in">
            Willkommen zurück
        </h1>
        <p class="text-gray-600 font-semibold text-lg">Melde dich bei deinem Konto an</p>
    </div>

    <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
    <div class="mb-6 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-400 rounded-lg text-yellow-800 shadow-md animate-slide-in">
        <div class="flex items-center">
            <i class="fas fa-clock mr-3 text-xl"></i>
            <span class="font-medium">Aus Sicherheitsgründen wurdest du automatisch ausgeloggt.</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
    <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-400 rounded-lg text-green-800 shadow-md animate-slide-in">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <span class="font-medium">Erfolgreich abgemeldet.</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-400 rounded-lg text-red-800 shadow-md animate-slide-in">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Microsoft Login Button - Official Branding -->
    <a href="<?php echo BASE_URL; ?>/auth/login_start.php" class="microsoft-btn group relative flex items-center justify-center w-full py-4 px-6 text-white rounded-lg font-semibold text-base transition-all duration-300 shadow-lg hover:shadow-xl overflow-hidden">
        <!-- Microsoft logo -->
        <img src="<?php echo asset('assets/img/microsoft-logo.svg'); ?>" alt="Microsoft" class="relative z-10 w-5 h-5 transition-transform duration-300 group-hover:scale-110">
        
        <!-- Vertical Separator -->
        <div class="relative z-10 w-px h-6 bg-white/20 mx-4"></div>
        
        <!-- Button text -->
        <span class="relative z-10 font-bold">
            Sign in with Microsoft
        </span>
    </a>
    
    <div class="mt-6 text-center">
        <p class="text-gray-600 text-sm">Verwende dein Microsoft-Konto zum Anmelden.</p>
        <div class="flex items-center justify-center space-x-2 text-gray-400 text-xs mt-2">
            <i class="fas fa-shield-alt"></i>
            <span>Sichere Anmeldung über Microsoft</span>
        </div>
    </div>
    
    <!-- Additional Style Tags for Animations -->
    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slide-in {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fade-in {
            animation: fade-in 0.8s ease-out;
        }
        
        .animate-slide-in {
            animation: slide-in 0.6s ease-out;
        }
    </style>
</div>

<?php
$content = ob_get_clean();
// Benutze require statt include für Layout, damit Fehler sichtbar werden
require __DIR__ . '/../../includes/templates/auth_layout.php';
?>