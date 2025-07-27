<?php
/**
 * Video CRUD API Endpoint
 *
 * Handles adding and editing the video for a specific module.
 */

header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request.'];
$action = $_POST['action'] ?? '';

try {
    $module_id = filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
    if (!$module_id) {
        throw new Exception('A valid Module ID is required.');
    }

    switch ($action) {
        case 'add_video':
        case 'edit_video':
            $video_id = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);
            $video_title = trim($_POST['video_title']);
            $video_description = trim($_POST['video_description']);

            $video_path = null;
            $thumbnail_path = null;

            // Handle Video File Upload
            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_vid = '../../uploads/videos/';
                if (!is_dir($upload_dir_vid)) mkdir($upload_dir_vid, 0755, true);
                $video_file_name = 'module_' . $module_id . '_' . time() . '.mp4';
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $upload_dir_vid . $video_file_name)) {
                    $video_path = $video_file_name;
                } else {
                    throw new Exception('Failed to move uploaded video file.');
                }
            }

            // Handle Thumbnail File Upload
            if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_thumb = '../../uploads/thumbnails/';
                if (!is_dir($upload_dir_thumb)) mkdir($upload_dir_thumb, 0755, true);
                $thumb_file_name = 'module_' . $module_id . '_thumb_' . time() . '.' . pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $upload_dir_thumb . $thumb_file_name)) {
                    $thumbnail_path = $thumb_file_name;
                }
            }

            if ($video_id) { // This is an EDIT
                $sql = "UPDATE videos SET title = ?, description = ?";
                $params = [$video_title, $video_description];
                if ($video_path) { $sql .= ", video_path = ?"; $params[] = $video_path; }
                if ($thumbnail_path) { $sql .= ", thumbnail_path = ?"; $params[] = $thumbnail_path; }
                $sql .= " WHERE id = ? AND module_id = ?";
                $params[] = $video_id;
                $params[] = $module_id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else { // This is an ADD
                if (!$video_path) throw new Exception('A video file is required.');
                $sql = "INSERT INTO videos (module_id, title, description, video_path, thumbnail_path, upload_by) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$module_id, $video_title, $video_description, $video_path, $thumbnail_path, $_SESSION['user_id']]);
            }
            $response = ['success' => true, 'message' => 'Video information saved successfully.'];
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
