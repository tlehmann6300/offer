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

    <!-- Enhanced Microsoft Login Button with Premium Effects -->
    <a href="<?php echo BASE_URL; ?>/auth/login_start.php" class="group relative flex items-center justify-center w-full py-5 px-8 bg-gradient-to-r from-white via-gray-50 to-white hover:from-blue-50 hover:via-blue-100 hover:to-blue-50 text-gray-900 rounded-2xl font-bold text-lg transition-all duration-500 transform hover:scale-[1.03] hover:-translate-y-1 shadow-xl hover:shadow-2xl tracking-wide border-2 border-gray-200 hover:border-blue-400 overflow-hidden">
        <!-- Animated Background Gradient -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-emerald-500/0 to-blue-500/0 group-hover:from-blue-500/10 group-hover:via-emerald-500/10 group-hover:to-blue-500/10 transition-all duration-700"></div>
        
        <!-- Glow Effect on Hover -->
        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 blur-2xl bg-gradient-to-r from-blue-400 to-emerald-400 transition-opacity duration-500"></div>
        
        <!-- Microsoft logo with enhanced animation -->
        <img src="<?php echo asset('assets/img/microsoft-logo.svg'); ?>" alt="Microsoft" class="relative z-10 w-7 h-7 mr-4 transition-all duration-500 group-hover:scale-125 group-hover:rotate-12 drop-shadow-lg">
        
        <!-- Button text with gradient on hover -->
        <span class="relative z-10 group-hover:bg-gradient-to-r group-hover:from-blue-600 group-hover:to-emerald-600 group-hover:bg-clip-text group-hover:text-transparent transition-all duration-500">
            Mit Microsoft anmelden
        </span>
        
        <!-- Animated Arrow -->
        <i class="fas fa-arrow-right relative z-10 ml-3 opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-2 transition-all duration-500"></i>
        
        <!-- Premium Shine effect -->
        <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
        
        <!-- Corner Accents -->
        <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-blue-400 rounded-tl-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-emerald-400 rounded-br-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
    </a>
    
    <div class="mt-8 text-center space-y-2">
        <p class="text-gray-600 text-base font-semibold">Verwende dein Microsoft-Konto zum Anmelden.</p>
        <div class="flex items-center justify-center space-x-2 text-gray-400 text-sm">
            <i class="fas fa-shield-alt"></i>
            <span class="font-medium">Sichere Anmeldung über Microsoft</span>
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