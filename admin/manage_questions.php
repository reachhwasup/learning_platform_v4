<?php
$page_title = 'Manage Questions';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// --- Filter Logic ---
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
$params = [];

if ($filter === 'final_exam') {
    $where_clause = 'WHERE q.is_final_exam_question = 1';
} elseif (str_starts_with($filter, 'module_')) {
    $module_id = (int)substr($filter, 7);
    if ($module_id > 0) {
        $where_clause = 'WHERE q.module_id = :module_id AND q.is_final_exam_question = 0';
        $params[':module_id'] = $module_id;
    }
}

// --- Pagination Logic ---
$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

try {
    // Fetch modules for dropdowns
    $modules = $pdo->query("SELECT id, title, module_order FROM modules ORDER BY module_order ASC")->fetchAll();

    // Get total number of questions for pagination based on filter
    $total_records_sql = "SELECT COUNT(*) FROM questions q " . $where_clause;
    $total_records_stmt = $pdo->prepare($total_records_sql);
    $total_records_stmt->execute($params);
    $total_records = $total_records_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch questions for the current page based on filter
    $sql = "SELECT q.id, q.question_text, q.question_type, q.is_final_exam_question, m.title as module_title 
            FROM questions q 
            LEFT JOIN modules m ON q.module_id = m.id 
            {$where_clause}
            ORDER BY m.module_order, q.id DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    // Bind parameters from filter and pagination
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Manage Questions Error: " . $e->getMessage());
    $modules = [];
    $questions = [];
    $total_pages = 0;
}

require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <!-- Action Buttons and Import Section -->
    <div class="md:flex justify-between items-start mb-6 bg-white p-4 rounded-lg shadow">
        <div class="flex items-start gap-4">
            <button id="add-question-btn" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors">
                + Add New Question
            </button>
            <button id="delete-all-questions-btn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Delete All
            </button>
        </div>
        <div class="border-t md:border-t-0 md:border-l border-gray-200 pt-4 md:pt-0 md:pl-4 w-full md:w-auto mt-4 md:mt-0">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Import Questions</h3>
            <form id="import-form" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start gap-2">
                <input type="file" name="import_file" required accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors w-full sm:w-auto">Import</button>
            </form>
            <div id="import-feedback" class="mt-2 text-sm"></div>
            <a href="../assets/templates/question_template.csv" download class="text-sm text-primary hover:underline mt-2 inline-block">Download CSV Template</a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="mb-6">
        <form method="GET" class="flex items-center gap-4">
            <label for="filter" class="text-sm font-medium text-gray-700">Filter by:</label>
            <select name="filter" id="filter" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Questions</option>
                <option value="final_exam" <?= $filter === 'final_exam' ? 'selected' : '' ?>>Final Exam Questions</option>
                <optgroup label="Module Questions">
                    <?php foreach ($modules as $module): ?>
                        <option value="module_<?= $module['id'] ?>" <?= $filter === 'module_' . $module['id'] ? 'selected' : '' ?>>
                            Module <?= $module['module_order'] ?>: <?= escape($module['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </form>
    </div>

    <!-- Questions Table -->
    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">No.</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Question</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Type</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Associated With</th>
                    <th class="px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                     <tr>
                        <td colspan="5" class="text-center py-10 text-gray-500">No questions found for this filter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($questions as $index => $q): ?>
                        <tr id="question-row-<?= $q['id'] ?>">
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= $offset + $index + 1 ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap"><?= escape($q['question_text']) ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm capitalize"><?= escape($q['question_type']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <?php if ($q['is_final_exam_question']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Final Exam</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><?= escape($q['module_title'] ?? 'N/A') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <button onclick="editQuestion(<?= $q['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="deleteQuestion(<?= $q['id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
        <div class="py-6 flex justify-center">
            <nav class="flex rounded-md shadow">
                <a href="?filter=<?= $filter ?>&page=<?= max(1, $current_page - 1) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-l-md hover:bg-gray-50">Previous</a>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="px-4 py-2 text-sm font-medium <?= $i == $current_page ? 'bg-primary text-white' : 'text-gray-700 bg-white hover:bg-gray-50' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="?filter=<?= $filter ?>&page=<?= min($total_pages, $current_page + 1) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-r-md hover:bg-gray-50">Next</a>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Question Modal -->
<div id="question-modal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 text-center sm:block">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full">
            <form id="question-form">
                <div class="bg-white px-6 pt-5 pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="question-modal-title">Add Question</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="question_id" id="question_id">
                        <input type="hidden" name="action" id="question-form-action">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Association</label>
                            <select name="association_type" id="association_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="module">Module Quiz</option>
                                <option value="final_exam">Final Exam</option>
                            </select>
                        </div>
                        <div id="module-select-container">
                            <label for="module_id" class="block text-sm font-medium text-gray-700">Module</label>
                            <select name="module_id" id="module_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select a module</option>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?= $module['id'] ?>">Module <?= $module['module_order'] ?>: <?= escape($module['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="question_text" class="block text-sm font-medium text-gray-700">Question Text</label>
                            <textarea name="question_text" id="question_text" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Question Type</label>
                            <select name="question_type" id="question_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="single">Single Answer</option>
                                <option value="multiple">Multiple Answers</option>
                            </select>
                        </div>
                        
                        <div id="options-container" class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">Answer Options</label>
                            <!-- Options will be dynamically added here by JavaScript -->
                        </div>
                        <button type="button" id="add-option-btn" class="text-sm text-primary hover:underline">+ Add Another Option</button>
                    </div>
                </div>
                <div id="question-form-feedback" class="px-6 py-2 text-sm text-red-600"></div>
                <div class="bg-gray-50 px-6 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark sm:ml-3 sm:w-auto sm:text-sm">Save Question</button>
                    <button type="button" id="question-cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/admin.js"></script>
<?php require_once 'includes/footer.php'; ?>
