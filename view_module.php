<?php
// 1. Authentication and Initialization
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

// 2. Input Validation
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    redirect('dashboard.php');
}
$module_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // 3. Fetch all modules to determine the correct order and previous module
    $all_modules_stmt = $pdo->query("SELECT id, module_order FROM modules ORDER BY module_order ASC");
    $all_modules = $all_modules_stmt->fetchAll();

    // Find the current module and its index
    $current_module_index = -1;
    foreach ($all_modules as $index => $mod) {
        if ($mod['id'] == $module_id) {
            $current_module_index = $index;
            break;
        }
    }

    if ($current_module_index === -1) {
        // The requested module ID doesn't exist
        redirect('dashboard.php');
    }

    // 4. Fetch user's completed modules
    $stmt_progress = $pdo->prepare("SELECT module_id FROM user_progress WHERE user_id = :user_id");
    $stmt_progress->execute(['user_id' => $user_id]);
    $completed_modules = $stmt_progress->fetchAll(PDO::FETCH_COLUMN);

    // 5. Authorization Check: Is this module unlocked for the user?
    $is_completed = in_array($module_id, $completed_modules);
    $is_locked = true;

    if ($current_module_index === 0) {
        $is_locked = false; // First module is always unlocked
    } else {
        // Get the ID of the previous module from the ordered array
        $previous_module_id = $all_modules[$current_module_index - 1]['id'];
        if (in_array($previous_module_id, $completed_modules)) {
            $is_locked = false; // Unlocked if the previous one is complete
        }
    }
    
    if ($is_completed) {
        $is_locked = false;
    }

    if ($is_locked) {
        redirect('dashboard.php');
    }

    // 6. Fetch full details for the current module and its video
    $sql_module = "SELECT m.title, m.description, m.module_order, v.video_path 
                   FROM modules m
                   LEFT JOIN videos v ON m.id = v.module_id
                   WHERE m.id = :module_id";
    $stmt_module = $pdo->prepare($sql_module);
    $stmt_module->execute(['module_id' => $module_id]);
    $module = $stmt_module->fetch();

    // 7. Fetch Quiz Questions for this module
    $sql_questions = "SELECT q.id, q.question_text, q.question_type FROM questions q WHERE q.module_id = :module_id ORDER BY RAND() LIMIT 4";
    $stmt_questions = $pdo->prepare($sql_questions);
    $stmt_questions->execute(['module_id' => $module_id]);
    $questions = $stmt_questions->fetchAll();

    $question_ids = array_column($questions, 'id');
    $options = [];
    if (!empty($question_ids)) {
        $sql_options = "SELECT id, question_id, option_text FROM question_options WHERE question_id IN (" . implode(',', $question_ids) . ")";
        $stmt_options = $pdo->query($sql_options);
        while ($row = $stmt_options->fetch()) {
            $options[$row['question_id']][] = $row;
        }
    }

} catch (PDOException $e) {
    error_log("View Module Error: " . $e->getMessage());
    die("An error occurred while loading the module. Please try again later.");
}

$page_title = 'Module ' . escape($module['module_order']) . ': ' . escape($module['title']);
require_once 'includes/header.php';
?>

<style>
    #video-player-container:fullscreen #custom-controls {
        opacity: 1;
    }
</style>

