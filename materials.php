<?php
$page_title = 'Download Materials';
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];

try {
    // Fetch all modules that have a PDF material attached
    $sql_materials = "SELECT id, title, description, module_order, pdf_material_path 
                      FROM modules 
                      WHERE pdf_material_path IS NOT NULL AND pdf_material_path != ''
                      ORDER BY module_order ASC";
    $stmt_materials = $pdo->query($sql_materials);
    $materials = $stmt_materials->fetchAll();

    // Fetch completed modules for the user
    $stmt_progress = $pdo->prepare("SELECT module_id FROM user_progress WHERE user_id = ?");
    $stmt_progress->execute([$user_id]);
    $completed_modules = $stmt_progress->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Materials Page Error: " . $e->getMessage());
    $materials = [];
    $completed_modules = [];
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800">Training Materials</h1>
        <p class="text-lg text-gray-600 mt-2">Download supplementary PDF materials for the modules you have completed.</p>
    </div>

    <?php if (empty($materials)): ?>
        <div class="bg-white p-8 rounded-lg shadow-md text-center text-gray-500">
            <p>There are currently no training materials available.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ($materials as $material): ?>
                <?php
                    // Determine if the user can download this material
                    $can_download = in_array($material['id'], $completed_modules);
                ?>
                <!-- Material Card -->
                <div class="bg-white shadow-lg rounded-lg overflow-hidden flex flex-col">
                    <div class="p-6 flex-grow">
                        <p class="text-sm text-gray-500">Module <?= escape($material['module_order']) ?></p>
                        <h3 class="text-lg font-bold text-gray-900 mt-1 h-14">
                            <?= escape($material['title']) ?>
                        </h3>
                        <p class="text-gray-600 mt-2 text-sm h-16 overflow-hidden">
                            <?= escape($material['description']) ?>
                        </p>
                    </div>
                    <div class="p-6 bg-gray-50 border-t">
                        <?php if ($can_download): ?>
                            <a href="download.php?module_id=<?= escape($material['id']) ?>" 
                               class="block w-full text-center bg-primary text-white font-bold py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download PDF
                            </a>
                        <?php else: ?>
                            <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6.364-6.364l-1.414 1.414M21 12h-2M12 3V1m-6.364 6.364L4.222 7.222M19.778 7.222l-1.414 1.414M12 21a9 9 0 110-18 9 9 0 010 18zM12 9v6"></path></svg>
                                Complete Module to Unlock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>
