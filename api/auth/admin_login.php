<?php
/**
 * Admin Login API Endpoint
 *
 * Handles admin login, verifies credentials and role, and creates a session.
 */

header('Content-Type: application/json');

require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['email']) || empty($_POST['email']) || !isset($_POST['password']) || empty($_POST['password'])) {
    $response['message'] = 'Email and password are required.';
    echo json_encode($response);
    exit;
}

$email = $_POST['email'];
$password = $_POST['password'];

try {
    $sql = "SELECT id, password, role, first_name, status FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // VERIFY ROLE
        if ($user['role'] !== 'admin') {
            $response['message'] = 'Access denied. This account is not an administrator.';
            echo json_encode($response);
            exit;
        }

        if ($user['status'] !== 'active') {
            $response['message'] = 'Your admin account is inactive.';
            echo json_encode($response);
            exit;
        }

        // Create Session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['logged_in_at'] = time();

        $response['success'] = true;
        $response['message'] = 'Login successful! Redirecting...';

    } else {
        $response['message'] = 'Invalid email or password.';
    }

} catch (PDOException $e) {
    error_log("Admin Login Error: " . $e->getMessage());
    $response['message'] = 'A server error occurred.';
}

echo json_encode($response);
?>
