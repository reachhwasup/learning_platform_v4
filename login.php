<?php
// This page doesn't need a session check, as it's the entry point for non-logged-in users.
// We can include functions to start the session for when the form is processed.
require_once 'includes/functions.php';

// If a user is already logged in, redirect them away from the login page.
if (is_logged_in()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Security Awareness Platform</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Link to our custom Tailwind config
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'primary': '#0052cc',
                        'primary-dark': '#0041a3',
                        'secondary': '#f4f5f7',
                        'accent': '#ffab00',
                    }
                }
            }
        }
    </script>
    <style>
        /* A little extra style for the background */
        body {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <!-- You can replace this with your logo -->
                <svg class="mx-auto h-12 w-auto text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="signup.php" class="font-medium text-primary hover:text-primary-dark">
                        create a new account
                    </a>
                </p>
            </div>
            
            <!-- Login Form -->
            <form id="login-form" class="mt-8 space-y-6" action="api/auth/user_login.php" method="POST">
                <input type="hidden" name="remember" value="true">
                <div class="-space-y-px rounded-md shadow-sm">
                    <div>
                        <label for="staff-id" class="sr-only">Staff ID</label>
                        <input id="staff-id" name="staff_id" type="text" required class="relative block w-full appearance-none rounded-none rounded-t-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-primary focus:outline-none focus:ring-primary sm:text-sm" placeholder="Staff ID">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="relative block w-full appearance-none rounded-none rounded-b-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-primary focus:outline-none focus:ring-primary sm:text-sm" placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative flex w-full justify-center rounded-md border border-transparent bg-primary py-2 px-4 text-sm font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-dark focus:ring-offset-2">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-indigo-300 group-hover:text-indigo-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        Sign in
                    </button>
                </div>
            </form>
            <!-- Error message container -->
            <div id="error-message" class="mt-4 text-center text-sm text-red-600"></div>
        </div>
    </div>
    
    <script>
        // UPDATED AJAX submission to handle dynamic redirects
        document.getElementById('login-form').addEventListener('submit', function(event) {
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
                if (data.success && data.redirect_url) {
                    // On successful login, redirect to the URL provided by the server
                    window.location.href = data.redirect_url;
                } else {
                    // Show error message
                    errorMessageDiv.textContent = data.message || 'An unknown error occurred.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessageDiv.textContent = 'A server error occurred. Please try again later.';
            });
        });
    </script>

</body>
</html>
