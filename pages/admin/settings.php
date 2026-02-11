<?php
require_once __DIR__ . '/../../src/Auth.php';

if (!Auth::isBoard()) {
    header('Location: /index.php');
    exit;
}

$title = 'System Einstellungen - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="fas fa-cog text-purple-600 mr-2"></i>
        System Einstellungen
    </h1>
</div>

<div class="card p-6">
    <p class="text-gray-600">Einstellungen sind aktuell deaktiviert.</p>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
