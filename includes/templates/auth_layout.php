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
        /* Advanced Animated Gradient Background */
        body {
            background: linear-gradient(135deg, 
                #0a1628 0%, 
                #0d2137 20%,
                #0f172a 40%, 
                #162033 60%,
                #1a1f3a 80%,
                #0d1b2a 100%
            );
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            animation: gradientShift 25s ease infinite;
            background-size: 400% 400%;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            25% { background-position: 50% 25%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 50% 75%; }
        }
        
        /* Enhanced Glowing Orbs with Pulsing */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 100%;
            background: radial-gradient(circle, rgba(0, 166, 81, 0.18) 0%, rgba(0, 166, 81, 0.08) 40%, transparent 70%);
            pointer-events: none;
            animation: pulse 10s ease-in-out infinite, drift 30s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 60%;
            height: 80%;
            background: radial-gradient(circle, rgba(0, 102, 179, 0.18) 0%, rgba(0, 102, 179, 0.08) 40%, transparent 70%);
            pointer-events: none;
            animation: pulse 10s ease-in-out infinite, drift 30s ease-in-out infinite;
            animation-delay: -5s, -15s;
        }
        
        @keyframes pulse {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.7;
                transform: scale(1.1);
            }
        }
        
        @keyframes drift {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(20px, -20px) scale(1.05);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.95);
            }
        }
        
        /* Premium Glassmorphism Card */
        .auth-card {
            background: linear-gradient(145deg, 
                rgba(255, 255, 255, 0.09) 0%, 
                rgba(255, 255, 255, 0.05) 50%,
                rgba(255, 255, 255, 0.07) 100%
            );
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
            border-radius: 28px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.4),
                0 30px 60px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1),
                inset 0 -1px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: cardEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        /* Animated gradient border */
        .auth-card::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(
                60deg,
                #00a651,
                #0066b3,
                #00a651,
                #0066b3
            );
            background-size: 300% 300%;
            border-radius: 28px;
            z-index: -1;
            animation: gradientRotate 8s ease infinite;
            opacity: 0.6;
        }
        
        @keyframes gradientRotate {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes cardEntrance {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Shimmer Effect on Card */
        .auth-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.03) 50%,
                transparent 70%
            );
            animation: shimmer 6s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .auth-card .bg-white {
            background: rgba(255, 255, 255, 0.98) !important;
            border-radius: 24px !important;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }
        
        /* Enhanced Floating Elements with 3D Effect */
        .floating-dot {
            position: absolute;
            border-radius: 50%;
            opacity: 0.18;
            animation: float3D 20s ease-in-out infinite;
            will-change: transform;
            filter: blur(40px);
        }
        
        .delay-0 { animation-delay: 0s; }
        .delay-3 { animation-delay: -3s; }
        .delay-7 { animation-delay: -7s; }
        .delay-14 { animation-delay: -14s; }
        
        @keyframes float3D {
            0%, 100% { 
                transform: translate(0, 0) scale(1) rotate(0deg);
            }
            25% { 
                transform: translate(30px, -40px) scale(1.1) rotate(90deg);
            }
            50% { 
                transform: translate(-20px, 20px) scale(0.9) rotate(180deg);
            }
            75% { 
                transform: translate(40px, 30px) scale(1.05) rotate(270deg);
            }
        }
        
        /* Particle System */
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            pointer-events: none;
            animation: particleFloat 15s linear infinite;
        }
        
        .particle-1 { width: 3px; height: 3px; left: 10%; animation-delay: 0s; }
        .particle-2 { width: 2px; height: 2px; left: 20%; animation-delay: 2s; }
        .particle-3 { width: 4px; height: 4px; left: 30%; animation-delay: 4s; }
        .particle-4 { width: 2px; height: 2px; left: 40%; animation-delay: 6s; }
        .particle-5 { width: 3px; height: 3px; left: 50%; animation-delay: 8s; }
        .particle-6 { width: 2px; height: 2px; left: 60%; animation-delay: 10s; }
        .particle-7 { width: 4px; height: 4px; left: 70%; animation-delay: 12s; }
        .particle-8 { width: 3px; height: 3px; left: 80%; animation-delay: 14s; }
        .particle-9 { width: 2px; height: 2px; left: 90%; animation-delay: 16s; }
        
        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px) scale(1);
                opacity: 0;
            }
        }
        
        /* Logo Animation */
        .logo-container {
            animation: logoFloat 3s ease-in-out infinite;
            filter: drop-shadow(0 8px 30px rgba(0, 166, 81, 0.4));
            transition: all 0.3s ease;
        }
        
        .logo-container:hover {
            filter: drop-shadow(0 12px 40px rgba(0, 166, 81, 0.6));
            transform: scale(1.05);
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(2deg); }
        }
        
        /* Perfect Responsive Auth Layout */
        @media (max-width: 640px) {
            body::before,
            body::after {
                opacity: 0.4;
                width: 100%;
                height: 100%;
            }
            
            .floating-dot {
                width: 60px !important;
                height: 60px !important;
                filter: blur(30px);
            }
            
            .auth-card {
                border-radius: 16px;
                padding: 0.5rem !important;
                margin: 0 0.5rem;
            }
            
            .auth-card .bg-white {
                padding: 1.25rem !important;
                border-radius: 14px !important;
            }
            
            .particle {
                display: none;
            }
            
            .logo-container img {
                height: 4rem !important;
            }
        }
        
        @media (min-width: 641px) and (max-width: 768px) {
            .auth-card {
                padding: 1rem !important;
            }
            
            .auth-card .bg-white {
                padding: 1.75rem !important;
                border-radius: 18px !important;
            }
            
            .floating-dot {
                width: 100px !important;
                height: 100px !important;
            }
            
            .logo-container img {
                height: 5rem !important;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .auth-card .bg-white {
                padding: 2.25rem !important;
            }
        }
        
        /* Ultra-wide screens */
        @media (min-width: 1920px) {
            .auth-card {
                max-width: 32rem;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .auth-card:hover {
                transform: none;
            }
            
            .floating-dot {
                animation-duration: 30s;
            }
        }
        
        /* Landscape mobile optimization */
        @media (max-width: 640px) and (orientation: landscape) {
            .logo-container {
                display: none;
            }
            
            .relative.z-10 {
                padding: 1rem;
            }
            
            .auth-card .bg-white {
                padding: 1rem !important;
            }
        }

    </style>
</head>
<body class="min-h-screen">
    <!-- Enhanced Decorative floating elements with 3D effect -->
    <div class="floating-dot w-80 h-80 top-20 -left-32 bg-gradient-to-br from-green-400 to-emerald-600 delay-0" aria-hidden="true"></div>
    <div class="floating-dot w-64 h-64 top-40 right-10 bg-gradient-to-br from-blue-400 to-cyan-600 delay-7" aria-hidden="true"></div>
    <div class="floating-dot w-48 h-48 bottom-20 left-1/4 bg-gradient-to-br from-blue-300 to-blue-500 delay-14" aria-hidden="true"></div>
    <div class="floating-dot w-56 h-56 bottom-40 right-1/3 bg-gradient-to-br from-emerald-400 to-green-600 delay-3" aria-hidden="true"></div>
    
    <!-- Particle System -->
    <div class="particle particle-1"></div>
    <div class="particle particle-2"></div>
    <div class="particle particle-3"></div>
    <div class="particle particle-4"></div>
    <div class="particle particle-5"></div>
    <div class="particle particle-6"></div>
    <div class="particle particle-7"></div>
    <div class="particle particle-8"></div>
    <div class="particle particle-9"></div>

    <div class="relative z-10 flex flex-col items-center justify-center min-h-screen p-4">
        <!-- IBC Logo above content with enhanced animation -->
        <div class="mb-10 logo-container">
            <img src="<?php echo asset('assets/img/ibc_logo_original.webp'); ?>" alt="IBC Logo" class="mx-auto h-24 w-auto" style="filter: brightness(1.15) drop-shadow(0 8px 30px rgba(0, 166, 81, 0.5)) drop-shadow(0 4px 20px rgba(0, 166, 81, 0.3));">
        </div>
        
        <!-- Content area wrapped in auth-card -->
        <div class="auth-card p-1.5 w-full max-w-md">
            <?php echo $content ?? ''; ?>
        </div>
        
        <!-- Footer text with proper contrast -->
        <div class="mt-10 text-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">
                © <?php echo date('Y'); ?> IBC · Intranet Platform
            </p>
            <div class="mt-2 flex justify-center space-x-1">
                <div class="w-1 h-1 rounded-full bg-gray-400/30 dark:bg-gray-500/30"></div>
                <div class="w-1 h-1 rounded-full bg-gray-400/40 dark:bg-gray-500/40"></div>
                <div class="w-1 h-1 rounded-full bg-gray-400/30 dark:bg-gray-500/30"></div>
            </div>
        </div>
    </div>
</body>
</html>
