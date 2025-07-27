<?php
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Validate module_id from URL
if (!isset($_GET['module_id']) || !filter_var($_GET['module_id'], FILTER_VALIDATE_INT)) {
    redirect('manage_modules.php');
}
$module_id = (int)$_GET['module_id'];

// Fetch module and video info
try {
    $stmt = $pdo->prepare(
        "SELECT m.title as module_title, v.id as video_id, v.title as video_title, v.description as video_description, v.video_path, v.thumbnail_path 
         FROM modules m
         LEFT JOIN videos v ON m.id = v.module_id
         WHERE m.id = ?"
    );
    $stmt->execute([$module_id]);
    $data = $stmt->fetch();

    if (!$data) {
        redirect('manage_modules.php');
    }
} catch (PDOException $e) {
    error_log("Manage Video Page Error: " . $e->getMessage());
    die("An error occurred while fetching video data.");
}

$page_title = 'Manage Video for: ' . escape($data['module_title']);
require_once 'includes/header.php';
?>

<div class="container mx-auto">
    <div class="mb-6">
        <a href="manage_video.php" class="text-primary hover:underline">&larr; Back to Video List</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        
        <!-- Left Column: Edit Form -->
        <div class="lg:col-span-3">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold text-gray-800 mb-6"><?= $data['video_id'] ? 'Edit Video Information' : 'Upload New Video' ?></h3>
                <div id="video-feedback" class="mb-4 text-center"></div>
                <form id="video-form" enctype="multipart/form-data">
                    <input type="hidden" name="module_id" value="<?= $module_id ?>">
                    <input type="hidden" name="video_id" value="<?= escape($data['video_id']) ?>">
                    <input type="hidden" name="action" value="<?= $data['video_id'] ? 'edit_video' : 'add_video' ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="video_title" class="block text-sm font-medium text-gray-700">Video Title</label>
                            <input type="text" name="video_title" id="video_title" value="<?= escape($data['video_title'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label for="video_description" class="block text-sm font-medium text-gray-700">Video Description</label>
                            <textarea name="video_description" id="video_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><?= escape($data['video_description'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label for="video_file" class="block text-sm font-medium text-gray-700">Video File (MP4)</label>
                            <input type="file" name="video_file" id="video_file" accept="video/mp4" <?= !$data['video_id'] ? 'required' : '' ?> class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0">
                            <p class="text-xs text-gray-500 mt-1">Uploading a new file will replace the current video.</p>
                        </div>
                         <div>
                            <label for="thumbnail_file" class="block text-sm font-medium text-gray-700">Thumbnail Image (JPG, PNG)</label>
                            <input type="file" name="thumbnail_file" id="thumbnail_file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0">
                             <p class="text-xs text-gray-500 mt-1">Uploading a new image will replace the current thumbnail.</p>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary-dark transition-colors">Save Video</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Previews -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
               <h3 class="text-xl font-semibold text-gray-800 mb-6">Current Video</h3>
               <?php if ($data['video_path']): ?>
                   <div class="aspect-w-16 aspect-h-9 bg-black rounded-lg overflow-hidden">
                       <video controls class="w-full h-full" src="../uploads/videos/<?= escape($data['video_path']) ?>"></video>
                   </div>
               <?php else: ?>
                   <div class="flex items-center justify-center h-48 bg-gray-100 rounded-lg">
                       <p class="text-gray-500">No video uploaded.</p>
                   </div>
               <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
               <h3 class="text-xl font-semibold text-gray-800 mb-6">Current Thumbnail</h3>
               <?php if ($data['thumbnail_path']): ?>
                    <div class="max-w-sm mx-auto">
                        <img src="../uploads/thumbnails/<?= escape($data['thumbnail_path']) ?>" alt="Current Thumbnail" class="w-full h-auto rounded-lg shadow-sm">
                    </div>
               <?php else: ?>
                   <div class="flex items-center justify-center h-32 bg-gray-100 rounded-lg">
                       <p class="text-gray-500">No thumbnail uploaded.</p>
                   </div>
               <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('video-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const feedbackDiv = document.getElementById('video-feedback');
    feedbackDiv.textContent = 'Uploading and saving... Please wait.';
    feedbackDiv.className = 'mb-4 text-center text-blue-600';

    fetch('../api/admin/video_crud.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        feedbackDiv.textContent = data.message;
        feedbackDiv.className = `mb-4 text-center ${data.success ? 'text-green-600' : 'text-red-600'}`;
        if (data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        feedbackDiv.textContent = 'A network or server error occurred.';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
