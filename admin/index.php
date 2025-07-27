<?php
$page_title = 'Admin Dashboard';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch stats for dashboard cards
try {
    $total_users = $pdo->query("SELECT count(*) FROM users WHERE role = 'user'")->fetchColumn();
    $total_admins = $pdo->query("SELECT count(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $total_modules = $pdo->query("SELECT count(*) FROM modules")->fetchColumn();
    $passed_exams = $pdo->query("SELECT count(*) FROM final_assessments WHERE status = 'passed'")->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin Dashboard Stats Error: " . $e->getMessage());
    // Set defaults on error to prevent page crash
    $total_users = $total_admins = $total_modules = $passed_exams = 'N/A';
}

require_once 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="container mx-auto">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <p class="text-3xl font-bold text-gray-900"><?= $total_users ?></p>
            </div>
            <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A10.99 10.99 0 0012 5.197a10.99 10.99 0 00-3-3.999z"></path></svg>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Modules</p>
                <p class="text-3xl font-bold text-gray-900"><?= $total_modules ?></p>
            </div>
            <div class="bg-indigo-100 text-indigo-600 p-3 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Passed Exams</p>
                <p class="text-3xl font-bold text-gray-900"><?= $passed_exams ?></p>
            </div>
            <div class="bg-green-100 text-green-600 p-3 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Administrators</p>
                <p class="text-3xl font-bold text-gray-900"><?= $total_admins ?></p>
            </div>
            <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6.364-6.364l-1.414 1.414M21 12h-2M12 3V1m-6.364 6.364L4.222 7.222M19.778 7.222l-1.414 1.414M12 21a9 9 0 110-18 9 9 0 010 18zM12 9v6"></path></svg>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">User Signups by Department</h3>
            <canvas id="signupDepartmentChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Assessment Results by Department</h3>
            <canvas id="assessmentDepartmentChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Fetch data for charts from our new API endpoint
    fetch('../api/admin/dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            // --- Chart 1: User Signups by Department (Pie Chart) ---
            const signupCtx = document.getElementById('signupDepartmentChart').getContext('2d');
            const signupLabels = data.signupData.map(d => d.name);
            const signupCounts = data.signupData.map(d => d.user_count);
            
            new Chart(signupCtx, {
                type: 'pie',
                data: {
                    labels: signupLabels,
                    datasets: [{
                        label: 'Users',
                        data: signupCounts,
                        backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#ec4899'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { 
                        legend: { 
                            position: 'top',
                            labels: { color: '#333' } 
                        } 
                    }
                }
            });

            // --- Chart 2: Assessment Results by Department (Bar Chart) ---
            const assessmentCtx = document.getElementById('assessmentDepartmentChart').getContext('2d');
            const assessmentLabels = data.assessmentData.map(d => d.name);
            const passedCounts = data.assessmentData.map(d => d.passed_count);
            const failedCounts = data.assessmentData.map(d => d.failed_count);

            new Chart(assessmentCtx, {
                type: 'bar',
                data: {
                    labels: assessmentLabels,
                    datasets: [
                        {
                            label: 'Passed',
                            data: passedCounts,
                            backgroundColor: '#10b981',
                        },
                        {
                            label: 'Failed',
                            data: failedCounts,
                            backgroundColor: '#ef4444',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { 
                            stacked: true, 
                            ticks: { color: '#333' } 
                        },
                        y: { 
                            stacked: true, 
                            beginAtZero: true,
                            ticks: { color: '#333' }
                        }
                    },
                    plugins: { 
                        legend: { 
                            labels: { color: '#333' } 
                        } 
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching dashboard data:', error));
});
</script>

<?php require_once 'includes/footer.php'; ?>
