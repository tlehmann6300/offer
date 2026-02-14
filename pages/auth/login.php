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
    <!-- Welcome Text -->
    <div class="text-center mb-8 sm:mb-10">
        <h1 class="text-3xl sm:text-4xl font-black text-white mb-2 sm:mb-3 tracking-tight animate-fade-in text-shadow-strong">
            Willkommen zurück
        </h1>
        <p class="text-white/90 font-semibold text-base sm:text-lg text-shadow-medium">Melde dich mit deinem Microsoft-Konto an</p>
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

    <!-- Microsoft Login Button - Styled like newloginpage.html -->
    <a href="<?php echo BASE_URL; ?>/auth/login_start.php" 
       class="microsoft-button relative flex items-center justify-center gap-3 sm:gap-4 w-full py-5 sm:py-6 px-6 sm:px-8 bg-gradient-to-br from-white via-gray-50 to-white hover:from-gray-50 hover:via-white hover:to-gray-50 text-gray-800 rounded-xl sm:rounded-2xl font-bold text-base sm:text-lg transition-all duration-500 shadow-2xl hover:shadow-3xl hover:scale-[1.02] transform group overflow-hidden border-2 border-green-500/20"
       aria-label="Mit deinem Microsoft-Konto anmelden"
       style="box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.8);">
        
        <!-- Shimmer Effect -->
        <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 bg-gradient-to-r from-transparent via-green-400/30 to-transparent"></div>
        
        <!-- Microsoft logo - Grid Style from newloginpage.html -->
        <div class="relative flex-shrink-0 microsoft-logo">
            <div style="width: 24px; height: 24px; display: grid; grid-template-columns: repeat(2, 11px); grid-template-rows: repeat(2, 11px); gap: 2px;">
                <div style="background: #f25022; width: 11px; height: 11px;"></div>
                <div style="background: #7fba00; width: 11px; height: 11px;"></div>
                <div style="background: #00a4ef; width: 11px; height: 11px;"></div>
                <div style="background: #ffb900; width: 11px; height: 11px;"></div>
            </div>
        </div>
        
        <!-- Button text -->
        <span class="relative font-bold tracking-wide flex-1 text-center">Mit Microsoft anmelden</span>
    </a>
    
    <!-- Footer -->
    <div class="mt-6 sm:mt-8 text-center">
        <p class="text-white/60 text-xs sm:text-sm font-medium text-shadow-light">&copy; <span id="currentYear"><?php echo date('Y'); ?></span> IBC Business Consulting. Alle Rechte vorbehalten.</p>
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
        
        .microsoft-button {
            position: relative;
        }
        
        /* Microsoft logo animation */
        .microsoft-logo {
            transition: all 0.4s ease;
        }
        
        .microsoft-button:hover .microsoft-logo {
            transform: scale(1.1);
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