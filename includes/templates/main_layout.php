<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../handlers/AuthHandler.php';
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
                        'sans': ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'system-ui', 'sans-serif'],
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
            background: #111827; /* gray-900 for dark theme */
        }
        
        /* Custom scrollbar styling for sidebar */
        .sidebar::-webkit-scrollbar,
        .sidebar-scroll::-webkit-scrollbar {
            width: 8px;
        }
        
        .sidebar::-webkit-scrollbar-track,
        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        .sidebar::-webkit-scrollbar-thumb,
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover,
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Firefox scrollbar styling */
        .sidebar,
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) rgba(0, 0, 0, 0.2);
        }
        
        /* Light mode sidebar styling */
        body:not(.dark-mode) .sidebar {
            background: linear-gradient(135deg, #0066b3 0%, #004f8c 100%);
            box-shadow: 4px 0 20px rgba(0, 102, 179, 0.15);
        }
        
        /* Light mode sidebar text colors */
        body:not(.dark-mode) .sidebar a {
            color: rgba(255, 255, 255, 0.95);
            transition: all 0.2s ease;
        }
        
        body:not(.dark-mode) .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }
        
        body:not(.dark-mode) .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            border-right: 3px solid #00a651;
        }
        
        body:not(.dark-mode) .sidebar .bg-gray-800 {
            background: rgba(255, 255, 255, 0.15) !important;
        }
        
        body:not(.dark-mode) .sidebar .text-gray-300 {
            color: rgba(255, 255, 255, 0.95) !important;
        }
        
        body:not(.dark-mode) .sidebar button {
            color: rgba(255, 255, 255, 0.95);
        }
        
        /* Dark mode sidebar styling */
        body.dark-mode .sidebar {
            background: #1a1a1a;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Dark mode sidebar - ensure all text is white */
        body.dark-mode .sidebar a {
            color: rgba(255, 255, 255, 0.95) !important;
            transition: all 0.2s ease;
        }
        
        body.dark-mode .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
            transform: translateX(4px);
        }
        
        body.dark-mode .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            border-right: 3px solid #00a651;
            color: white !important;
        }
        
        body.dark-mode .sidebar .text-gray-300,
        body.dark-mode .sidebar .text-gray-200 {
            color: rgba(255, 255, 255, 0.95) !important;
        }
        
        body.dark-mode .sidebar button {
            color: rgba(255, 255, 255, 0.95) !important;
        }
        
        body.dark-mode .sidebar p,
        body.dark-mode .sidebar span,
        body.dark-mode .sidebar div {
            color: rgba(255, 255, 255, 0.95);
        }
        
        /* Light mode mobile menu button styling */
        body:not(.dark-mode) #mobile-menu-btn {
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body:not(.dark-mode) #mobile-menu-btn i {
            color: var(--ibc-blue);
        }
        
        body.dark-mode #mobile-menu-btn {
            background: var(--bg-secondary);
        }
        
        body.dark-mode #mobile-menu-btn i {
            color: var(--text-primary);
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .card:hover {
            box-shadow: 0 12px 32px rgba(0, 102, 179, 0.15);
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #00a651 0%, #0066b3 100%);
            color: white;
            padding: 0.625rem 1.75rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 166, 81, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 166, 81, 0.35);
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
            
            /* Improve heading sizes on mobile */
            main h1 {
                font-size: 1.75rem;
            }
            
            main h2 {
                font-size: 1.5rem;
            }
            
            main h3 {
                font-size: 1.25rem;
            }
            
            /* Better image scaling on mobile */
            img {
                max-width: 100%;
                height: auto;
            }
            
            /* Ensure grids stack on mobile - for auto-responsive grids */
            .grid:not(.grid-no-stack) {
                grid-template-columns: 1fr;
            }
        }
        
        /* Tablet view improvements */
        @media (min-width: 769px) and (max-width: 1024px) {
            main {
                padding: 1.5rem !important;
            }
            
            /* 2-column grid on tablets - for auto-responsive grids */
            .grid:not(.grid-no-stack) {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Ensure long text doesn't overflow */
        .text-sm, .text-xs {
            overflow-wrap: break-word;
            word-break: break-word;
        }
    </style>
</head>
<body class="bg-gray-50" data-user-theme="<?php echo htmlspecialchars($currentUser['theme_preference'] ?? 'auto'); ?>">
    <script>
        // Apply theme immediately to prevent flash of unstyled content (FOUC)
        (function() {
            const userTheme = document.body.getAttribute('data-user-theme') || 'auto';
            const savedTheme = localStorage.getItem('theme') || userTheme;
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
            } else if (savedTheme === 'light') {
                document.body.classList.remove('dark-mode');
            } else { // auto
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.body.classList.add('dark-mode');
                }
            }
        })();
    </script>
    <!-- Mobile Menu Overlay -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-30 hidden transition-opacity duration-300"></div>

    <!-- Mobile Menu Toggle -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-btn" class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg">
            <i class="fas fa-bars text-gray-700 dark:text-gray-100"></i>
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
        <div class="p-6 flex-1 overflow-y-auto sidebar-scroll">
            <!-- IBC Logo in Navbar -->
            <div class="mb-8">
                <img src="<?php echo asset('assets/img/ibc_logo_original_navbar.webp'); ?>" alt="IBC Logo" class="w-full h-auto">
            </div>
            
            <nav>
                <!-- Dashboard (All) -->
                <a href="<?php echo asset('pages/dashboard/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/dashboard/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-home w-5 mr-3"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Mitglieder (Board, Head, Member, Candidate) -->
                <?php 
                $isBoardRole = Auth::isBoardMember();
                $canSeeMitglieder = isset($_SESSION['user_role']) && (
                    $isBoardRole || 
                    in_array($_SESSION['user_role'], ['head', 'member', 'candidate'])
                );
                ?>
                <?php if ($canSeeMitglieder): ?>
                <a href="<?php echo asset('pages/members/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/members/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-users w-5 mr-3"></i>
                    <span>Mitglieder</span>
                </a>
                <?php endif; ?>

                <!-- Alumni (All) -->
                <a href="<?php echo asset('pages/alumni/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/alumni/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-user-graduate w-5 mr-3"></i>
                    <span>Alumni</span>
                </a>

                <!-- Projekte (All) -->
                <a href="<?php echo asset('pages/projects/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/projects/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-folder w-5 mr-3"></i>
                    <span>Projekte</span>
                </a>

                <!-- Events (All) -->
                <a href="<?php echo asset('pages/events/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/events/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-calendar w-5 mr-3"></i>
                    <span>Events</span>
                </a>

                <!-- Helfersystem (All) - Indented -->
                <a href="<?php echo asset('pages/events/helpers.php'); ?>" 
                   style="padding-left: 2.5rem;"
                   class="flex items-center pr-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/events/helpers.php') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-hands-helping w-5 mr-3"></i>
                    <span>Helfersystem</span>
                </a>

                <!-- Inventar (All) -->
                <a href="<?php echo asset('pages/inventory/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo (isActivePath('/inventory/') && !isActivePath('/my_rentals.php')) ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-box w-5 mr-3"></i>
                    <span>Inventar</span>
                </a>

                <!-- Blog (All) -->
                <a href="<?php echo asset('pages/blog/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/blog/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-newspaper w-5 mr-3"></i>
                    <span>Blog</span>
                </a>

                <!-- Rechnungen (Board roles, Alumni, Alumni-Board, Honorary Member) -->
                <?php 
                $canSeeInvoices = isset($_SESSION['user_role']) && (
                    $isBoardRole ||
                    in_array($_SESSION['user_role'], ['alumni', 'alumni_board', 'honorary_member'])
                );
                ?>
                <?php if ($canSeeInvoices): ?>
                <a href="<?php echo asset('pages/invoices/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/invoices/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar w-5 mr-3"></i>
                    <span>Rechnungen</span>
                </a>
                <?php endif; ?>

                <!-- Ideenbox (Members, Candidates, Head, Board) -->
                <?php 
                $canSeeIdeas = isset($_SESSION['user_role']) && (
                    $isBoardRole ||
                    in_array($_SESSION['user_role'], ['member', 'candidate', 'head'])
                );
                ?>
                <?php if ($canSeeIdeas): ?>
                <a href="<?php echo asset('pages/ideas/index.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/ideas/') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-lightbulb w-5 mr-3"></i>
                    <span>Ideenbox</span>
                </a>
                <?php endif; ?>

                <!-- Schulungsanfrage (Alumni, Alumni-Board) -->
                <?php 
                $canSeeTrainingRequests = isset($_SESSION['user_role']) && (
                    in_array($_SESSION['user_role'], ['alumni', 'alumni_board'])
                );
                ?>
                <?php if ($canSeeTrainingRequests): ?>
                <a href="<?php echo asset('pages/alumni/requests.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/alumni/requests.php') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher w-5 mr-3"></i>
                    <span>Schulungsanfrage</span>
                </a>
                <?php endif; ?>

                <!-- Benutzer (Board roles ONLY) -->
                <?php if ($isBoardRole): ?>
                <a href="<?php echo asset('pages/admin/users.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/admin/users.php') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-users-cog w-5 mr-3"></i>
                    <span>Benutzer</span>
                </a>
                <?php endif; ?>

                <!-- Einstellungen (Board roles ONLY) -->
                <?php if ($isBoardRole): ?>
                <a href="<?php echo asset('pages/auth/settings.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/auth/settings.php') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-cog w-5 mr-3"></i>
                    <span>Einstellungen</span>
                </a>
                <?php endif; ?>

                <!-- Statistiken (Board roles ONLY) -->
                <?php if ($isBoardRole): ?>
                <a href="<?php echo asset('pages/admin/stats.php'); ?>" 
                   class="flex items-center px-6 py-2 hover:bg-gray-800 hover:text-white transition-colors duration-200 <?php echo isActivePath('/admin/stats.php') ? 'bg-gray-800 text-white border-r-4 border-purple-500' : ''; ?>">
                    <i class="fas fa-chart-bar w-5 mr-3"></i>
                    <span>Statistiken</span>
                </a>
                <?php endif; ?>
                
            </nav>
        </div>

        <!-- User Profile Section -->
        <div class='mt-auto border-t border-white/20 pt-8 pb-6 px-5 bg-gradient-to-b from-black/30 to-black/40'>
            <?php 
            $currentUser = Auth::user();
            
            // Initialize default values
            $firstname = '';
            $lastname = '';
            $email = '';
            $role = 'User';
            
            // Only try to get profile if user is logged in
            if ($currentUser && isset($currentUser['id'])) {
                // Try to get name from alumni_profiles table first
                require_once __DIR__ . '/../models/Alumni.php';
                $profile = Alumni::getProfileByUserId($currentUser['id']);
                
                if ($profile && !empty($profile['first_name'])) {
                    $firstname = $profile['first_name'];
                    $lastname = $profile['last_name'] ?? '';
                } elseif (!empty($currentUser['firstname'])) {
                    $firstname = $currentUser['firstname'];
                    $lastname = $currentUser['lastname'] ?? '';
                }
                
                $email = $currentUser['email'] ?? '';
                $role = $currentUser['role'] ?? 'User';
            }
            
            // Generate greeting
            $greeting = 'Guten Tag';
            if (!empty($firstname) && !empty($lastname)) {
                $greetingName = $firstname . ' ' . $lastname;
            } elseif (!empty($firstname)) {
                $greetingName = $firstname;
            } elseif (!empty($lastname)) {
                $greetingName = $lastname;
            } else {
                $greetingName = $email;
            }
            
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
            ?>
            <!-- User Info -->
            <div class='flex items-center gap-3 mb-5'>
                <div class='w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-base shadow-lg border-2 border-white/20 shrink-0'>
                    <?php echo $initials; ?>
                </div>
                <div class='flex-1 min-w-0'>
                    <?php if (!empty($firstname) || !empty($lastname)): ?>
                    <p class='text-sm font-semibold text-white dark:text-gray-100 truncate leading-snug mb-0.5' title='<?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>'>
                        <?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>
                    </p>
                    <?php endif; ?>
                    <p class='text-[11px] text-white/80 truncate leading-snug mb-1' title='<?php echo htmlspecialchars($email); ?>'>
                        <?php echo htmlspecialchars($email); ?>
                    </p>
                    <span class='inline-block px-2.5 py-0.5 rounded-full text-[10px] font-semibold tracking-wide uppercase bg-white/10 text-white dark:text-gray-100 border border-white/20'>
                        <?php 
                        // Translate role to German
                        $roleTranslations = [
                            'candidate' => 'Anwärter',
                            'member' => 'Mitglied',
                            'head' => 'Ressortleiter',
                            'board' => 'Vorstand',
                            'vorstand_intern' => 'Vorstand Intern',
                            'vorstand_extern' => 'Vorstand Extern',
                            'vorstand_finanzen_recht' => 'Vorstand Finanzen & Recht',
                            'alumni' => 'Alumni',
                            'alumni_board' => 'Alumni Vorstand',
                            'honorary_member' => 'Ehrenmitglied'
                        ];
                        $roleDisplay = $roleTranslations[$role] ?? ucfirst($role);
                        echo htmlspecialchars($roleDisplay); 
                        ?>
                    </span>
                </div>
            </div>
            
            <!-- Bottom Section Links -->
            <div class='mb-3 space-y-2'>
                <!-- Profilangaben -->
                <a href='<?php echo asset('pages/auth/profile.php'); ?>' 
                   class='flex items-center justify-center w-full px-4 py-2 text-xs font-medium text-white/90 dark:text-gray-100 border border-white/30 rounded-lg hover:bg-white/10 hover:text-white hover:border-white/50 transition-all duration-200 group backdrop-blur-sm <?php echo isActivePath('/auth/profile.php') ? 'bg-white/10' : ''; ?>'>
                    <i class='fas fa-user text-xs mr-2'></i> 
                    <span>Mein Profil</span>
                </a>
                
                <!-- Einstellungen -->
                <a href='<?php echo asset('pages/auth/settings.php'); ?>' 
                   class='flex items-center justify-center w-full px-4 py-2 text-xs font-medium text-white/90 dark:text-gray-100 border border-white/30 rounded-lg hover:bg-white/10 hover:text-white hover:border-white/50 transition-all duration-200 group backdrop-blur-sm <?php echo isActivePath('/auth/settings.php') ? 'bg-white/10' : ''; ?>'>
                    <i class='fas fa-cog text-xs mr-2'></i> 
                    <span>Einstellungen</span>
                </a>
            </div>
            
            <!-- Logout Button -->
            <a href='<?php echo asset('pages/auth/logout.php'); ?>' 
               class='flex items-center justify-center w-full px-4 py-2 mb-3 text-xs font-medium text-white/90 dark:text-gray-100 border border-white/30 rounded-lg hover:bg-white/10 hover:text-white hover:border-white/50 transition-all duration-200 group backdrop-blur-sm'>
                <i class='fas fa-sign-out-alt text-xs mr-2 group-hover:translate-x-0.5 transition-transform'></i> 
                <span>Abmelden</span>
            </a>
            
            <!-- Dark/Light Mode Toggle -->
            <button id="theme-toggle" class='flex items-center justify-center w-full px-4 py-2 text-xs font-medium text-white/90 border border-white/30 rounded-lg hover:bg-white/10 hover:text-white hover:border-white/50 transition-all duration-200 group backdrop-blur-sm'>
                <i id="theme-icon" class='fas fa-moon text-xs mr-2'></i>
                <span id="theme-text">Dunkelmodus</span>
            </button>
            
            <!-- Live Clock -->
            <div class='mt-4 pt-4 border-t border-white/20 text-center'>
                <div id="live-clock" class='text-xs text-white/80 font-mono'>
                    <!-- JavaScript will update this -->
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen p-6 lg:p-8">
        <?php if (isset($_SESSION['show_2fa_nudge']) && $_SESSION['show_2fa_nudge']): ?>
        <!-- 2FA Nudge Modal -->
        <div id="tfa-nudge-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-green-600 px-6 py-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-shield-alt text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Sicherheitshinweis</h3>
                    </div>
                </div>
                
                <!-- Modal Body -->
                <div class="px-6 py-6">
                    <p class="text-gray-700 dark:text-gray-300 text-lg mb-2 font-semibold">
                        Erhöhe deine Sicherheit!
                    </p>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Aktiviere jetzt die 2-Faktor-Authentifizierung für zusätzlichen Schutz deines Kontos.
                    </p>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-1 mr-3"></i>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Die 2-Faktor-Authentifizierung macht dein Konto deutlich sicherer, indem bei der Anmeldung ein zusätzlicher Code erforderlich ist.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex flex-col sm:flex-row gap-3">
                    <a href="<?php echo asset('pages/auth/profile.php'); ?>" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-green-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-green-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Jetzt einrichten
                    </a>
                    <button onclick="dismissTfaNudge()" class="flex-1 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-semibold hover:bg-gray-400 dark:hover:bg-gray-500 transition-all duration-300">
                        Später
                    </button>
                </div>
            </div>
        </div>

        <script>
        // Dismiss modal
        function dismissTfaNudge() {
            document.getElementById('tfa-nudge-modal').style.display = 'none';
        }
        </script>
        <?php 
            unset($_SESSION['show_2fa_nudge']);
        endif; 
        ?>
        
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
        
        // Dark/Light Mode Toggle
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');
        
        // Get user's saved theme preference from database (via data attribute)
        const userThemePreference = document.body.getAttribute('data-user-theme') || 'auto';
        
        // Load theme preference (localStorage overrides database preference)
        let currentTheme = localStorage.getItem('theme') || userThemePreference;
        
        // Apply theme based on preference
        function applyTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                themeText.textContent = 'Hellmodus';
            } else if (theme === 'light') {
                document.body.classList.remove('dark-mode');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                themeText.textContent = 'Dunkelmodus';
            } else { // auto
                // Check system preference
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.body.classList.add('dark-mode');
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                    themeText.textContent = 'Hellmodus';
                } else {
                    document.body.classList.remove('dark-mode');
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                    themeText.textContent = 'Dunkelmodus';
                }
            }
        }
        
        // Apply initial theme
        applyTheme(currentTheme);
        
        // Toggle theme on button click
        themeToggle?.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                themeText.textContent = 'Hellmodus';
            } else {
                localStorage.setItem('theme', 'light');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                themeText.textContent = 'Dunkelmodus';
            }
        });
        
        // Live Clock - Updates every second
        function updateLiveClock() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const dateTimeString = `${day}.${month}.${year} ${hours}:${minutes}:${seconds}`;
            const clockElement = document.getElementById('live-clock');
            if (clockElement) {
                clockElement.textContent = dateTimeString;
            }
        }
        
        // Update immediately and then every second
        updateLiveClock();
        setInterval(updateLiveClock, 1000);
    </script>
</body>
</html>
<!-- ✅ Sidebar updated: Invoices visible for Board, Head & Alumni Board -->
