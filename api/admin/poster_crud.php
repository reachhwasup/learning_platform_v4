<?php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request.'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add_poster':
            if (empty($_POST['title']) || empty($_POST['assigned_month']) || !isset($_FILES['poster_image']) || $_FILES['poster_image']['error'] != 0) {
                throw new Exception('Title, Assigned Month, and a poster image are required.');
            }
            $target_dir = "../../uploads/posters/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            $file_name = uniqid() . '_' . basename($_FILES["poster_image"]["name"]);
            if (!move_uploaded_file($_FILES["poster_image"]["tmp_name"], $target_dir . $file_name)) {
                throw new Exception('Failed to upload image.');
            }
            // Add '-01' to the month to make it a valid DATE for the database
            $assigned_month_date = $_POST['assigned_month'] . '-01';
            $sql = "INSERT INTO monthly_posters (title, description, assigned_month, image_path, uploaded_by) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['title'], $_POST['description'], $assigned_month_date, $file_name, $_SESSION['user_id']]);
            $response = ['success' => true, 'message' => 'Poster added successfully.'];
            break;

        case 'get_poster':
            $stmt = $pdo->prepare("SELECT * FROM monthly_posters WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $response = ['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)];
            break;

        case 'edit_poster':
            $poster_id = $_POST['poster_id'];
            $stmt = $pdo->prepare("SELECT image_path FROM monthly_posters WHERE id = ?");
            $stmt->execute([$poster_id]);
            $current_image = $stmt->fetchColumn();
            $image_path = $current_image;

            if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] == 0) {
                if ($current_image && file_exists("../../uploads/posters/" . $current_image)) {
                    unlink("../../uploads/posters/" . $current_image);
                }
                $target_dir = "../../uploads/posters/";
                $file_name = uniqid() . '_' . basename($_FILES["poster_image"]["name"]);
                if (move_uploaded_file($_FILES["poster_image"]["tmp_name"], $target_dir . $file_name)) {
                    $image_path = $file_name;
                }
            }
            $assigned_month_date = $_POST['assigned_month'] . '-01';
            $sql = "UPDATE monthly_posters SET title = ?, description = ?, assigned_month = ?, image_path = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['title'], $_POST['description'], $assigned_month_date, $image_path, $poster_id]);
            $response = ['success' => true, 'message' => 'Poster updated successfully.'];
            break;

        case 'delete_poster':
            $poster_id = $_POST['poster_id'];
            $stmt = $pdo->prepare("SELECT image_path FROM monthly_posters WHERE id = ?");
            $stmt->execute([$poster_id]);
            if ($image = $stmt->fetchColumn()) {
                if (file_exists("../../uploads/posters/" . $image)) {
                    unlink("../../uploads/posters/" . $image);
                }
            }
            $stmt = $pdo->prepare("DELETE FROM monthly_posters WHERE id = ?");
            $stmt->execute([$poster_id]);
            $response = ['success' => true, 'message' => 'Poster deleted.'];
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
