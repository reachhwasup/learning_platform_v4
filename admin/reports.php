<?php
$page_title = 'Assessment Reports';
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch data for all assessment results
try {
    $sql = "SELECT u.first_name, u.last_name, u.staff_id, u.email, d.name as department_name, fa.id as assessment_id, fa.score, fa.status, fa.completed_at
            FROM final_assessments fa
            JOIN users u ON fa.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE fa.status IN ('passed', 'failed')
            ORDER BY fa.completed_at DESC";
    
    $stmt = $pdo->query($sql);
    $all_results = $stmt->fetchAll();

    // Separate results into passed and failed arrays
    $passed_users = array_filter($all_results, fn($r) => $r['status'] === 'passed');
    $failed_users = array_filter($all_results, fn($r) => $r['status'] === 'failed');

} catch (PDOException $e) {
    error_log("Reports Page Error: " . $e->getMessage());
    $all_results = [];
    $passed_users = [];
    $failed_users = [];
}

require_once 'includes/header.php';

// Helper function to render the results table
function render_results_table($users, $status_filter) {
    // Determine title and color based on status
    switch ($status_filter) {
        case 'passed':
            $title = 'Passed';
            $color = 'green';
            break;
        case 'failed':
            $title = 'Failed';
            $color = 'red';
            break;
        default:
            $title = 'All Results';
            $color = 'primary';
            break;
    }

    echo "<div class='mb-12'>";
    echo "<div class='flex justify-between items-center mb-4'>";
    echo "<h2 class='text-2xl font-semibold text-gray-800'>{$title}</h2>";
    echo "<a href='../api/admin/generate_report.php?status={$status_filter}' class='bg-{$color}-600 hover:bg-{$color}-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors'>Export {$title} List (Excel)</a>";
    echo "</div>";

    echo "<div class='bg-white shadow-md rounded-lg overflow-x-auto'>";
    echo "<table class='min-w-full leading-normal'>";
    echo "<thead class='bg-gray-200'><tr>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>No.</th>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>Name</th>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>Staff ID</th>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>Department</th>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>Score (Points)</th>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>Status</th>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>Date Completed</th>
            <th class='px-5 py-3 border-b-2 border-gray-300 text-left text-xs font-semibold text-gray-700 uppercase'>Details</th>
          </tr></thead>";
    echo "<tbody>";

    if (empty($users)) {
        echo "<tr><td colspan='8' class='text-center py-10 text-gray-500'>No users found in this category.</td></tr>";
    } else {
        $row_num = 1;
        foreach ($users as $user) {
            $status_badge_class = $user['status'] === 'passed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            echo "<tr>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm'>" . $row_num++ . "</td>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm'>" . escape($user['first_name'] . ' ' . $user['last_name']) . "</td>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm'>" . escape($user['staff_id']) . "</td>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm'>" . escape($user['department_name'] ?? 'N/A') . "</td>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm font-semibold'>" . (int)$user['score'] . "</td>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm'>
                        <span class='relative inline-block px-3 py-1 font-semibold leading-tight rounded-full {$status_badge_class}'>" . ucfirst(escape($user['status'])) . "</span>
                    </td>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm'>" . date('M d, Y H:i', strtotime($user['completed_at'])) . "</td>
                    <td class='px-5 py-5 border-b border-gray-200 bg-white text-sm'>
                        <a href='view_exam_details.php?assessment_id={$user['assessment_id']}' class='text-primary hover:underline'>View Details</a>
                    </td>
                  </tr>";
        }
    }
    echo "</tbody></table></div></div>";
}
?>

<div class="container mx-auto">
    <?php
        render_results_table($all_results, 'all');
        render_results_table($passed_users, 'passed');
        render_results_table($failed_users, 'failed');
    ?>
</div>

<?php require_once 'includes/footer.php'; ?>
