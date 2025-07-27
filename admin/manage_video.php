<?php
$page_title = 'Manage Videos';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch all modules to display in the table
try {
    $stmt = $pdo->query(
        "SELECT m.id, m.title, m.module_order, v.video_path 
         FROM modules m
         LEFT JOIN videos v ON m.id = v.module_id
         ORDER BY m.module_order ASC"
    );
    $modules = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Video Page Error: " . $e->getMessage());
    $modules = [];
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <!-- Modules Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase">Order</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase">Module Title</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase">Current Video File</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($modules)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-10 text-gray-500">No modules found. Please create a module first on the 'Manage Modules' page.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($modules as $module): ?>
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= escape($module['module_order']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= escape($module['title']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <?= $module['video_path'] ? basename($module['video_path']) : '<span class="text-gray-500">No video uploaded</span>' ?>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
                                <a href="manage_video_details.php?module_id=<?= $module['id'] ?>" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg">
                                    Manage Video
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
