<?php
$page_title = 'Manage Users';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// --- Pagination Logic for Normal Users ---
$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

try {
    // Get total number of modules for progress calculation
    $total_modules_stmt = $pdo->query("SELECT COUNT(*) FROM modules");
    $total_modules = $total_modules_stmt->fetchColumn();
    $total_modules = $total_modules > 0 ? $total_modules : 1; // Avoid division by zero

    // --- Fetch Normal Users with Pagination and Progress ---
    $total_normal_users_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $total_records = $total_normal_users_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    $sql_users = "SELECT 
                    u.id, u.first_name, u.last_name, u.staff_id, u.role, u.status, d.name as department_name, 
                    (SELECT COUNT(*) FROM user_progress up WHERE up.user_id = u.id) as completed_modules,
                    (SELECT COUNT(*) FROM final_assessments fa_count WHERE fa_count.user_id = u.id) as exam_attempts,
                    latest_fa.quiz_started_at,
                    latest_fa.quiz_ended_at
                  FROM users u 
                  LEFT JOIN departments d ON u.department_id = d.id 
                  LEFT JOIN (
                      SELECT 
                          user_id, 
                          quiz_started_at, 
                          quiz_ended_at,
                          ROW_NUMBER() OVER(PARTITION BY user_id ORDER BY completed_at DESC) as rn
                      FROM final_assessments
                  ) AS latest_fa ON u.id = latest_fa.user_id AND latest_fa.rn = 1
                  WHERE u.role = 'user'
                  ORDER BY u.first_name, u.last_name
                  LIMIT :limit OFFSET :offset";
    $stmt_users = $pdo->prepare($sql_users);
    $stmt_users->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt_users->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_users->execute();
    $normal_users = $stmt_users->fetchAll();

    // --- Fetch All Admin Users (no pagination needed) ---
    $sql_admins = "SELECT u.id, u.first_name, u.last_name, u.email, u.staff_id, u.role, u.status 
                   FROM users u 
                   WHERE u.role = 'admin'
                   ORDER BY u.first_name, u.last_name";
    $stmt_admins = $pdo->query($sql_admins);
    $admin_users = $stmt_admins->fetchAll();
    
    // Fetch all departments for the add/edit form
    $departments = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll();

} catch (PDOException $e) {
    error_log("Manage Users Error: " . $e->getMessage());
    $normal_users = [];
    $admin_users = [];
    $departments = [];
    $total_pages = 0;
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="flex justify-end mb-6">
        <button id="add-user-btn" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors">
            + Add New User
        </button>
    </div>

    <!-- Normal Users Table -->
    <h2 class="text-2xl font-semibold text-gray-900 mb-4">Normal User Management</h2>
    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Name</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Progress</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Exam Attempts</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($normal_users)): ?>
                    <tr><td colspan="5" class="text-center py-10 text-gray-500">No normal users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($normal_users as $user): ?>
                        <?php $progress_percentage = round(($user['completed_modules'] / $total_modules) * 100); ?>
                        <tr id="user-row-<?= $user['id'] ?>">
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap font-semibold"><?= escape($user['first_name'] . ' ' . $user['last_name']) ?></p>
                                <p class="text-gray-600 whitespace-no-wrap"><?= escape($user['staff_id']) ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <div class="w-full bg-gray-200 rounded-full">
                                    <div class="bg-blue-500 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" style="width: <?= $progress_percentage ?>%"><?= $progress_percentage ?>%</div>
                                </div>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center font-semibold">
                                <?= $user['exam_attempts'] ?>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?= $user['status'] === 'active' ? 'text-green-900' : 'text-red-900' ?>">
                                    <span aria-hidden class="absolute inset-0 <?= $user['status'] === 'active' ? 'bg-green-200' : 'bg-red-200' ?> opacity-50 rounded-full"></span>
                                    <span class="relative capitalize"><?= escape($user['status']) ?></span>
                                </span>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
                                <a href="view_user_progress.php?user_id=<?= $user['id'] ?>" class="text-green-600 hover:text-green-900 mr-3">History</a>
                                <button onclick="editUser(<?= $user['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="resetPassword(<?= $user['id'] ?>)" class="text-yellow-600 hover:text-yellow-900 mr-3">Reset Pass</button>
                                <button onclick="deleteUser(<?= $user['id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination for Normal Users -->
    <?php if ($total_pages > 1): ?>
        <div class="py-6 flex justify-center">
            <nav class="flex rounded-md shadow">
                <a href="?page=<?= max(1, $current_page - 1) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-l-md hover:bg-gray-50">Previous</a>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-4 py-2 text-sm font-medium <?= $i == $current_page ? 'bg-primary text-white' : 'text-gray-700 bg-white hover:bg-gray-50' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="?page=<?= min($total_pages, $current_page + 1) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-r-md hover:bg-gray-50">Next</a>
            </nav>
        </div>
    <?php endif; ?>

    <!-- Admin Users Table -->
    <h2 class="text-2xl font-semibold text-gray-900 mt-12 mb-4">Administrator Management</h2>
    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
             <thead class="bg-gray-200">
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Name</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Staff ID / Email</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admin_users)): ?>
                    <tr><td colspan="4" class="text-center py-10 text-gray-500">No administrators found.</td></tr>
                <?php else: ?>
                    <?php foreach ($admin_users as $user): ?>
                        <tr id="user-row-<?= $user['id'] ?>">
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap font-semibold"><?= escape($user['first_name'] . ' ' . $user['last_name']) ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap"><?= escape($user['staff_id']) ?></p>
                                <p class="text-gray-600 whitespace-no-wrap"><?= escape($user['email']) ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?= $user['status'] === 'active' ? 'text-green-900' : 'text-red-900' ?>">
                                    <span aria-hidden class="absolute inset-0 <?= $user['status'] === 'active' ? 'bg-green-200' : 'bg-red-200' ?> opacity-50 rounded-full"></span>
                                    <span class="relative capitalize"><?= escape($user['status']) ?></span>
                                </span>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
                                <button onclick="editUser(<?= $user['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="resetPassword(<?= $user['id'] ?>)" class="text-yellow-600 hover:text-yellow-900 mr-3">Reset Pass</button>
                                    <button onclick="deleteUser(<?= $user['id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="user-modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="user-form">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="user-modal-title">Add New User</h3>
                    <div id="user-info-readonly" class="mt-4 p-3 bg-gray-50 rounded-md border border-gray-200 hidden">
                    </div>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="user_id" id="user_id">
                        <input type="hidden" name="action" id="user-form-action">
                        <div id="add-user-fields">
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                 <input name="first_name" id="first_name" type="text" placeholder="First Name" class="rounded-md border-gray-300">
                                 <input name="last_name" id="last_name" type="text" placeholder="Last Name" class="rounded-md border-gray-300">
                                 <input name="email" id="email" type="email" placeholder="Email Address" class="rounded-md border-gray-300 md:col-span-2">
                                 <input name="staff_id" id="staff_id" type="text" placeholder="Staff ID" class="rounded-md border-gray-300">
                                 <select name="department_id" id="department_id" class="rounded-md border-gray-300">
                                     <option value="" disabled selected>Select Department</option>
                                     <?php foreach ($departments as $dept): ?>
                                         <option value="<?= escape($dept['id']) ?>"><?= escape($dept['name']) ?></option>
                                     <?php endforeach; ?>
                                 </select>
                                 <input name="password" id="password" type="password" placeholder="Password (min. 8 chars)" class="rounded-md border-gray-300 md:col-span-2">
                            </div>
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="role" id="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div id="user-form-feedback" class="px-6 py-2 text-sm text-red-600"></div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                    <button type="button" id="user-cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/admin.js"></script>
<?php require_once 'includes/footer.php'; ?>
