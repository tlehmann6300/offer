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

<div class="w-full max-w-md p-6 sm:p-8 md:p-10 bg-white rounded-2xl shadow-2xl transition-all duration-500 hover:shadow-3xl">
    <!-- Enhanced Header with Gradient Icon -->
    <div class="text-center mb-8 sm:mb-10">
        <div class="relative inline-flex items-center justify-center mb-5 sm:mb-6">
            <!-- Animated Glow Ring -->
            <div class="absolute inset-0 w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-blue-400 via-blue-500 to-emerald-400 rounded-2xl opacity-30 animate-pulse blur-xl"></div>
            
            <!-- Icon Container with 3D Effect -->
            <div class="relative inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-blue-500 via-blue-600 to-emerald-500 rounded-2xl shadow-2xl shadow-blue-500/50 hover:shadow-emerald-500/50 hover:scale-110 transition-all duration-500 hover:rotate-6">
                <i class="fas fa-building text-3xl sm:text-4xl text-white drop-shadow-lg"></i>
                
                <!-- Sparkle Effects -->
                <div class="absolute -top-1 -right-1 w-2 h-2 sm:w-3 sm:h-3 bg-white rounded-full animate-ping opacity-75"></div>
                <div class="absolute -bottom-1 -left-1 w-1.5 h-1.5 sm:w-2 sm:h-2 bg-emerald-300 rounded-full animate-ping opacity-75" style="animation-delay: 0.5s;"></div>
            </div>
        </div>
        
        <h1 class="text-3xl sm:text-4xl font-black text-white mb-2 sm:mb-3 tracking-tight animate-fade-in text-shadow-strong">
            Willkommen zurück
        </h1>
        <p class="text-white/90 font-semibold text-base sm:text-lg text-shadow-medium">Melde dich mit deinem Konto an</p>
    </div>

    <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
    <div class="mb-6 p-4 bg-gradient-to-r from-yellow-500/20 to-amber-500/20 backdrop-blur-md border border-yellow-400/40 rounded-lg text-white shadow-lg animate-slide-in text-shadow-light">
        <div class="flex items-center">
            <i class="fas fa-clock mr-3 text-xl text-yellow-300"></i>
            <span class="font-medium">Aus Sicherheitsgründen wurdest du automatisch ausgeloggt.</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
    <div class="mb-6 p-4 bg-gradient-to-r from-green-500/20 to-emerald-500/20 backdrop-blur-md border border-green-400/40 rounded-lg text-white shadow-lg animate-slide-in text-shadow-light">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl text-green-300"></i>
            <span class="font-medium">Erfolgreich abgemeldet.</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-gradient-to-r from-red-500/20 to-rose-500/20 backdrop-blur-md border border-red-400/40 rounded-lg text-white shadow-lg animate-slide-in text-shadow-light">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl text-red-300"></i>
            <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Divider with "Oder" text -->
    <div class="relative flex items-center justify-center my-6 sm:my-8">
        <div class="flex-grow border-t border-white/30"></div>
        <span class="px-3 sm:px-4 text-xs sm:text-sm text-white/80 font-medium text-shadow-light">Anmelden mit</span>
        <div class="flex-grow border-t border-white/30"></div>
    </div>

    <!-- Ultra-Premium Microsoft Login Button with 3D Glass Effect -->
    <div class="relative group">
        <!-- Animated Background Glow -->
        <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 rounded-2xl blur-lg opacity-30 group-hover:opacity-60 transition-all duration-500 animate-pulse"></div>
        
        <!-- Main Button -->
        <a href="<?php echo BASE_URL; ?>/auth/login_start.php" 
           class="relative flex items-center justify-center gap-3 sm:gap-4 w-full py-4 sm:py-5 px-6 sm:px-8 bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 hover:from-blue-700 hover:via-blue-800 hover:to-blue-900 text-white rounded-xl sm:rounded-2xl font-bold text-sm sm:text-base transition-all duration-500 shadow-2xl hover:shadow-blue-500/50 hover:scale-[1.03] transform group overflow-hidden"
           aria-label="Mit deinem Microsoft-Konto anmelden">
            
            <!-- Shimmer Effect -->
            <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
            
            <!-- Microsoft logo - Enhanced with Animation -->
            <div class="relative flex-shrink-0 transition-all duration-500 group-hover:rotate-[360deg] group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" class="drop-shadow-lg" aria-hidden="true">
                    <rect x="1" y="1" width="10" height="10" fill="#f25022"/>
                    <rect x="1" y="13" width="10" height="10" fill="#00a4ef"/>
                    <rect x="13" y="1" width="10" height="10" fill="#7fba00"/>
                    <rect x="13" y="13" width="10" height="10" fill="#ffb900"/>
                </svg>
            </div>
            
            <!-- Button text with Letter Spacing -->
            <span class="relative font-black tracking-wider text-shadow-lg flex-1 text-center">Mit Microsoft anmelden</span>
            
            <!-- Enhanced Arrow with Bounce Animation -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6 flex-shrink-0 transition-all duration-500 group-hover:translate-x-2 group-hover:scale-125" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
            
            <!-- Floating Sparkles on Hover -->
            <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-white rounded-full opacity-0 group-hover:opacity-100 group-hover:animate-ping"></div>
            <div class="absolute top-1/3 right-1/3 w-1.5 h-1.5 bg-cyan-200 rounded-full opacity-0 group-hover:opacity-100 group-hover:animate-ping" style="animation-delay: 0.2s;"></div>
            <div class="absolute bottom-1/4 right-1/4 w-1 h-1 bg-white rounded-full opacity-0 group-hover:opacity-100 group-hover:animate-ping" style="animation-delay: 0.4s;"></div>
        </a>
    </div>
    
    <!-- Enhanced Info Section with Icons -->
    <div class="mt-5 sm:mt-6 text-center space-y-2 sm:space-y-3">
        <p class="text-white/80 text-xs sm:text-sm font-medium text-shadow-light">Verwende dein Microsoft-Konto zum Anmelden</p>
        <div class="flex items-center justify-center space-x-4 sm:space-x-6 text-white/70 text-xs text-shadow-light">
            <div class="flex items-center space-x-1.5 transition-colors duration-300 hover:text-blue-300">
                <i class="fas fa-shield-alt text-sm sm:text-base"></i>
                <span class="hidden sm:inline">Sicher</span>
            </div>
            <div class="flex items-center space-x-1.5 transition-colors duration-300 hover:text-green-300">
                <i class="fas fa-lock text-sm sm:text-base"></i>
                <span class="hidden sm:inline">Verschlüsselt</span>
            </div>
            <div class="flex items-center space-x-1.5 transition-colors duration-300 hover:text-emerald-300">
                <i class="fas fa-check-circle text-sm sm:text-base"></i>
                <span class="hidden sm:inline">Vertraut</span>
            </div>
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
        
        .text-shadow-lg {
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Enhanced responsive styles */
        @media (max-width: 640px) {
            .w-full.max-w-md {
                max-width: calc(100vw - 2rem);
            }
        }
    </style>
</div>

<?php
$content = ob_get_clean();
// Benutze require statt include für Layout, damit Fehler sichtbar werden
require __DIR__ . '/../../includes/templates/auth_layout.php';
?>