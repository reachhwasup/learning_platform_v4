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
                        q.question_text, 
                        qo.option_text, 
                        qo.is_correct, 
                        (SELECT COUNT(*) FROM user_answers ua WHERE ua.assessment_id = ? AND ua.question_id = q.id AND ua.selected_option_id = qo.id) as was_selected
                    FROM questions q
                    JOIN question_options qo ON q.id = qo.question_id
                    WHERE q.id IN (SELECT DISTINCT question_id FROM user_answers WHERE assessment_id = ?)
                    ORDER BY q.id, qo.id";
    $stmt_details = $pdo->prepare($sql_details);
    $stmt_details->execute([$assessment_id, $assessment_id]);
    $details = $stmt_details->fetchAll(PDO::FETCH_GROUP); // Group results by question_text

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
                <p class="text-3xl font-bold <?= $assessment['status'] === 'passed' ? 'text-green-600' : 'text-red-600' ?>"><?= escape($assessment['score']) ?> Points</p>
            </div>
        </div>

        <div class="space-y-8">
            <?php if (empty($details)): ?>
                <p class="text-center text-gray-500 py-10">No detailed answers were recorded for this exam attempt. This may be because it was taken with an older version of the system.</p>
            <?php else: ?>
                <?php foreach ($details as $question_text => $options): ?>
                    <div class="question-block">
                        <p class="font-semibold text-lg text-gray-800 mb-4"><?= escape($question_text) ?></p>
                        <div class="space-y-2">
                            <?php foreach ($options as $option): ?>
                                <?php
                                    $li_class = 'p-3 border rounded-lg flex justify-between items-center';
                                    $user_pick_badge = '';

                                    if ($option['was_selected']) {
                                        $user_pick_badge = '<span class="text-xs font-bold uppercase bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Your Answer</span>';
                                    }

                                    if ($option['is_correct']) {
                                        $li_class .= ' bg-green-50 border-green-500';
                                    } elseif ($option['was_selected'] && !$option['is_correct']) {
                                        $li_class .= ' bg-red-50 border-red-500';
                                    } else {
                                        $li_class .= ' border-gray-200';
                                    }
                                ?>
                                <div class="<?= $li_class ?>">
                                    <span class="text-gray-700"><?= escape($option['option_text']) ?></span>
                                    <?= $user_pick_badge ?>
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
