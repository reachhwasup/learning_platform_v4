<?php
/**
 * Module CRUD API Endpoint (CORRECTED for Separated Workflow)
 *
 * Handles Create, Read, Update, Delete for module metadata and PDFs ONLY.
 */

header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// --- Admin Authentication ---
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request.'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add_module':
            if (empty($_POST['title']) || !isset($_POST['module_order'])) {
                throw new Exception('Title and Module Order are required.');
            }

            $pdf_path = null;
            if (isset($_FILES['pdf_material']) && $_FILES['pdf_material']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_pdf = "../../uploads/materials/";
                if (!is_dir($upload_dir_pdf)) mkdir($upload_dir_pdf, 0755, true);
                $pdf_file_name = time() . '_' . basename($_FILES['pdf_material']['name']);
                if (move_uploaded_file($_FILES['pdf_material']['tmp_name'], $upload_dir_pdf . $pdf_file_name)) {
                    $pdf_path = $pdf_file_name;
                }
            }

            $sql = "INSERT INTO modules (title, description, module_order, pdf_material_path) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['title'], $_POST['description'], $_POST['module_order'], $pdf_path]);
            $response = ['success' => true, 'message' => 'Module added successfully.'];
            break;

        case 'get_module':
            $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($module) {
                $response = ['success' => true, 'data' => $module];
            } else {
                throw new Exception('Module not found.');
            }
            break;

        case 'edit_module':
            if (empty($_POST['module_id']) || empty($_POST['title']) || !isset($_POST['module_order'])) {
                throw new Exception('Module ID, Title, and Order are required.');
            }
            $module_id = $_POST['module_id'];
            
            $stmt = $pdo->prepare("SELECT pdf_material_path FROM modules WHERE id = ?");
            $stmt->execute([$module_id]);
            $pdf_path = $stmt->fetchColumn();

            if (isset($_FILES['pdf_material']) && $_FILES['pdf_material']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_pdf = '../../uploads/materials/';
                if ($pdf_path && file_exists($upload_dir_pdf . $pdf_path)) {
                    unlink($upload_dir_pdf . $pdf_path);
                }
                $pdf_file_name = time() . '_' . basename($_FILES['pdf_material']['name']);
                if (move_uploaded_file($_FILES['pdf_material']['tmp_name'], $upload_dir_pdf . $pdf_file_name)) {
                    $pdf_path = $pdf_file_name;
                }
            }

            $sql = "UPDATE modules SET title = ?, description = ?, module_order = ?, pdf_material_path = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['title'], $_POST['description'], $_POST['module_order'], $pdf_path, $module_id]);
            $response = ['success' => true, 'message' => 'Module updated successfully.'];
            break;
        
        case 'delete_module':
            $module_id = $_POST['module_id'] ?? 0;
            if (!$module_id) {
                throw new Exception('Module ID is required.');
            }
            // Fetch and delete associated files
            $stmt = $pdo->prepare("SELECT pdf_material_path, (SELECT video_path FROM videos WHERE module_id = ?) as video_path FROM modules WHERE id = ?");
            $stmt->execute([$module_id, $module_id]);
            if ($paths = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($paths['pdf_material_path'] && file_exists('../../uploads/materials/' . $paths['pdf_material_path'])) {
                    unlink('../../uploads/materials/' . $paths['pdf_material_path']);
                }
                if ($paths['video_path'] && file_exists('../../uploads/videos/' . $paths['video_path'])) {
                    unlink('../../uploads/videos/' . $paths['video_path']);
                }
            }
            $stmt_del = $pdo->prepare("DELETE FROM modules WHERE id = ?");
            $stmt_del->execute([$module_id]);
            $response = ['success' => true, 'message' => 'Module deleted successfully.'];
            break;
    }
} catch (PDOException | Exception $e) {
    error_log($e->getMessage());
    $response['message'] = 'A server error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?>
