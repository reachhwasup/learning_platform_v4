<?php
/**
 * Submit Quiz API Endpoint
 *
 * Receives and processes answers for an end-of-module quiz.
 * This script logs the answers but does not have a pass/fail score.
 * Completion is based on watching the video, this quiz is for reinforcement.
 */

header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// 1. Authenticate user and check request method
if (!is_logged_in()) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// 2. Get and validate input
if (!isset($_POST['module_id']) || !filter_var($_POST['module_id'], FILTER_VALIDATE_INT)) {
    $response['message'] = 'Module ID is missing.';
    echo json_encode($response);
    exit;
}
if (!isset($_POST['answers']) || !is_array($_POST['answers'])) {
    // It's okay if no answers are submitted, we can just proceed.
    // The main goal is completing the video.
    echo json_encode(['success' => true, 'message' => 'Quiz submitted successfully (no answers provided).']);
    exit;
}

$module_id = (int)$_POST['module_id'];
$user_answers = $_POST['answers'];
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    foreach ($user_answers as $question_id => $selected_options) {
        // Sanitize the question ID
        $question_id = (int)$question_id;

        // Normalize selected_options to be an array for consistent processing
        $selected_options = is_array($selected_options) ? $selected_options : [$selected_options];

        // Fetch the correct option(s) for the question
        $sql_correct = "SELECT id FROM question_options WHERE question_id = :question_id AND is_correct = 1";
        $stmt_correct = $pdo->prepare($sql_correct);
        $stmt_correct->execute(['question_id' => $question_id]);
        $correct_option_ids = $stmt_correct->fetchAll(PDO::FETCH_COLUMN);

        // Process each selected option from the user
        foreach ($selected_options as $selected_option_id) {
            $selected_option_id = (int)$selected_option_id;
            $is_correct = in_array($selected_option_id, $correct_option_ids);

            // Insert or update the user's answer into the database.
            // ON DUPLICATE KEY UPDATE handles cases where a user re-submits a quiz.
            $sql_insert = "INSERT INTO user_answers (user_id, question_id, selected_option_id, is_correct) 
                           VALUES (:user_id, :question_id, :selected_option_id, :is_correct)
                           ON DUPLICATE KEY UPDATE selected_option_id = VALUES(selected_option_id), is_correct = VALUES(is_correct)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                'user_id' => $user_id,
                'question_id' => $question_id,
                'selected_option_id' => $selected_option_id,
                'is_correct' => $is_correct ? 1 : 0
            ]);
        }
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Quiz submitted successfully.';

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Submit Quiz Error: " . $e->getMessage());
    $response['message'] = 'A server error occurred while submitting the quiz.';
}

echo json_encode($response);
?>
