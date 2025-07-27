<?php
$page_title = 'My Profile';
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];

try {
    // Fetch current user data
    $stmt_user = $pdo->prepare(
        "SELECT u.first_name, u.last_name, u.email, u.staff_id, u.gender, u.dob, u.position, u.profile_picture, d.name as department_name 
         FROM users u
         LEFT JOIN departments d ON u.department_id = d.id
         WHERE u.id = ?"
    );
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch();

    if (!$user) {
        redirect('api/auth/logout.php');
    }

    // Check if the user has passed the final assessment to display the badge
    $stmt_badge = $pdo->prepare("SELECT id FROM final_assessments WHERE user_id = ? AND status = 'passed' LIMIT 1");
    $stmt_badge->execute([$user_id]);
    $has_passed = $stmt_badge->fetch();

} catch (PDOException $e) {
    error_log("Profile Page Error: " . $e->getMessage());
    die("An error occurred while loading your profile.");
}

require_once 'includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Profile Update Form -->
    <div class="md:col-span-2 bg-white shadow-md rounded-lg p-6">
        <form id="profile-form" enctype="multipart/form-data">
            <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">Personal Information</h3>
            
            <div id="profile-feedback" class="mb-4 text-center"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" id="first_name" value="<?= escape($user['first_name']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" id="last_name" value="<?= escape($user['last_name']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                    <select name="gender" id="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="Male" <?= $user['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $user['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $user['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="dob" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" name="dob" id="dob" value="<?= escape($user['dob']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>
            <div class="mt-8">
                <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary-dark transition-colors">Update Information</button>
            </div>
        </form>

        <!-- Change Password Form -->
        <form id="password-form" class="mt-10">
            <h3 class="text-xl font-semibold text-gray-800 border-b pb-4 mb-6">Change Password</h3>
            <div id="password-feedback" class="mb-4 text-center"></div>
            <div class="space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>
             <div class="mt-8">
                <button type="submit" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-800 transition-colors">Change Password</button>
            </div>
        </form>
    </div>

    <!-- Profile Card -->
    <div class="md:col-span-1">
        <div class="bg-white shadow-md rounded-lg p-6 text-center">
            <img id="profile-pic-display" src="uploads/profile_pictures/<?= escape($user['profile_picture']) ?>" alt="Profile Picture" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover" onerror="this.src='assets/images/default_avatar.png'">
            <div class="flex items-center justify-center">
                <h2 class="text-2xl font-bold text-gray-900"><?= escape($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                <?php if ($has_passed): ?>
                    <!-- Achievement Badge -->
                    <span title="Assessment Passed" class="ml-2 text-green-500">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a.75.75 0 00-1.06-1.06L9 10.94l-1.72-1.72a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.06 0l4.25-4.25z" clip-rule="evenodd" /></svg>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-gray-600"><?= escape($user['position']) ?></p>
            <form id="picture-form" class="mt-4">
                <label for="profile_picture" class="cursor-pointer bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg transition-colors text-sm">
                    Change Picture
                </label>
                <input type="file" name="profile_picture" id="profile_picture" class="hidden" accept="image/*">
            </form>
            <div class="text-left mt-6 border-t pt-6 space-y-2">
                <p><strong class="font-medium text-gray-700">Staff ID:</strong> <?= escape($user['staff_id']) ?></p>
                <p><strong class="font-medium text-gray-700">Email:</strong> <?= escape($user['email']) ?></p>
                <p><strong class="font-medium text-gray-700">Department:</strong> <?= escape($user['department_name']) ?></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profile-form');
    const passwordForm = document.getElementById('password-form');
    const pictureForm = document.getElementById('picture-form');
    const pictureInput = document.getElementById('profile_picture');

    // Handle profile info update
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(profileForm);
        formData.append('action', 'update_info');
        submitFormData(formData, 'profile-feedback');
    });

    // Handle password change
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(passwordForm);
        formData.append('action', 'change_password');
        submitFormData(formData, 'password-feedback', () => passwordForm.reset());
    });
    
    // Handle profile picture change automatically on file selection
    pictureInput.addEventListener('change', function() {
        if (pictureInput.files.length > 0) {
            const formData = new FormData();
            formData.append('profile_picture', pictureInput.files[0]);
            formData.append('action', 'change_picture');
            submitFormData(formData, 'profile-feedback', (data) => {
                // Update image on success
                if (data.success && data.new_path) {
                    document.getElementById('profile-pic-display').src = data.new_path + '?t=' + new Date().getTime();
                }
            });
        }
    });

    function submitFormData(formData, feedbackDivId, callback) {
        const feedbackDiv = document.getElementById(feedbackDivId);
        feedbackDiv.textContent = 'Saving...';
        feedbackDiv.className = 'mb-4 text-center text-blue-600';

        fetch('api/user/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            feedbackDiv.textContent = data.message;
            feedbackDiv.className = `mb-4 text-center ${data.success ? 'text-green-600' : 'text-red-600'}`;
            if (data.success && callback) {
                callback(data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            feedbackDiv.textContent = 'A server error occurred.';
            feedbackDiv.className = 'mb-4 text-center text-red-600';
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
