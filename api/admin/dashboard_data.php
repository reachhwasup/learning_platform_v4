<?php
/**
 * Dashboard Data API Endpoint
 *
 * This script provides data for the charts on the admin dashboard.
 */

header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// --- Admin Authentication ---
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$response = [
    'signupData' => [],
    'assessmentData' => [],
];

try {
    // 1. Get User Signup data per department
    $sql_signups = "SELECT d.name, COUNT(u.id) as user_count
                    FROM departments d
                    LEFT JOIN users u ON d.id = u.department_id AND u.role = 'user'
                    GROUP BY d.id
                    ORDER BY d.name";
    $stmt_signups = $pdo->query($sql_signups);
    $response['signupData'] = $stmt_signups->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Passed/Failed data per department
    $sql_assessments = "SELECT 
                            d.name,
                            SUM(CASE WHEN fa.status = 'passed' THEN 1 ELSE 0 END) as passed_count,
                            SUM(CASE WHEN fa.status = 'failed' THEN 1 ELSE 0 END) as failed_count
                        FROM departments d
                        LEFT JOIN users u ON d.id = u.department_id
                        LEFT JOIN final_assessments fa ON u.id = fa.user_id
                        WHERE u.role = 'user'
                        GROUP BY d.id
                        ORDER BY d.name";
    $stmt_assessments = $pdo->query($sql_assessments);
    $response['assessmentData'] = $stmt_assessments->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard Data API Error: " . $e->getMessage());
    // Send empty data on error
}

echo json_encode($response);
?>
