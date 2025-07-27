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
$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_assignments') {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $assigned_modules = $_POST['modules'] ?? []; // Array of module IDs

        if (!$user_id) {
            throw new Exception('Invalid user ID.');
        }

        $pdo->beginTransaction();

        // 1. Delete all existing assignments for this user
        $stmt_delete = $pdo->prepare("DELETE FROM user_module_assignments WHERE user_id = ?");
        $stmt_delete->execute([$user_id]);

        // 2. Insert the new assignments
        if (!empty($assigned_modules)) {
            $sql_insert = "INSERT INTO user_module_assignments (user_id, module_id) VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            foreach ($assigned_modules as $module_id) {
                $stmt_insert->execute([$user_id, (int)$module_id]);
            }
        }

        $pdo->commit();
        $response = ['success' => true, 'message' => 'User assignments updated successfully.'];
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
