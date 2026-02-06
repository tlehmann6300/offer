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
            background: #111827; /* gray-900 */
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
        <?php 
        // Helper function to determine if a path is active
        function isActivePath($path) {
            $currentUri = $_SERVER['REQUEST_URI'];
            return strpos($currentUri, $path) !== false;
        }
        ?>
        <div class="p-6 flex-1 overflow-y-auto">
            <!-- IBC Logo in Navbar -->
            <div class="mb-8">
                <img src="<?php echo asset('assets/img/ibc_logo_original_navbar.webp'); ?>" alt="IBC Logo" class="w-full h-auto">
            </div>
            
            <nav>
                <!-- DURCHSTARTEN Section -->
                <div class="px-6 mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Durchstarten
                </div>
                <a href="<?php echo asset('pages/dashboard/index.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/dashboard/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-home w-5 mr-3"></i>
                    <span>Dashboard</span>
                </a>

                <!-- ORGANISATION Section -->
                <div class="px-6 mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Organisation
                </div>
                <a href="<?php echo asset('pages/projects/index.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/projects/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-folder w-5 mr-3"></i>
                    <span>Projekte</span>
                </a>
                <a href="<?php echo asset('pages/events/index.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/events/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-calendar w-5 mr-3"></i>
                    <span>Events</span>
                </a>
                <a href="<?php echo asset('pages/inventory/index.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo (isActivePath('/inventory/') && !isActivePath('/my_rentals.php')) ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-box w-5 mr-3"></i>
                    <span>Inventar</span>
                </a>

                <!-- NETZWERK Section -->
                <div class="px-6 mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Netzwerk
                </div>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'board', 'head', 'member', 'candidate'])): ?>
                <a href="<?php echo asset('pages/members/index.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/members/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-users w-5 mr-3"></i>
                    <span>Mitglieder</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo asset('pages/alumni/index.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/alumni/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-graduation-cap w-5 mr-3"></i>
                    <span>Alumni</span>
                </a>
                <a href="<?php echo asset('pages/blog/index.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/blog/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-newspaper w-5 mr-3"></i>
                    <span>News / Blog</span>
                </a>

                <!-- VERWALTUNG Section (Only visible for admin/board) -->
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'board'])): ?>
                <div class="px-6 mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Verwaltung
                </div>
                <a href="<?php echo asset('pages/admin/users.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/admin/users.php') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-user-cog w-5 mr-3"></i>
                    <span>Benutzer</span>
                </a>
                <a href="<?php echo asset('pages/admin/audit.php'); ?>" 
                   class="flex items-center px-6 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/admin/audit.php') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-shield-alt w-5 mr-3"></i>
                    <span>Einstellungen / Audit</span>
                </a>
                <?php endif; ?>
                
            </nav>
        </div>

        <!-- User Profile Section -->
        <div class='mt-auto border-t border-white/20 pt-8 pb-6 px-5 bg-gradient-to-b from-black/30 to-black/40'>
            <?php 
            $currentUser = Auth::user();
            $firstname = !empty($currentUser['firstname']) ? $currentUser['firstname'] : '';
            $lastname = !empty($currentUser['lastname']) ? $currentUser['lastname'] : '';
            $email = $currentUser['email'] ?? '';
            $role = $currentUser['role'] ?? 'User';
            
            // Generate initials with proper fallbacks
            if (!empty($firstname) && !empty($lastname)) {
                $initials = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
            } elseif (!empty($firstname)) {
                $initials = strtoupper(substr($firstname, 0, 1));
            } elseif (!empty($lastname)) {
                $initials = strtoupper(substr($lastname, 0, 1));
            } elseif (!empty($email)) {
                $initials = strtoupper(substr($email, 0, 1));
            } else {
                $initials = 'U';
            }
            
            // Generate display name
            $fullname = trim($firstname . ' ' . $lastname);
            $displayName = !empty($fullname) ? $fullname : $email;
            ?>
            <!-- User Info -->
            <div class='flex items-center gap-3 mb-5'>
                <div class='w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-base shadow-lg border-2 border-white/20 shrink-0'>
                    <?php echo $initials; ?>
                </div>
                <div class='flex-1 min-w-0'>
                    <p class='text-sm font-semibold text-white truncate leading-snug mb-0.5' title='<?php echo htmlspecialchars($displayName); ?>'>
                        <?php echo htmlspecialchars($displayName); ?>
                    </p>
                    <?php if (!empty($email)): ?>
                    <p class='text-xs text-gray-300 truncate leading-relaxed' title='<?php echo htmlspecialchars($email); ?>'>
                        <?php echo htmlspecialchars($email); ?>
                    </p>
                    <?php endif; ?>
                    <span class='inline-block mt-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold tracking-wide uppercase bg-white/10 text-white border border-white/20'>
                        <?php 
                        // Translate candidate role to German
                        $roleDisplay = $role === 'candidate' ? 'Anwärter' : ucfirst($role);
                        echo htmlspecialchars($roleDisplay); 
                        ?>
                    </span>
                </div>
            </div>
            <!-- Logout Button -->
            <a href='<?php echo asset('pages/auth/logout.php'); ?>' 
               class='flex items-center justify-center w-full px-4 py-2 text-xs font-medium text-white/90 border border-white/30 rounded-lg hover:bg-white/10 hover:text-white hover:border-white/50 transition-all duration-200 group backdrop-blur-sm'>
                <i class='fas fa-sign-out-alt text-xs mr-2 group-hover:translate-x-0.5 transition-transform'></i> 
                <span>Abmelden</span>
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
    </script>
</body>
</html>
