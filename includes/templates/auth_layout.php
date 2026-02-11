<?php
require_once __DIR__ . '/../helpers.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'IBC Intranet'; ?></title>
    <link rel="icon" type="image/webp" href="<?php echo asset('assets/img/cropped_maskottchen_32x32.webp'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset('assets/css/theme.css'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ibc-green': 'var(--ibc-green)',
                        'ibc-green-light': 'var(--ibc-green-light)',
                        'ibc-green-dark': 'var(--ibc-green-dark)',
                        'ibc-blue': 'var(--ibc-blue)',
                        'ibc-blue-light': 'var(--ibc-blue-light)',
                        'ibc-blue-dark': 'var(--ibc-blue-dark)',
                        'ibc-accent': 'var(--ibc-accent)',
                        'ibc-accent-light': 'var(--ibc-accent-light)',
                        'ibc-accent-dark': 'var(--ibc-accent-dark)',
                    },
                    fontFamily: {
                        'sans': ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    },
                    boxShadow: {
                        'glow': 'var(--shadow-glow-green)',
                        'premium': 'var(--shadow-premium)',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a1628 0%, #0d2137 30%, #0f172a 60%, #162033 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 100%;
            background: radial-gradient(circle, rgba(0, 166, 81, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 60%;
            height: 80%;
            background: radial-gradient(circle, rgba(0, 102, 179, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .auth-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }
        .auth-card .bg-white {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 20px !important;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        .floating-dot {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: float 15s ease-in-out infinite;
            will-change: transform;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Decorative floating elements -->
    <div class="floating-dot w-64 h-64 top-20 -left-20 bg-green-500" style="animation-delay: 0s;" aria-hidden="true"></div>
    <div class="floating-dot w-48 h-48 top-40 right-10 bg-blue-500" style="animation-delay: -5s;" aria-hidden="true"></div>
    <div class="floating-dot w-32 h-32 bottom-20 left-1/4 bg-blue-400" style="animation-delay: -10s;" aria-hidden="true"></div>

    <div class="relative z-10 flex flex-col items-center justify-center min-h-screen p-4">
        <!-- IBC Logo above content -->
        <div class="mb-8">
            <img src="<?php echo asset('assets/img/ibc_logo_original.webp'); ?>" alt="IBC Logo" class="mx-auto h-20 w-auto drop-shadow-2xl" style="filter: brightness(1.1) drop-shadow(0 4px 20px rgba(0, 166, 81, 0.3));">
        </div>
        
        <!-- Content area wrapped in auth-card -->
        <div class="auth-card p-1.5 w-full max-w-md">
            <?php echo $content ?? ''; ?>
        </div>
        
        <!-- Footer text -->
        <p class="mt-8 text-sm text-white/30 font-medium tracking-wide">© <?php echo date('Y'); ?> IBC · Intranet Platform</p>
    </div>
</body>
</html>
