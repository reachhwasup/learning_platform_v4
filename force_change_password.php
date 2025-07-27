<?php
/*
File: /security-learning-platform/force_change_password.php (UI UPDATED)
Description: This page requires the user to set a new password before they can proceed, now with an improved user interface.
*/
require_once 'includes/db_connect.php'; // Use your path
require_once 'includes/functions.php';   // Use your path

if (session_status() === PHP_SESSION_NONE) session_start();

// If the user isn't logged in, send them to the login page.
if (!is_logged_in()) {
    redirect('login.php'); // Use your login page name
}

$user_id = $_SESSION['user_id'];
$error = '';
$message = '';

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "The new passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Your new password must be at least 8 characters long.";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password AND set the reset flag back to 0
            $sql = "UPDATE users SET password = ?, password_reset_required = 0 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hashed_password, $user_id]);

            // --- FIX: Update the session to reflect the change ---
            $_SESSION['password_reset_required'] = false;

            $message = "Password updated successfully! Redirecting to your dashboard...";
            
            // Redirect to the dashboard after a short delay
            header("Refresh: 3; url=dashboard.php");

        } catch (PDOException $e) {
            $error = "A database error occurred. Please try again.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Your Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { 'primary': '#075985', 'primary-dark': '#0c4a6e' }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <svg class="mx-auto h-12 w-auto text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                </svg>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                    Update Your Password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    For your security, you must set a new password.
                </p>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-md space-y-6">
                <?php if ($message): ?>
                    <div class="text-center">
                        <p class="text-green-600"><?= escape($message) ?></p>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                            <p><?= escape($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="force_change_password.php" class="space-y-6">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <button type="submit" class="group relative flex w-full justify-center rounded-md border border-transparent bg-primary py-2 px-4 text-sm font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-dark focus:ring-offset-2">
                                Set New Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
