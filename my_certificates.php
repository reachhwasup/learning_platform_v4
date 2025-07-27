<?php
$page_title = 'My Certificates';
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch user's certificate data
try {
    $sql = "SELECT u.first_name, u.last_name, c.certificate_code, fa.score, fa.completed_at 
            FROM certificates c
            JOIN users u ON c.user_id = u.id
            JOIN final_assessments fa ON c.assessment_id = fa.id
            WHERE c.user_id = ? 
            ORDER BY fa.completed_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $certificate = $stmt->fetch();
} catch (PDOException $e) {
    error_log("My Certificates Page Error: " . $e->getMessage());
    $certificate = null;
}

require_once 'includes/header.php';
?>

<div class="bg-white shadow-md rounded-lg p-6">
    <?php if ($certificate): ?>
        <!-- This section is a visual preview of the certificate -->
        <div id="certificate-wrapper" class="border-4 border-primary p-8 bg-gray-50 relative">
            <div class="text-center">
                <h2 class="text-xl font-semibold text-gray-500 uppercase tracking-widest">Certificate of Completion</h2>
                <p class="text-lg mt-4">This certificate is proudly presented to</p>
                <h1 class="text-5xl font-bold text-primary my-6"><?= escape($certificate['first_name'] . ' ' . $certificate['last_name']) ?></h1>
                <p class="text-lg">For successfully completing the</p>
                <h3 class="text-2xl font-semibold text-gray-800 mt-2">Information Security Awareness Training</h3>
                <p class="text-lg mt-8">on</p>
                <p class="text-xl font-medium text-gray-700"><?= date('F j, Y', strtotime($certificate['completed_at'])) ?></p>
                
                <div class="mt-12 flex justify-between items-center text-sm text-gray-500">
                    <div>
                        <p class="border-t-2 border-gray-400 pt-2">Authorized Signature</p>
                    </div>
                    <div>
                        <p>Certificate ID: <?= escape($certificate['certificate_code']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-6">
            <!-- This button now links to the PDF generation script -->
            <a href="api/user/generate_certificate.php" target="_blank" class="bg-green-600 text-white font-semibold py-2 px-6 rounded-lg hover:bg-green-700 transition-colors">
                Download PDF Certificate
            </a>
        </div>
    <?php else: ?>
        <div class="text-center py-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">No Certificate Found</h2>
            <p class="text-gray-600 mb-6">You have not yet earned a certificate. Complete all modules and pass the final assessment to receive one.</p>
            <a href="final_assessment.php" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary-dark transition-colors">Go to Final Assessment</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
