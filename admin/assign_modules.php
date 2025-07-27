<?php
$page_title = 'Assign Modules';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Get user ID from the URL
if (!isset($_GET['user_id']) || !filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    redirect('manage_users.php');
}
$user_id = (int)$_GET['user_id'];

try {
    // Fetch the user's details
    $stmt_user = $pdo->prepare("SELECT first_name, last_name, staff_id FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch();

    if (!$user) {
        redirect('manage_users.php');
    }

    // Fetch all available modules
    $all_modules = $pdo->query("SELECT id, title, module_order FROM modules ORDER BY module_order ASC")->fetchAll();

    // Fetch the modules this user is already assigned to
    $stmt_assigned = $pdo->prepare("SELECT module_id FROM user_module_assignments WHERE user_id = ?");
    $stmt_assigned->execute([$user_id]);
    $assigned_modules = $stmt_assigned->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Assign Modules Error: " . $e->getMessage());
    die("An error occurred.");
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="mb-6">
        <a href="manage_users.php" class="text-primary hover:underline">&larr; Back to User Management</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md max-w-2xl mx-auto">
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Assign Modules for:</h3>
        <p class="text-2xl font-bold text-gray-900 mb-6"><?= escape($user['first_name'] . ' ' . $user['last_name']) ?> (<?= escape($user['staff_id']) ?>)</p>

        <div id="assignment-feedback" class="mb-4 text-center"></div>
        
        <form id="assignment-form">
            <input type="hidden" name="user_id" value="<?= $user_id ?>">
            <input type="hidden" name="action" value="update_assignments">

            <div class="space-y-4">
                <?php if (empty($all_modules)): ?>
                    <p class="text-gray-500">No modules have been created yet.</p>
                <?php else: ?>
                    <?php foreach ($all_modules as $module): ?>
                        <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="modules[]" value="<?= $module['id'] ?>" 
                                   class="h-5 w-5 text-primary focus:ring-primary border-gray-300 rounded"
                                   <?= in_array($module['id'], $assigned_modules) ? 'checked' : '' ?>>
                            <span class="ml-3 text-gray-700">
                                Module <?= escape($module['module_order']) ?>: <?= escape($module['title']) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="mt-8">
                <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary-dark transition-colors">Save Assignments</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('assignment-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const feedbackDiv = document.getElementById('assignment-feedback');
    feedbackDiv.textContent = 'Saving...';
    feedbackDiv.className = 'mb-4 text-center text-blue-600';

    fetch('../api/admin/assignment_crud.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        feedbackDiv.textContent = data.message;
        feedbackDiv.className = `mb-4 text-center ${data.success ? 'text-green-600' : 'text-red-600'}`;
    })
    .catch(error => {
        console.error('Error:', error);
        feedbackDiv.textContent = 'A network or server error occurred.';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