<div class="bg-white shadow-md rounded-lg p-6">
    <!-- Video Player Section -->
    <div id="video-container">
        <h2 class="text-2xl font-bold text-gray-800 mb-4"><?= escape($module['title']) ?></h2>
        <p class="text-gray-600 mb-6"><?= escape($module['description']) ?></p>

        <?php if (empty($module['video_path'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                <p class="font-bold">Video Not Found</p>
                <p>The video for this module has not been uploaded yet. Please contact an administrator.</p>
            </div>
        <?php else: ?>
            <div id="video-player-container" class="relative bg-black rounded-lg overflow-hidden">
                <video id="learning-video" class="w-full h-auto max-h-[70vh]" data-module-id="<?= $module_id ?>">
                    <source src="uploads/videos/<?= escape($module['video_path']) ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                
                <!-- Custom Controls -->
                <div id="custom-controls" class="absolute bottom-0 left-0 right-0 h-14 bg-black bg-opacity-60 text-white flex items-center px-4 opacity-0 transition-opacity duration-300">
                    <button id="play-pause-btn" class="p-2">
                        <svg id="play-icon" class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>
                        <svg id="pause-icon" class="w-6 h-6 hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path></svg>
                    </button>
                    <div class="flex items-center mx-2 group">
                        <button id="mute-btn" class="p-2">
                            <svg id="volume-high-icon" class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"></path></svg>
                            <svg id="volume-off-icon" class="w-6 h-6 hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L7 9.01V11h1.73l4.01-4.01L12 4z"></path></svg>
                        </button>
                        <input id="volume-slider" type="range" min="0" max="1" step="0.1" value="1" class="w-0 group-hover:w-24 transition-all duration-300">
                    </div>
                    <div class="text-sm ml-2">
                        <span id="current-time">00:00</span> / <span id="duration">00:00</span>
                    </div>
                    <div class="flex-grow"></div>
                    <button id="fullscreen-btn" class="p-2">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"></path></svg>
                    </button>
                </div>
            </div>
            
            <?php if ($is_completed && !empty($questions)): ?>
                <div class="text-center mt-4">
                    <button id="review-quiz-btn" class="bg-gray-200 text-gray-800 font-semibold py-2 px-6 rounded-lg hover:bg-gray-300 transition-colors">Review Quiz</button>
                </div>
            <?php elseif (!$is_completed): ?>
                <div class="mt-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
                    <p>You must watch the entire video to unlock the quiz. The video progress bar is disabled.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Quiz Section - Always Hidden on Load -->
    <div id="quiz-container" class="mt-10 hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Module Test</h2>
        <p class="text-gray-600 mb-6">Answer the following questions to complete the module.</p>
        
        <?php if (empty($questions)): ?>
             <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                <p class="font-bold">No Test Required</p>
                <p>This module does not have a test. You can now proceed to the next module.</p>
                <a href="dashboard.php" class="mt-4 inline-block bg-primary text-white font-semibold py-2 px-4 rounded-lg hover:bg-primary-dark">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <form id="quiz-form">
                <input type="hidden" name="module_id" value="<?= $module_id ?>">
                <div class="space-y-8">
                    <?php foreach ($questions as $q_index => $question): ?>
                        <div class="question-block">
                            <p class="font-semibold text-lg text-gray-800"><?= ($q_index + 1) . '. ' . escape($question['question_text']) ?></p>
                            <div class="mt-4 space-y-2">
                                <?php foreach ($options[$question['id']] as $option): ?>
                                    <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="<?= $question['question_type'] === 'single' ? 'radio' : 'checkbox' ?>" 
                                               name="answers[<?= $question['id'] ?>]<?= $question['question_type'] === 'multiple' ? '[]' : '' ?>" 
                                               value="<?= $option['id'] ?>" 
                                               class="h-5 w-5 text-primary focus:ring-primary border-gray-300">
                                        <span class="ml-3 text-gray-700"><?= escape($option['option_text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-8">
                    <button type="submit" class="w-full bg-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-green-700 transition-colors">
                        Submit Answers
                    </button>
                </div>
            </form>
            <div id="quiz-result" class="mt-6 text-center"></div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoContainer = document.getElementById('video-player-container');
    const video = document.getElementById('learning-video');
    const customControls = document.getElementById('custom-controls');
    const playPauseBtn = document.getElementById('play-pause-btn');
    const playIcon = document.getElementById('play-icon');
    const pauseIcon = document.getElementById('pause-icon');
    const muteBtn = document.getElementById('mute-btn');
    const volumeHighIcon = document.getElementById('volume-high-icon');
    const volumeOffIcon = document.getElementById('volume-off-icon');
    const volumeSlider = document.getElementById('volume-slider');
    const currentTimeEl = document.getElementById('current-time');
    const durationEl = document.getElementById('duration');
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    const quizContainer = document.getElementById('quiz-container');
    const reviewQuizBtn = document.getElementById('review-quiz-btn');

    if (!video) return;

    video.controls = false;

    videoContainer.addEventListener('mouseenter', () => { customControls.style.opacity = '1'; });
    videoContainer.addEventListener('mouseleave', () => { if (!video.paused) customControls.style.opacity = '0'; });

    const togglePlay = () => { video.paused ? video.play() : video.pause(); };
    playPauseBtn.addEventListener('click', togglePlay);
    video.addEventListener('click', togglePlay);
    video.addEventListener('play', () => {
        playIcon.classList.add('hidden');
        pauseIcon.classList.remove('hidden');
    });
    video.addEventListener('pause', () => {
        pauseIcon.classList.add('hidden');
        playIcon.classList.remove('hidden');
        customControls.style.opacity = '1';
    });

    muteBtn.addEventListener('click', () => { video.muted = !video.muted; });
    video.addEventListener('volumechange', () => {
        volumeSlider.value = video.volume;
        if (video.muted || video.volume === 0) {
            volumeHighIcon.classList.add('hidden');
            volumeOffIcon.classList.remove('hidden');
        } else {
            volumeOffIcon.classList.add('hidden');
            volumeHighIcon.classList.remove('hidden');
        }
    });
    volumeSlider.addEventListener('input', (e) => {
        video.volume = e.target.value;
        video.muted = e.target.value == 0;
    });

    const formatTime = (timeInSeconds) => {
        const result = new Date(timeInSeconds * 1000).toISOString().substr(14, 5);
        return result;
    };
    video.addEventListener('loadedmetadata', () => {
        if(video.duration) durationEl.textContent = formatTime(video.duration);
    });
    video.addEventListener('timeupdate', () => {
        currentTimeEl.textContent = formatTime(video.currentTime);
    });

    fullscreenBtn.addEventListener('click', () => {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            videoContainer.requestFullscreen().catch(err => alert(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`));
        }
    });

    // --- ADDED: Seek Restriction Logic ---
    const isModuleCompleted = <?php echo json_encode($is_completed); ?>;
    if (!isModuleCompleted) {
        let lastPlayedTime = 0;
        video.addEventListener('timeupdate', () => {
            // Allow small skips but prevent large jumps
            if (!video.seeking && (video.currentTime > lastPlayedTime + 1.5)) {
                video.currentTime = lastPlayedTime;
            }
            lastPlayedTime = video.currentTime;
        });
    }

    const showQuiz = () => {
        if (quizContainer) {
            quizContainer.classList.remove('hidden');
            quizContainer.scrollIntoView({ behavior: 'smooth' });
        }
    };

    if (reviewQuizBtn) {
        reviewQuizBtn.addEventListener('click', showQuiz);
    }

    let progressTracked = false;
    video.addEventListener('ended', () => {
        showQuiz();
        if (progressTracked) return;
        
        const moduleId = video.dataset.moduleId;
        if (!moduleId) return;
        fetch('api/learning/track_progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ module_id: moduleId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                progressTracked = true;
            }
        });
    });

    const quizForm = document.getElementById('quiz-form');
    if (quizForm) {
        quizForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(quizForm);
            const resultDiv = document.getElementById('quiz-result');
            
            resultDiv.textContent = 'Submitting...';
            resultDiv.className = 'mt-6 text-center text-blue-600';

            fetch('api/learning/submit_quiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.textContent = 'Quiz submitted successfully! You will be redirected to the dashboard.';
                    resultDiv.className = 'mt-6 text-center text-green-600';
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    resultDiv.textContent = data.message || 'Failed to submit quiz. Please try again.';
                    resultDiv.className = 'mt-6 text-center text-red-600';
                }
            })
            .catch(error => {
                console.error('Quiz submission error:', error);
                resultDiv.textContent = 'A server error occurred.';
                resultDiv.className = 'mt-6 text-center text-red-600';
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
