<?php
// 1. Konfiguration laden
require_once __DIR__ . '/../../config/config.php';

// Security Headers (CSP) für maximale Sicherheit
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; form-action 'self';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

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

<div class="login-page-wrapper">
    <div class="login-container glass-card animated-entrance">
        <!-- LOGO ANIMATION -->
        <div class="logo-container">
            <div class="logo-glow"></div>
            <div class="logo-wrapper">
                <svg class="ibc-logo" viewBox="0 0 5016 2287" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1234.9" y="30.021" width="240.167" height="1839.458" fill="#6cb73e"/>
                    <path d="M2069.658,1864.021c79.146,10.917 204.688,21.833 368.438,21.833c300.208,0 507.625,-54.583 633.167,-171.938c95.521,-87.333 158.292,-210.146 158.292,-368.438c0,-272.917 -204.688,-417.563 -379.354,-458.5l0,-8.188c191.042,-68.229 311.125,-223.792 311.125,-403.917c0,-144.646 -60.042,-253.812 -155.562,-324.771c-111.896,-92.792 -264.729,-133.729 -502.167,-133.729c-163.75,0 -330.229,16.375 -433.938,40.938l0,1806.708Zm237.438,-1648.417c38.208,-8.187 100.979,-16.375 210.146,-16.375c240.167,0 401.188,87.333 401.188,300.208c0,177.396 -147.375,311.125 -395.729,311.125l-215.604,0l0,-594.958Zm0,772.354l196.5,0c259.271,0 474.875,106.438 474.875,354.792c0,267.458 -226.521,357.521 -472.146,357.521c-84.604,0 -150.104,-2.729 -199.229,-10.917l0,-701.396Z" fill="#6cb73e"/>
                    <path d="M4963.756,1621.125c-95.521,46.396 -242.896,76.417 -390.271,76.417c-444.854,0 -704.125,-286.563 -704.125,-739.604c0,-483.062 286.562,-758.708 717.771,-758.708c152.833,0 281.104,32.75 368.438,76.417l60.042,-193.771c-62.771,-32.75 -210.146,-81.875 -436.667,-81.875c-570.396,0 -960.667,387.542 -960.667,966.125c0,605.875 387.542,933.375 906.083,933.375c223.792,0 401.188,-43.667 485.792,-87.333l-46.396,-191.042Z" fill="#6cb73e"/>
                    <path d="M1018.765,844.401l-1018.765,1018.773l1018.765,0l0,-1018.773Z" fill="#ffffff"/>
                    <path d="M1018.765,347.539l-836.007,836.009l237.49,237.492l598.517,-598.525" fill="#6cb73e"/>
                    <path d="M1018.765,53.816l-562.483,562.485l135.722,136.093l426.761,-426.767" fill="#646464"/>
                </svg>
            </div>
        </div>

    <!-- Welcome Text -->
    <div class="welcome-text">
        <h1 class="welcome-title">Willkommen zurück</h1>
        <p class="welcome-subtitle">Melde dich mit deinem Microsoft-Konto an</p>
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

    <!-- Microsoft Login Button -->
    <a href="<?php echo BASE_URL; ?>/auth/login_start.php" class="microsoft-btn" id="loginButton" onclick="return handleLogin(event)">
        <div class="microsoft-logo">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
        <span>Mit Microsoft anmelden</span>
        <div class="loading-spinner"></div>
        <div class="success-checkmark"></div>
    </a>

        <!-- Footer -->
        <div class="login-footer" style="color: #ffffff; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);">
            <p>&copy; <?php echo date('Y'); ?> IBC Business Consulting. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</div>

<script>
    // Ripple Effect on Button Click
    function createRipple(event) {
        const button = event.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        ripple.className = 'ripple';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';

        button.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    }

    // Microsoft Login Handler with proper animation timing
    function handleLogin(event) {
        event.preventDefault();
        createRipple(event);
        
        const button = document.getElementById('loginButton');
        if (button.classList.contains('loading') || button.classList.contains('success')) {
            return false;
        }
        
        button.classList.add('loading');
        
        // Show loading, then success, then navigate
        setTimeout(() => {
            button.classList.remove('loading');
            button.classList.add('success');
            
            setTimeout(() => {
                window.location.href = button.href;
            }, 800);
        }, 1500);
        
        return false;
    }
</script>

<?php
$content = ob_get_clean();
// Benutze require statt include für Layout, damit Fehler sichtbar werden
require __DIR__ . '/../../includes/templates/auth_layout.php';
?>