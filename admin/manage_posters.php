<?php
$page_title = 'Manage Posters';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch all posters to display, ordered by the new assigned_month field
try {
    $stmt = $pdo->query("SELECT p.*, u.first_name, u.last_name FROM monthly_posters p LEFT JOIN users u ON p.uploaded_by = u.id ORDER BY p.assigned_month DESC");
    $posters = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Posters Error: " . $e->getMessage());
    $posters = [];
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="flex justify-end mb-6">
        <button id="add-poster-btn" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors">
            + Add New Poster
        </button>
    </div>

    <!-- Posters Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Poster</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Title</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Assigned Month</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="posters-table-body">
                <?php if (empty($posters)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-10 text-gray-500">No posters found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posters as $poster): ?>
                        <tr id="poster-row-<?= $poster['id'] ?>">
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <img src="../uploads/posters/<?= escape($poster['image_path']) ?>" alt="<?= escape($poster['title']) ?>" class="w-24 h-auto rounded">
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= escape($poster['title']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-semibold"><?= $poster['assigned_month'] ? date('F Y', strtotime($poster['assigned_month'])) : 'N/A' ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
                                <button onclick="editPoster(<?= $poster['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="deletePoster(<?= $poster['id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Poster Modal -->
<div id="poster-modal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="poster-form" enctype="multipart/form-data">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Add Poster</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="poster_id" id="poster_id">
                        <input type="hidden" name="action" id="form-action">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" id="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>
                        <div>
                            <label for="assigned_month" class="block text-sm font-medium text-gray-700">Assign to Month</label>
                            <input type="month" name="assigned_month" id="assigned_month" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label for="poster_image" class="block text-sm font-medium text-gray-700">Poster/Background Image</label>
                            <input type="file" name="poster_image" id="poster_image" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p id="current-image" class="mt-2 text-sm text-gray-500"></p>
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

<script>
// This JavaScript is self-contained for this page
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('poster-modal');
    const addBtn = document.getElementById('add-poster-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const form = document.getElementById('poster-form');
    const feedbackDiv = document.getElementById('form-feedback');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => {
        modal.classList.add('hidden');
        form.reset();
        feedbackDiv.textContent = '';
        document.getElementById('current-image').textContent = '';
    };

    addBtn.addEventListener('click', () => {
        form.reset();
        document.getElementById('modal-title').textContent = 'Add New Poster';
        document.getElementById('form-action').value = 'add_poster';
        document.getElementById('poster_id').value = '';
        document.getElementById('poster_image').required = true;
        openModal();
    });

    cancelBtn.addEventListener('click', closeModal);

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(form);
        
        fetch('../api/admin/poster_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal();
                location.reload(); 
            } else {
                feedbackDiv.textContent = data.message || 'An error occurred.';
            }
        });
    });
});

function editPoster(id) {
    fetch(`../api/admin/poster_crud.php?action=get_poster&id=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const poster = data.data;
            document.getElementById('modal-title').textContent = 'Edit Poster';
            document.getElementById('form-action').value = 'edit_poster';
            document.getElementById('poster_id').value = poster.id;
            document.getElementById('title').value = poster.title;
            document.getElementById('description').value = poster.description;
            // Format the date for the month input (YYYY-MM)
            if (poster.assigned_month) {
                document.getElementById('assigned_month').value = poster.assigned_month.substring(0, 7);
            }
            document.getElementById('current-image').textContent = `Current: ${poster.image_path}. Leave blank to keep.`;
            document.getElementById('poster_image').required = false;
            document.getElementById('poster-modal').classList.remove('hidden');
        }
    });
}

function deletePoster(id) {
    if (!confirm('Are you sure you want to delete this poster?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_poster');
    formData.append('poster_id', id);
    fetch('../api/admin/poster_crud.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`poster-row-${id}`).remove();
        } else {
            alert('Failed to delete poster: ' + data.message);
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
