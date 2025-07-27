<?php
$page_title = 'Information Security Awareness Training';
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];

try {
    $sql_modules = "SELECT m.id, m.title, m.description, m.module_order, v.thumbnail_path 
                    FROM modules m
                    LEFT JOIN videos v ON m.id = v.module_id
                    ORDER BY m.module_order ASC";
    $stmt_modules = $pdo->query($sql_modules);
    $all_modules = $stmt_modules->fetchAll();

    $sql_progress = "SELECT module_id FROM user_progress WHERE user_id = :user_id";
    $stmt_progress = $pdo->prepare($sql_progress);
    $stmt_progress->execute(['user_id' => $user_id]);
    $completed_modules = $stmt_progress->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    die("An error occurred while loading the dashboard. Please try again later.");
}

require_once 'includes/header.php';
?>

<!-- Welcome Banner Section -->
<div class="welcome-banner">
    <div class="welcome-overlay"></div>
    <div class="welcome-content">
        <h1 class="text-4xl font-bold text-white">Welcome to Your Customized Learning Experience</h1>
        <p class="text-xl text-gray-200 mt-2">Powered by APD Bank Security Awareness</p>
    </div>
</div>

<div class="container mx-auto -mt-16 relative z-10">
    
    <!-- Search Bar Section -->
    <div class="search-container">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
            </div>
            <input type="text" id="module-search" placeholder="Search modules by title..." class="block w-full rounded-md border-gray-300 py-3 pl-10 pr-3 shadow-sm sm:text-sm">
        </div>
    </div>

    <div class="flex justify-between items-center mt-8 mb-4">
        <h2 class="text-2xl font-semibold text-gray-900">Security Awareness Training Modules</h2>
        <!-- View Switcher Buttons -->
        <div class="view-switcher">
            <button id="grid-view-btn" class="view-btn active" title="Grid View">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            </button>
            <button id="list-view-btn" class="view-btn" title="List View">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
            </button>
        </div>
    </div>

    <?php if (empty($all_modules)): ?>
        <div class="bg-white p-8 rounded-lg shadow-md text-center text-gray-500">
            <p>No learning modules have been added yet. Please check back later.</p>
        </div>
    <?php else: ?>
        <!-- Grid View Container -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8" id="modules-grid-container">
            <?php foreach ($all_modules as $index => $module): ?>
                <?php
                    $is_completed = in_array($module['id'], $completed_modules);
                    $is_locked = true;
                    if ($index === 0 || ($index > 0 && in_array($all_modules[$index - 1]['id'], $completed_modules))) { $is_locked = false; }
                    if ($is_completed) $is_locked = false;
                    $thumbnail_url = !empty($module['thumbnail_path']) ? 'uploads/thumbnails/' . $module['thumbnail_path'] : 'https://placehold.co/600x400/0052cc/FFFFFF?text=Module+' . $module['module_order'];
                ?>
                <div class="bg-white shadow-lg rounded-lg overflow-hidden transform transition-transform duration-300 hover:scale-105 module-card" data-title="<?= strtolower(escape($module['title'])) ?>">
                    <a href="<?= !$is_locked ? 'view_module.php?id=' . escape($module['id']) : '#' ?>" class="<?= $is_locked ? 'pointer-events-none' : '' ?>">
                        <div class="relative">
                            <img class="w-full h-48 object-cover" src="<?= escape($thumbnail_url) ?>" alt="Module Thumbnail">
                            <?php if ($is_locked): ?>
                                <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6.364-6.364l-1.414 1.414M21 12h-2M12 3V1m-6.364 6.364L4.222 7.222M19.778 7.222l-1.414 1.414M12 21a9 9 0 110-18 9 9 0 010 18zM12 9v6"></path></svg>
                                </div>
                            <?php elseif ($is_completed): ?>
                                 <div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">COMPLETED</div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="text-lg font-bold text-gray-900 flex-grow">
                            Module <?= escape($module['module_order']) ?>: <?= escape($module['title']) ?>
                        </h3>
                        <div class="mt-4">
                             <?php if ($is_locked): ?>
                                 <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded-lg cursor-not-allowed">Locked</button>
                            <?php else: ?>
                                 <a href="view_module.php?id=<?= escape($module['id']) ?>" class="block w-full text-center bg-primary text-white font-bold py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors">
                                     <?= $is_completed ? 'Review Module' : 'Start Module' ?>
                                 </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- List View Container -->
        <div class="hidden space-y-4" id="modules-list-container">
            <?php foreach ($all_modules as $index => $module): ?>
                 <?php
                    $is_completed = in_array($module['id'], $completed_modules);
                    $is_locked = true;
                    if ($index === 0 || ($index > 0 && in_array($all_modules[$index - 1]['id'], $completed_modules))) { $is_locked = false; }
                    if ($is_completed) $is_locked = false;
                    $thumbnail_url = !empty($module['thumbnail_path']) ? 'uploads/thumbnails/' . $module['thumbnail_path'] : 'https://placehold.co/600x400/0052cc/FFFFFF?text=Module+' . $module['module_order'];
                ?>
                <div class="bg-white shadow-md rounded-lg p-4 flex items-center space-x-4 module-row" data-title="<?= strtolower(escape($module['title'])) ?>">
                    <div class="flex-shrink-0">
                        <img class="h-20 w-32 object-cover rounded" src="<?= escape($thumbnail_url) ?>" alt="Module Thumbnail">
                    </div>
                    <div class="flex-grow">
                        <p class="text-sm text-gray-500">Module <?= escape($module['module_order']) ?></p>
                        <h3 class="text-lg font-bold text-gray-900"><?= escape($module['title']) ?></h3>
                    </div>
                    <div class="flex-shrink-0 text-center">
                        <?php if ($is_completed): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Completed</span>
                        <?php elseif ($is_locked): ?>
                             <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">Locked</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">In Progress</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-shrink-0">
                        <?php if ($is_locked): ?>
                            <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-2 px-4 rounded-lg cursor-not-allowed">Locked</button>
                        <?php else: ?>
                            <a href="view_module.php?id=<?= escape($module['id']) ?>" class="block w-full text-center bg-primary text-white font-bold py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors">
                                <?= $is_completed ? 'Review' : 'Start' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="no-results-message" class="hidden col-span-full text-center py-10 text-gray-500">
            No modules match your search.
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>
