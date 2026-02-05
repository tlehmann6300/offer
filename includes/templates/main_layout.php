<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Auth.php';
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
        .sidebar {
            background: linear-gradient(180deg, var(--ibc-blue) 0%, var(--ibc-green) 100%);
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--ibc-green) 0%, var(--ibc-blue) 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow-green);
        }
        
        /* Mobile view improvements */
        @media (max-width: 768px) {
            .card {
                padding: 1rem !important;
            }
            
            /* Fix text overflow in cards */
            .card p, .card div {
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            /* Better spacing on mobile - add top padding for better visibility */
            main {
                padding: 1rem !important;
                padding-top: var(--mobile-header-offset) !important; /* Ensure content doesn't hide under toggle button */
            }
            
            /* Prevent horizontal overflow */
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            /* Better form spacing on mobile */
            form input, form select, form textarea {
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            /* Stack buttons vertically on mobile */
            .flex.space-x-4 {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .flex.space-x-4 > * {
                width: 100%;
            }
        }
        
        /* Ensure long text doesn't overflow */
        .text-sm, .text-xs {
            overflow-wrap: break-word;
            word-break: break-word;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-30 hidden transition-opacity duration-300"></div>

    <!-- Mobile Menu Toggle -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-btn" class="bg-white p-3 rounded-lg shadow-lg">
            <i class="fas fa-bars text-gray-700"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 top-0 h-screen w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 text-white shadow-2xl flex flex-col">
        <?php $currentUser = Auth::user(); ?>
        <div class="p-6 flex-1 overflow-y-auto">
            <!-- IBC Logo in Navbar -->
            <div class="mb-8">
                <img src="<?php echo asset('assets/img/ibc_logo_original_navbar.webp'); ?>" alt="IBC Logo" class="w-full h-auto">
            </div>
            
            <nav class="space-y-2">
                <a href="<?php echo asset('pages/dashboard/index.php'); ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo asset('pages/inventory/index.php'); ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-boxes w-5"></i>
                    <span>Inventar</span>
                </a>
                <a href="<?php echo asset('pages/events/index.php'); ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-calendar-check w-5"></i>
                    <span>Events</span>
                </a>
                <a href="<?php echo asset('pages/projects/index.php'); ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-briefcase w-5"></i>
                    <span>Projekte</span>
                </a>
                
                <!-- Verwaltung Dropdown -->
                <div class="pt-2">
                    <button type="button"
                            onclick="toggleVerwaltungDropdown()" 
                            id="verwaltung-button"
                            class="w-full flex items-center justify-between space-x-3 p-3 rounded-lg hover:bg-white/10 transition"
                            aria-expanded="false"
                            aria-controls="verwaltung-dropdown">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-cog w-5"></i>
                            <span>Verwaltung</span>
                        </div>
                        <i id="verwaltung-arrow" class="fas fa-chevron-down text-sm transition-transform duration-300"></i>
                    </button>
                    <nav id="verwaltung-dropdown" 
                         class="hidden bg-black/40 rounded-lg mt-2 ml-2 border-l-2 border-gray-600 overflow-hidden"
                         aria-labelledby="verwaltung-button">
                        <a href="<?php echo asset('pages/inventory/my_rentals.php'); ?>" class="flex items-center pr-4 py-2 text-sm text-gray-300 pl-4 hover:bg-gray-700 hover:text-white transition-all">
                            <i class="fas fa-clipboard-list w-5 mr-2"></i>
                            <span>Meine Ausleihen</span>
                        </a>
                        <?php if (Auth::hasPermission('manager')): ?>
                        <a href="<?php echo asset('pages/events/manage.php'); ?>" class="flex items-center pr-4 py-2 text-sm text-gray-300 pl-4 hover:bg-gray-700 hover:text-white transition-all">
                            <i class="fas fa-calendar-alt w-5 mr-2"></i>
                            <span>Event-Verwaltung</span>
                        </a>
                        <a href="<?php echo asset('pages/projects/manage.php'); ?>" class="flex items-center pr-4 py-2 text-sm text-gray-300 pl-4 hover:bg-gray-700 hover:text-white transition-all">
                            <i class="fas fa-tasks w-5 mr-2"></i>
                            <span>Projekt-Verwaltung</span>
                        </a>
                        <a href="<?php echo asset('pages/inventory/manage.php'); ?>" class="flex items-center pr-4 py-2 text-sm text-gray-300 pl-4 hover:bg-gray-700 hover:text-white transition-all">
                            <i class="fas fa-cogs w-5 mr-2"></i>
                            <span>Inventar-Verwaltung</span>
                        </a>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'board'])): ?>
                        <a href="<?php echo asset('pages/admin/users.php'); ?>" class="flex items-center pr-4 py-2 text-sm text-gray-300 pl-4 hover:bg-gray-700 hover:text-white transition-all">
                            <i class="fas fa-users w-5 mr-2"></i>
                            <span>Benutzerverwaltung</span>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'board'])): ?>
                <!-- Admin Section (Audit-Logs kept separate) -->
                <div class="pt-2">
                    <a href="<?php echo asset('pages/admin/audit.php'); ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                        <i class="fas fa-clipboard-list w-5"></i>
                        <span>Audit-Logs</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <a href="<?php echo asset('pages/auth/profile.php'); ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-user w-5"></i>
                    <span>Profil</span>
                </a>
                
            </nav>
        </div>

        <div class="mt-auto pt-6 border-t border-gray-700">
            <div class="flex items-center px-2 mb-4">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-3">
                    <?php 
                    $firstname = !empty($currentUser['firstname']) ? $currentUser['firstname'] : '';
                    $lastname = !empty($currentUser['lastname']) ? $currentUser['lastname'] : '';
                    
                    if (!empty($firstname) && !empty($lastname)) {
                        echo strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
                    } elseif (!empty($firstname)) {
                        echo strtoupper(substr($firstname, 0, 1));
                    } elseif (!empty($lastname)) {
                        echo strtoupper(substr($lastname, 0, 1));
                    } elseif (!empty($currentUser['email'])) {
                        echo strtoupper(substr($currentUser['email'], 0, 1));
                    } else {
                        echo 'U';
                    }
                    ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate" title="<?php echo htmlspecialchars($currentUser['email']); ?>">
                        <?php 
                        $fullname = trim($currentUser['firstname'] . ' ' . $currentUser['lastname']);
                        echo htmlspecialchars(!empty($fullname) ? $fullname : $currentUser['email']); 
                        ?>
                    </p>
                    <p class="text-xs text-gray-400 truncate">
                        <?php echo htmlspecialchars(ucfirst($currentUser['role'])); ?>
                    </p>
                </div>
            </div>
            <a href="<?php echo asset('pages/auth/logout.php'); ?>" 
               class="flex items-center justify-center w-full px-4 py-2 text-sm font-bold text-white bg-red-600/80 hover:bg-red-600 rounded-lg transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i> Abmelden
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen p-6 lg:p-8">
        <?php echo $content ?? ''; ?>
    </main>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }
        
        mobileMenuBtn?.addEventListener('click', () => {
            toggleSidebar();
        });

        // Close sidebar when clicking overlay
        sidebarOverlay?.addEventListener('click', () => {
            toggleSidebar();
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && 
                !sidebar.contains(e.target) && 
                !mobileMenuBtn.contains(e.target) &&
                !sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            }
        });

        // Verwaltung dropdown toggle
        function toggleVerwaltungDropdown() {
            const dropdown = document.getElementById('verwaltung-dropdown');
            const arrow = document.getElementById('verwaltung-arrow');
            const button = document.getElementById('verwaltung-button');
            const isHidden = dropdown.classList.contains('hidden');
            
            dropdown.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
            
            // Update aria-expanded for accessibility
            if (button) {
                button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            }
        }
    </script>
</body>
</html>
