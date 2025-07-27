<?php
/**
 * User Login API Endpoint (UPDATED)
 *
 * Handles user authentication and now saves the profile picture path to the session.
 */

// Set the content type of the response to JSON
header('Content-Type: application/json');

// Include necessary files
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// --- Response Object ---
$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

// --- Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// --- Input Validation ---
if (!isset($_POST['staff_id']) || empty($_POST['staff_id']) || !isset($_POST['password']) || empty($_POST['password'])) {
    $response['message'] = 'Staff ID and password are required.';
    echo json_encode($response);
    exit;
}

$staff_id = $_POST['staff_id'];
$password = $_POST['password'];

try {
    // --- Fetch User from Database (UPDATED to include profile_picture) ---
    $sql = "SELECT id, password, role, first_name, status, password_reset_required, profile_picture FROM users WHERE staff_id = :staff_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['staff_id' => $staff_id]);
    $user = $stmt->fetch();

    // --- Verify User and Password ---
    if ($user && password_verify($password, $user['password'])) {
        
        // Check if the user account is active
        if ($user['status'] !== 'active') {
            $response['message'] = 'Your account is inactive. Please contact an administrator.';
            echo json_encode($response);
            exit;
        }

        // --- Create Session ---
        session_regenerate_id(true);

        // Store user data in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['logged_in_at'] = time();
        $_SESSION['password_reset_required'] = (bool)$user['password_reset_required'];
        
        // --- NEW: Store the profile picture path in the session ---
        $_SESSION['user_profile_picture'] = $user['profile_picture'];

        $response['success'] = true;
        $response['message'] = 'Login successful! Redirecting...';

        // Determine the correct redirect URL
        if ($user['password_reset_required']) {
            $response['redirect_url'] = 'force_change_password.php';
        } elseif ($user['role'] === 'admin') {
            $response['redirect_url'] = 'admin/index.php';
        } else {
            $response['redirect_url'] = 'dashboard.php';
        }

    } else {
        // --- Invalid Credentials ---
        $response['message'] = 'Invalid Staff ID or password.';
    }

} catch (PDOException $e) {
    // --- Database Error ---
    error_log("Login Error: " . $e->getMessage()); // Log the actual error
    $response['message'] = 'A server error occurred. Please try again later.';
}

// --- Send Response ---
echo json_encode($response);
?>
