<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// If a user is already logged in, redirect them away from the signup page.
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Fetch departments for the dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
    $departments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Could not fetch departments: " . $e->getMessage());
    $departments = []; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Security Awareness Platform</title>
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
</head>
<body class="bg-secondary">
    <div class="flex min-h-screen items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-4xl space-y-8">
            <div class="bg-white shadow-2xl rounded-2xl p-8 md:p-12">
                <div class="text-center mb-10">
                    <svg class="mx-auto h-12 w-auto text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">
                        Create Your New Account
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Join the platform to start your security awareness training.
                    </p>
                </div>
                
                <!-- Signup Form -->
                <form id="signup-form" class="space-y-6" action="api/auth/user_signup.php" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" name="first_name" id="first_name" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" name="last_name" id="last_name" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                        <div class="md:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" id="email" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                        <div>
                            <label for="staff_id" class="block text-sm font-medium text-gray-700">Staff ID</label>
                            <input type="text" name="staff_id" id="staff_id" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-700">Position / Title</label>
                            <input type="text" name="position" id="position" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" id="password" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                        <div>
                            <label for="password_confirm" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" name="password_confirm" id="password_confirm" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                        <div class="md:col-span-2">
                            <label for="department_id" class="block text-sm font-medium text-gray-700">Department</label>
                            <select name="department_id" id="department_id" required class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                                <option value="" disabled selected>Please select your department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= escape($dept['id']) ?>"><?= escape($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">Gender (Optional)</label>
                            <select name="gender" id="gender" class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="dob" class="block text-sm font-medium text-gray-700">Date of Birth (Optional)</label>
                            <input type="date" name="dob" id="dob" class="mt-1 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary transition-colors duration-200">
                        </div>
                    </div>

                    <div class="pt-8">
                        <button type="submit" class="group relative flex w-full justify-center rounded-lg border border-transparent bg-primary py-3 px-4 text-base font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-dark focus:ring-offset-2">
                            Create Account
                        </button>
                    </div>
                </form>
                <!-- Message container -->
                <div id="message-container" class="mt-6 text-center text-sm"></div>
                <div class="mt-6 text-center text-sm text-gray-600">
                    Already have an account?
                    <a href="login.php" class="font-medium text-primary hover:text-primary-dark">
                        Sign in here
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('signup-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message-container');
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;

            messageDiv.textContent = '';
            messageDiv.className = 'mt-6 text-center text-sm';

            // Client-side password match validation
            if (password !== passwordConfirm) {
                messageDiv.textContent = 'Passwords do not match.';
                messageDiv.classList.add('text-red-600');
                return;
            }

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.textContent = data.message;
                    messageDiv.classList.add('text-green-600');
                    form.reset();
                    // Optional: Redirect to login after a short delay
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    messageDiv.textContent = data.message;
                    messageDiv.classList.add('text-red-600');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.textContent = 'A server error occurred. Please try again later.';
                messageDiv.classList.add('text-red-600');
            });
        });
    </script>
</body>
</html>
