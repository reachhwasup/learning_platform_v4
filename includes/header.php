<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? escape($page_title) : 'Security Awareness Platform' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- NEW: Link to your custom stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { 'primary': '#0052cc', 'primary-dark': '#0041a3', 'secondary': '#f4f5f7', 'accent': '#ffab00' }
                }
            }
        }
    </script>
</head>
<body class="h-full">
<div class="flex h-screen bg-gray-100">
    <?php require_once 'user_sidebar.php'; // The sidebar is now responsive ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Mobile Menu Button -->
                    <div class="md:hidden">
                        <button id="mobile-menu-button" class="p-2 rounded-md text-gray-600 hover:bg-gray-100">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                    </div>

                    <!-- Page Title (centered on mobile) -->
                    <h1 class="text-2xl font-bold text-gray-900 flex-1 text-center md:text-left"><?= isset($page_title) ? escape($page_title) : 'Dashboard' ?></h1>
                    
                    <!-- Profile Icon and Logout (only on desktop) -->
                    <div class="hidden md:flex items-center">
                        <div class="relative ml-3">
                            <div>
                                <a href="profile.php" class="flex max-w-xs items-center rounded-full bg-white text-sm text-gray-600 p-1 hover:bg-gray-100">
                                    <span class="mr-2">Hi, <?= escape($_SESSION['user_first_name']) ?></span>
                                    <?php 
                                    $profile_pic_path = 'assets/images/default_avatar.png';
                                    if (isset($_SESSION['user_profile_picture']) && $_SESSION['user_profile_picture'] !== 'default_avatar.png') {
                                        $user_pic = 'uploads/profile_pictures/' . $_SESSION['user_profile_picture'];
                                        if (file_exists($user_pic)) {
                                            $profile_pic_path = $user_pic;
                                        }
                                    }
                                    ?>
                                    <img class="h-8 w-8 rounded-full object-cover" src="<?= escape($profile_pic_path) ?>" alt="My Profile">
                                </a>
                            </div>
                        </div>
                         <a href="api/auth/logout.php" class="ml-4 rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">Logout</a>
                    </div>
                </div>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="mx-auto max-w-7xl">
                <!-- Page content will go here -->
