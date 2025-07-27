<?php
/**
 * Admin Panel Header
 *
 * This file contains the opening HTML, head section, and the top navigation bar for the admin panel.
 * It is included at the beginning of every admin-facing page.
 */
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Admin Dashboard' ?> - Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js for graphs on the dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { 'primary': '#075985', 'primary-dark': '#0c4a6e', 'secondary': '#f8fafc' }
                }
            }
        }
    </script>
</head>
<body class="h-full">
<div class="flex h-screen bg-gray-100">
    <?php require_once 'sidebar.php'; // The sidebar is included within the main layout structure ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></h1>
                    <div class="flex items-center">
                        <span class="mr-4 text-gray-600">Welcome, <?= htmlspecialchars($_SESSION['user_first_name']) ?></span>
                        <a href="../api/auth/logout.php" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">Logout</a>
                    </div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <!-- Page-specific content starts after this -->
