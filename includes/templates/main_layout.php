<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'IBC Intranet'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }
        .sidebar {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Toggle -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-btn" class="bg-white p-3 rounded-lg shadow-lg">
            <i class="fas fa-bars text-gray-700"></i>
        </button>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 top-0 h-screen w-64 transform -translate-x-full lg:translate-x-0 transition-transform z-40 text-white">
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-8">
                <i class="fas fa-building mr-2"></i>
                IBC Intranet
            </h1>
            
            <nav class="space-y-2">
                <a href="../dashboard/index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../inventory/index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-boxes w-5"></i>
                    <span>Inventar</span>
                </a>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'board'])): ?>
                <a href="../admin/users.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-users w-5"></i>
                    <span>Benutzerverwaltung</span>
                </a>
                <a href="../admin/audit.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-clipboard-list w-5"></i>
                    <span>Audit-Logs</span>
                </a>
                <?php endif; ?>
                <a href="../auth/profile.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
                    <i class="fas fa-user w-5"></i>
                    <span>Profil</span>
                </a>
            </nav>
        </div>

        <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-white/20">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'Guest'); ?></div>
                    <div class="text-xs text-white/70"><?php echo htmlspecialchars(ucfirst($_SESSION['user_role'] ?? 'guest')); ?></div>
                </div>
            </div>
            <a href="../auth/logout.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition text-red-300">
                <i class="fas fa-sign-out-alt w-5"></i>
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
        
        mobileMenuBtn?.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    </script>
</body>
</html>
