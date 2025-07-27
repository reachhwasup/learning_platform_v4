<?php
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Validate assessment_id from URL
if (!isset($_GET['assessment_id']) || !filter_var($_GET['assessment_id'], FILTER_VALIDATE_INT)) {
    redirect('reports.php');
}
$assessment_id = (int)$_GET['assessment_id'];

try {
    // Fetch assessment and user details
    $sql_assessment = "SELECT u.first_name, u.last_name, u.email, fa.score, fa.status, fa.completed_at 
                       FROM final_assessments fa
                       JOIN users u ON fa.user_id = u.id
                       WHERE fa.id = ?";
    $stmt_assessment = $pdo->prepare($sql_assessment);
    $stmt_assessment->execute([$assessment_id]);
    $assessment = $stmt_assessment->fetch();

    if (!$assessment) {
        redirect('reports.php');
    }

    // Fetch all questions and user's answers for this specific assessment
    $sql_details = "SELECT 
                        q.id as question_id,
                        q.question_text, 
                        qo.id as option_id,
                        qo.option_text, 
                        qo.is_correct, 
                        (SELECT COUNT(*) FROM user_answers ua WHERE ua.assessment_id = ? AND ua.question_id = q.id AND ua.selected_option_id = qo.id) as was_selected
                    FROM questions q
                    JOIN question_options qo ON q.id = qo.question_id
                    WHERE q.id IN (SELECT DISTINCT question_id FROM user_answers WHERE assessment_id = ?)
                    ORDER BY q.id, qo.id";
    $stmt_details = $pdo->prepare($sql_details);
    $stmt_details->execute([$assessment_id, $assessment_id]);
    $details_raw = $stmt_details->fetchAll();

    // Group results by question ID for easier processing
    $details = [];
    foreach ($details_raw as $row) {
        $details[$row['question_id']]['question_text'] = $row['question_text'];
        $details[$row['question_id']]['options'][] = $row;
    }


} catch (PDOException $e) {
    error_log("View Exam Details Error: " . $e->getMessage());
    die("An error occurred while fetching exam details.");
}

$page_title = 'Exam Details for ' . escape($assessment['first_name'] . ' ' . $assessment['last_name']);
require_once 'includes/header.php';
?>
<div class="container mx-auto">
    <div class="mb-6">
        <a href="reports.php" class="text-primary hover:underline">&larr; Back to Reports</a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <div class="border-b border-gray-200 pb-4 mb-6 flex justify-between items-center">
            <div>
                <h3 class="text-2xl font-bold text-gray-900"><?= escape($assessment['first_name'] . ' ' . $assessment['last_name']) ?></h3>
                <p class="text-sm text-gray-500">Completed on: <?= date('M d, Y H:i', strtotime($assessment['completed_at'])) ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Final Score</p>
                <p class="text-3xl font-bold <?= $assessment['status'] === 'passed' ? 'text-green-600' : 'text-red-600' ?>"><?= (int)$assessment['score'] ?> Points</p>
            </div>
        </div>

        <div class="space-y-8">
            <?php if (empty($details)): ?>
                <p class="text-center text-gray-500 py-10">No detailed answers were recorded for this exam attempt.</p>
            <?php else: ?>
                <?php $question_number = 1; ?>
                <?php foreach ($details as $question_id => $question_data): ?>
                    <div class="question-block border-t pt-6">
                        <p class="font-semibold text-lg text-gray-800 mb-4"><?= $question_number++ . '. ' . escape($question_data['question_text']) ?></p>
                        <div class="space-y-2">
                            <?php foreach ($question_data['options'] as $option): ?>
                                <?php
                                    $li_class = 'p-3 border rounded-lg flex justify-between items-center';
                                    $badges = '';

                                    // Determine highlighting and badges
                                    if ($option['is_correct']) {
                                        $li_class .= ' bg-green-50 border-green-500 font-semibold';
                                        $badges .= '<span class="text-xs font-bold uppercase bg-green-200 text-green-800 px-2 py-1 rounded-full">Correct Answer</span>';
                                    }
                                    
                                    if ($option['was_selected']) {
                                        if (!$option['is_correct']) {
                                            $li_class .= ' bg-red-50 border-red-500'; // User's wrong choice
                                        }
                                        $badges .= '<span class="ml-2 text-xs font-bold uppercase bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Your Answer</span>';
                                    }
                                    
                                    if (!$option['is_correct'] && !$option['was_selected']) {
                                        $li_class .= ' border-gray-200';
                                    }
                                ?>
                                <div class="<?= $li_class ?>">
                                    <span class="text-gray-700"><?= escape($option['option_text']) ?></span>
                                    <div><?= $badges ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
