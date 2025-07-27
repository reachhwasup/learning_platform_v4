<?php
// 1. Authentication and Initialization
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$module_id = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT);

if (!$module_id) {
    die("Invalid module specified.");
}

try {
    // 2. Verify user has completed this module
    $stmt_progress = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = ? AND module_id = ?");
    $stmt_progress->execute([$user_id, $module_id]);
    
    if (!$stmt_progress->fetch()) {
        // If user has not completed the module, deny access
        die("Access Denied: You must complete the module before downloading its material.");
    }

    // 3. Fetch the file path from the database
    $stmt_file = $pdo->prepare("SELECT pdf_material_path FROM modules WHERE id = ?");
    $stmt_file->execute([$module_id]);
    $file_name = $stmt_file->fetchColumn();

    if (!$file_name) {
        die("No training material found for this module.");
    }

    // 4. Force download the file
    $file_path_absolute = __DIR__ . '/uploads/materials/' . $file_name;

    if (file_exists($file_path_absolute)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file_path_absolute) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path_absolute));
        readfile($file_path_absolute);
        exit;
    } else {
        die("File not found on server. Please contact an administrator.");
    }

} catch (PDOException $e) {
    error_log("Download Error: " . $e->getMessage());
    die("A database error occurred.");
}
?>
