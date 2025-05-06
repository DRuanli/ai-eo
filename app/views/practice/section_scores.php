<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $data['section']->name; ?> Score History</h1>
        <a href="<?php echo URLROOT; ?>/practice" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Tests
        </a>
    </div>
    
    <!-- Score Trend Chart -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><?php echo $data['section']->name; ?> Score Trend</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['scores'])): ?>
                <div class="chart-container" style="position: relative; height:400px;">
                    <canvas id="scoreTrendChart"></canvas>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Latest Score</h5>
                                <h2 class="card-text">
                                    <span class="badge bg-primary"><?php echo $data['scores'][0]->score; ?></span>
                                </h2>
                                <p class="text-muted"><?php echo date('M j, Y', strtotime($data['scores'][0]->test_date)); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Average Score</h5>
                                <h2 class="card-text">
                                    <?php 
                                    $totalScore = 0;
                                    foreach($data['scores'] as $score) {
                                        $totalScore += $score->score;
                                    }
                                    $avgScore = count($data['scores']) > 0 ? round($totalScore / count($data['scores']), 1) : 0;
                                    ?>
                                    <span class="badge bg-info"><?php echo $avgScore; ?></span>
                                </h2>
                                <p class="text-muted">From <?php echo count($data['scores']); ?> tests</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Best Score</h5>
                                <h2 class="card-text">
                                    <?php 
                                    $bestScore = 0;
                                    foreach($data['scores'] as $score) {
                                        if($score->score > $bestScore) {
                                            $bestScore = $score->score;
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-success"><?php echo $bestScore; ?></span>
                                </h2>
                                <p class="text-muted">Your highest achievement</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No score history available for the <?php echo $data['section']->name; ?> section yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Score Details Table -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Score Details</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['scores'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Test Name</th>
                                <th>Score</th>
                                <th>Time Spent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['scores'] as $score): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($score->test_date)); ?></td>
                                    <td>
                                        <?php
                                        // Get test details
                                        $practiceTestModel = new PracticeTest();
                                        $test = $practiceTestModel->getTestById($score->practice_test_id);
                                        echo $test ? $test->name : 'Unknown Test';
                                        ?>
                                    </td>
                                    <td><span class="badge bg-primary"><?php echo $score->score; ?></span></td>
                                    <td>
                                        <?php 
                                        if($score->time_spent) {
                                            $hours = floor($score->time_spent / 60);
                                            $minutes = $score->time_spent % 60;
                                            echo ($hours > 0 ? $hours . ' hr ' : '') . $minutes . ' min';
                                        } else {
                                            echo '<span class="text-muted">Not recorded</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo URLROOT; ?>/practice/view/<?php echo $score->practice_test_id; ?>" class="btn btn-sm btn-info">
                                            View Test
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No score history available for the <?php echo $data['section']->name; ?> section yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Improvement Strategies -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Improvement Strategies for <?php echo $data['section']->name; ?></h5>
        </div>
        <div class="card-body">
            <?php
            // Section-specific strategies
            $strategies = [];
            
            switch($data['section']->id) {
                case 1: // Reading
                    $strategies = [
                        'Practice skimming techniques to quickly identify main ideas',
                        'Improve scanning skills to locate specific information',
                        'Expand your vocabulary, especially academic words',
                        'Practice identifying paragraph topics and main ideas',
                        'Work on time management - allocate specific time for each passage'
                    ];
                    break;
                case 2: // Writing
                    $strategies = [
                        'Analyze model answers to understand structure requirements',
                        'Practice paraphrasing and vocabulary variation',
                        'Improve your grammar, especially complex sentences',
                        'Work on coherence and cohesion through linking words',
                        'Practice time management - 20 minutes for Task 1, 40 minutes for Task 2'
                    ];
                    break;
                case 3: // Listening
                    $strategies = [
                        'Practice note-taking while listening',
                        'Focus on understanding different accents',
                        'Improve prediction skills before listening',
                        'Practice identifying signpost words and phrases',
                        'Work on spelling of commonly misspelled words'
                    ];
                    break;
                case 4: // Speaking
                    $strategies = [
                        'Record yourself speaking and analyze your performance',
                        'Practice speaking fluently without long pauses',
                        'Expand your vocabulary for common topics',
                        'Work on pronunciation of difficult sounds',
                        'Practice developing answers with examples and explanations'
                    ];
                    break;
            }
            ?>
            
            <div class="row">
                <?php foreach($strategies as $index => $strategy): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-check-circle text-success"></i> Strategy <?php echo $index + 1; ?></h5>
                                <p class="card-text"><?php echo $strategy; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-3 text-center">
                <a href="<?php echo URLROOT; ?>/resources" class="btn btn-primary">Find Study Resources</a>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($data['scores'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for score trend chart
    const dates = [];
    const scores = [];
    
    <?php 
    // Use array_reverse to show oldest to newest
    foreach(array_reverse($data['scores']) as $score): 
    ?>
        dates.push('<?php echo date('M j, Y', strtotime($score->test_date)); ?>');
        scores.push(<?php echo $score->score; ?>);
    <?php endforeach; ?>
    
    // Create score trend chart
    const ctx = document.getElementById('scoreTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: '<?php echo $data['section']->name; ?> Score',
                data: scores,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 0,
                    max: 9,
                    ticks: {
                        stepSize: 0.5
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>