<?php
$page_title = 'Manage Modules';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch all modules to display in the table
try {
    $stmt = $pdo->query("SELECT id, title, description, module_order, pdf_material_path FROM modules ORDER BY module_order ASC");
    $modules = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Modules Error: " . $e->getMessage());
    $modules = []; // Prevent page crash on DB error
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="flex justify-end mb-6">
        <button id="add-module-btn" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors">
            + Add New Module
        </button>
    </div>

    <!-- Modules Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Order</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Title</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Description</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="modules-table-body">
                <?php if (empty($modules)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-10 text-gray-500">No modules found. Click 'Add New Module' to get started.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($modules as $module): ?>
                        <tr id="module-row-<?= $module['id'] ?>">
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= escape($module['module_order']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= escape($module['title']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm max-w-xs truncate"><?= escape($module['description']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
                                <a href="manage_video.php?module_id=<?= $module['id'] ?>" class="text-green-600 hover:text-green-900 mr-3">Manage Video</a>
                                <button onclick="editModule(<?= $module['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="deleteModule(<?= $module['id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Module Modal -->
<div id="module-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="module-form" enctype="multipart/form-data">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Add Module</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="module_id" id="module_id">
                        <input type="hidden" name="action" id="form-action">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" id="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label for="module_order" class="block text-sm font-medium text-gray-700">Module Order</label>
                            <input type="number" name="module_order" id="module_order" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="pdf_material" class="block text-sm font-medium text-gray-700">PDF Material (Optional)</label>
                            <input type="file" name="pdf_material" id="pdf_material" accept=".pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p id="current-pdf" class="mt-2 text-sm text-gray-500"></p>
                        </div>
                    </div>
                </div>
                <div id="form-feedback" class="px-6 py-2 text-sm text-red-600"></div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                    <button type="button" id="cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/admin.js"></script>
<?php require_once 'includes/footer.php'; ?>
