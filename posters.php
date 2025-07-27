<?php
$page_title = 'Monthly Posters';
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

// Fetch all posters, newest first based on the assigned month
try {
    $stmt = $pdo->query("SELECT title, description, image_path, assigned_month FROM monthly_posters ORDER BY assigned_month DESC");
    $posters = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Posters Page Error: " . $e->getMessage());
    $posters = [];
}

require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800">Monthly Security Posters</h1>
        <p class="text-lg text-gray-600 mt-2">Stay updated with the latest security awareness tips and backgrounds.</p>
    </div>

    <?php if (empty($posters)): ?>
        <div class="text-center py-16 bg-white rounded-lg shadow-md">
            <p class="text-gray-500">No posters have been uploaded yet. Please check back soon!</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($posters as $poster): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden group transform hover:-translate-y-2 transition-transform duration-300">
                    <!-- The image is now a link that opens in a new tab -->
                    <a href="uploads/posters/<?= escape($poster['image_path']) ?>" target="_blank">
                        <div class="relative">
                            <img src="uploads/posters/<?= escape($poster['image_path']) ?>" alt="<?= escape($poster['title']) ?>" class="w-full h-64 object-cover">
                            <!-- The download button overlay has been removed -->
                        </div>
                    </a>
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-1"><?= $poster['assigned_month'] ? date('F Y', strtotime($poster['assigned_month'])) : 'Unassigned' ?></p>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 truncate"><?= escape($poster['title']) ?></h3>
                        <p class="text-gray-700 text-sm h-16 overflow-hidden"><?= escape($poster['description']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
