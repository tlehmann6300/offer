<?php
require_once __DIR__ . '/../../src/Auth.php';

if (!Auth::check() || !Auth::hasPermission('board')) {
    header('Location: ../auth/login.php');
    exit;
}

$title = 'Einstellungen - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="fas fa-cog text-purple-600 mr-2"></i>
        Einstellungen
    </h1>
    <p class="text-gray-600">Systemeinstellungen verwalten</p>
</div>

<div class="card p-6">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Allgemeine Einstellungen</h2>
            <p class="text-gray-600">Diese Seite ist in Entwicklung. Hier können zukünftig Systemeinstellungen konfiguriert werden.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
