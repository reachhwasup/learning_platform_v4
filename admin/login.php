<?php
require_once '../includes/functions.php';

// If an admin is already logged in, redirect them to the admin dashboard.
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Security Awareness Platform</title>
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
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286zm-1.5 6.144L6.75 12l-1.5-1.5m6 0l1.5 1.5-1.5 1.5" />
                </svg>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                    Administrator Sign In
                </h2>
                 <p class="mt-2 text-center text-sm text-gray-600">
                    <a href="../login.php" class="font-medium text-primary hover:text-primary-dark">
                        Not an admin? Go to user login
                    </a>
                </p>
            </div>
            
            <form id="admin-login-form" class="mt-8 space-y-6" action="../api/auth/admin_login.php" method="POST">
                <div class="-space-y-px rounded-md shadow-sm">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required class="relative block w-full appearance-none rounded-none rounded-t-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-primary focus:outline-none focus:ring-primary sm:text-sm" placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="relative block w-full appearance-none rounded-none rounded-b-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-primary focus:outline-none focus:ring-primary sm:text-sm" placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative flex w-full justify-center rounded-md border border-transparent bg-primary py-2 px-4 text-sm font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-dark focus:ring-offset-2">
                        Sign in
                    </button>
                </div>
            </form>
            <div id="error-message" class="mt-4 text-center text-sm text-red-600"></div>
        </div>
    </div>
    
    <script>
        document.getElementById('admin-login-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const errorMessageDiv = document.getElementById('error-message');

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    errorMessageDiv.textContent = data.message || 'An error occurred.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessageDiv.textContent = 'A server error occurred.';
            });
        });
    </script>
</body>
</html>
