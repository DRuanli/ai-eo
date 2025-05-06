<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $data['goal']->section_name; ?> Goal Progress</h1>
        <a href="<?php echo URLROOT; ?>/goals" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Goals
        </a>
    </div>
    
    <!-- Goal Overview -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Goal Overview</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>
                        <strong><?php echo $data['goal']->section_name; ?> Target:</strong> 
                        <span class="badge bg-primary"><?php echo $data['goal']->target_score; ?></span>
                    </h4>
                    <h4>
                        <strong>Current Score:</strong> 
                        <span class="badge bg-info"><?php echo $data['currentScore']; ?></span>
                    </h4>
                    <h4>
                        <strong>Gap:</strong> 
                        <span class="badge bg-warning text-dark"><?php echo $data['scoreGap']; ?></span>
                    </h4>
                    <h4>
                        <strong>Target Date:</strong> 
                        <?php echo date('F j, Y', strtotime($data['goal']->target_date)); ?>
                        (<?php echo $data['daysRemaining']; ?> days remaining)
                    </h4>
                    <h4>
                        <strong>Status:</strong> 
                        <?php if($data['goal']->achieved): ?>
                            <span class="badge bg-success">Achieved</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">In Progress</span>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="col-md-6">
                    <h5 class="text-center mb-3">Progress Towards Goal</h5>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar <?php echo ($data['progressPercentage'] >= 100) ? 'bg-success' : 'bg-info'; ?>" 
                             role="progressbar" 
                             style="width: <?php echo $data['progressPercentage']; ?>%" 
                             aria-valuenow="<?php echo $data['progressPercentage']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo round($data['progressPercentage']); ?>%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>0</span>
                        <span><?php echo $data['goal']->target_score; ?></span>
                    </div>
                    
                    <?php if($data['daysRemaining'] > 0 && $data['scoreGap'] > 0): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> 
                            You need to improve by <strong><?php echo $data['scoreGap']; ?></strong> points in 
                            <strong><?php echo $data['daysRemaining']; ?></strong> days.
                            That's approximately <strong><?php echo round(($data['scoreGap'] / $data['daysRemaining']) * 100, 2); ?></strong> 
                            points per day.
                        </div>
                    <?php elseif($data['scoreGap'] <= 0): ?>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle"></i> 
                            Congratulations! You've reached your target score.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Your target date has passed. Consider updating your goal with a new target date.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Score History -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Score History</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['scoreHistory'])): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container" style="position: relative; height:400px;">
                            <canvas id="scoreHistoryChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($data['scoreHistory'] as $history): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($history['date'])); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $history['score']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No score history available for this goal yet. Take a practice test to track your progress.</p>
                </div>
                <div class="text-center">
                    <a href="<?php echo URLROOT; ?>/practice/add" class="btn btn-primary">Add Practice Test</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Improvement Strategies -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Improvement Strategies</h5>
        </div>
        <div class="card-body">
            <?php
            // Section-specific strategies
            $strategies = [];
            
            if($data['goal']->section_name == 'Reading' || $data['goal']->section_name == 'Overall') {
                $strategies[] = [
                    'title' => 'Reading Improvement',
                    'icon' => 'fas fa-book',
                    'tips' => [
                        'Practice skimming and scanning techniques daily',
                        'Build academic vocabulary through regular reading',
                        'Time yourself when doing practice tests',
                        'Read a variety of academic texts regularly'
                    ]
                ];
            }
            
            if($data['goal']->section_name == 'Writing' || $data['goal']->section_name == 'Overall') {
                $strategies[] = [
                    'title' => 'Writing Improvement',
                    'icon' => 'fas fa-pen',
                    'tips' => [
                        'Study model essays and response structures',
                        'Practice paraphrasing and using synonyms',
                        'Work on linking words and phrases',
                        'Get feedback on your writing regularly'
                    ]
                ];
            }
            
            if($data['goal']->section_name == 'Listening' || $data['goal']->section_name == 'Overall') {
                $strategies[] = [
                    'title' => 'Listening Improvement',
                    'icon' => 'fas fa-headphones',
                    'tips' => [
                        'Listen to a variety of English accents daily',
                        'Practice note-taking while listening',
                        'Focus on understanding main ideas first',
                        'Train your ear to catch specific details'
                    ]
                ];
            }
            
            if($data['goal']->section_name == 'Speaking' || $data['goal']->section_name == 'Overall') {
                $strategies[] = [
                    'title' => 'Speaking Improvement',
                    'icon' => 'fas fa-comments',
                    'tips' => [
                        'Record yourself speaking and analyze your performance',
                        'Practice speaking fluently without hesitation',
                        'Develop good examples for common topics',
                        'Work on pronunciation and intonation'
                    ]
                ];
            }
            ?>
            
            <div class="row">
                <?php foreach($strategies as $strategy): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="<?php echo $strategy['icon']; ?> me-2"></i>
                                    <?php echo $strategy['title']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <?php foreach($strategy['tips'] as $tip): ?>
                                        <li><?php echo $tip; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?php echo URLROOT; ?>/resources" class="btn btn-primary">Find Study Resources</a>
                <a href="<?php echo URLROOT; ?>/weak_areas" class="btn btn-warning">Manage Weak Areas</a>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($data['scoreHistory'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for score history chart
    const dates = [];
    const scores = [];
    
    <?php foreach($data['scoreHistory'] as $history): ?>
        dates.push('<?php echo date('M j, Y', strtotime($history['date'])); ?>');
        scores.push(<?php echo $history['score']; ?>);
    <?php endforeach; ?>
    
    // Create score history chart
    const ctx = document.getElementById('scoreHistoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: '<?php echo $data['goal']->section_name; ?> Score',
                    data: scores,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Target Score',
                    data: Array(dates.length).fill(<?php echo $data['goal']->target_score; ?>),
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false
                }
            ]
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
            },
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false
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