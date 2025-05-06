<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Progress Overview</h1>
        <a href="<?php echo URLROOT; ?>/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    
    <!-- Section Scores Chart -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">IELTS Section Scores Over Time</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height:400px;">
                <canvas id="scoresChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- IELTS Section Progress -->
    <div class="row">
        <?php 
        $sectionColors = [
            'Reading' => 'primary',
            'Writing' => 'success',
            'Listening' => 'danger',
            'Speaking' => 'warning'
        ];
        
        foreach($data['sectionNames'] as $id => $name): 
            $color = $sectionColors[$name] ?? 'secondary';
        ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-<?php echo $color; ?> text-white">
                    <h5 class="card-title mb-0"><?php echo $name; ?> Section Progress</h5>
                </div>
                <div class="card-body">
                    <h6>Latest Score: 
                        <?php 
                        $latestScore = 'No scores yet';
                        $latestDate = '';
                        
                        switch($id) {
                            case 1:
                                if(!empty($data['readingScores'])) {
                                    $latestScore = $data['readingScores'][0]->score;
                                    $latestDate = date('M j, Y', strtotime($data['readingScores'][0]->test_date));
                                }
                                break;
                            case 2:
                                if(!empty($data['writingScores'])) {
                                    $latestScore = $data['writingScores'][0]->score;
                                    $latestDate = date('M j, Y', strtotime($data['writingScores'][0]->test_date));
                                }
                                break;
                            case 3:
                                if(!empty($data['listeningScores'])) {
                                    $latestScore = $data['listeningScores'][0]->score;
                                    $latestDate = date('M j, Y', strtotime($data['listeningScores'][0]->test_date));
                                }
                                break;
                            case 4:
                                if(!empty($data['speakingScores'])) {
                                    $latestScore = $data['speakingScores'][0]->score;
                                    $latestDate = date('M j, Y', strtotime($data['speakingScores'][0]->test_date));
                                }
                                break;
                        }
                        ?>
                        
                        <?php if($latestScore !== 'No scores yet'): ?>
                            <span class="badge bg-<?php echo $color; ?>"><?php echo $latestScore; ?></span>
                            <small class="text-muted">(<?php echo $latestDate; ?>)</small>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo $latestScore; ?></span>
                        <?php endif; ?>
                    </h6>
                    
                    <h6 class="mt-3">Study Time: 
                        <?php
                        $totalTime = 0;
                        foreach($data['studyTimeBySection'] as $section) {
                            if($section->id == $id) {
                                $totalTime = $section->total_minutes;
                                break;
                            }
                        }
                        
                        $hours = floor($totalTime / 60);
                        $minutes = $totalTime % 60;
                        echo "<span class='badge bg-{$color}'>{$hours} hr {$minutes} min</span>";
                        ?>
                    </h6>
                    
                    <h6 class="mt-3">Weak Areas: 
                        <?php
                        $weakCount = 0;
                        foreach($data['weakAreasBySection'] as $section) {
                            if($section->id == $id) {
                                $weakCount = $section->weak_area_count;
                                break;
                            }
                        }
                        
                        echo "<span class='badge bg-{$color}'>{$weakCount}</span>";
                        ?>
                    </h6>
                    
                    <div class="mt-3">
                        <a href="<?php echo URLROOT; ?>/practice/sectionScores/<?php echo $id; ?>" class="btn btn-sm btn-outline-<?php echo $color; ?>">View Scores</a>
                        <a href="<?php echo URLROOT; ?>/weak_areas/bySection/<?php echo $id; ?>" class="btn btn-sm btn-outline-<?php echo $color; ?>">View Weak Areas</a>
                        <a href="<?php echo URLROOT; ?>/study_sessions/bySection/<?php echo $id; ?>" class="btn btn-sm btn-outline-<?php echo $color; ?>">View Study Sessions</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Recent Tests -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Recent Practice Tests</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['tests'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Test Name</th>
                                <th>Overall Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $displayCount = 0;
                            foreach($data['tests'] as $test): 
                                if($displayCount >= 5) break; // Show only the 5 most recent tests
                            ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($test->test_date)); ?></td>
                                    <td><?php echo $test->name; ?></td>
                                    <td>
                                        <?php if(isset($test->overall_score)): ?>
                                            <span class="badge bg-primary"><?php echo $test->overall_score; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo URLROOT; ?>/practice/view/<?php echo $test->id; ?>" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            <?php 
                                $displayCount++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo URLROOT; ?>/practice" class="btn btn-outline-info">View All Tests</a>
                    <a href="<?php echo URLROOT; ?>/practice/add" class="btn btn-primary">Add New Test</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You haven't recorded any practice tests yet.</p>
                    <a href="<?php echo URLROOT; ?>/practice/add" class="btn btn-primary">Add Practice Test</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Goal Progress -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Goal Progress</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['goalProgress'])): ?>
                <div class="row">
                    <?php foreach($data['goalProgress'] as $goal): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6><?php echo $goal->section_name; ?> Score Goal</h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Current: 
                                            <?php if($goal->current_score): ?>
                                                <span class="badge bg-info"><?php echo number_format($goal->current_score, 1); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No score</span>
                                            <?php endif; ?>
                                        </span>
                                        <span>Target: <span class="badge bg-success"><?php echo $goal->target_score; ?></span></span>
                                    </div>
                                    <?php 
                                    $progress = 0;
                                    if($goal->current_score && $goal->target_score) {
                                        $progress = min(100, ($goal->current_score / $goal->target_score) * 100);
                                    }
                                    ?>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" 
                                             aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="mt-2 small">
                                        <strong>Target Date:</strong> <?php echo date('M j, Y', strtotime($goal->target_date)); ?>
                                        (<?php echo $goal->days_remaining > 0 ? $goal->days_remaining . ' days left' : 'Past due'; ?>)
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo URLROOT; ?>/goals" class="btn btn-outline-success">View All Goals</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You don't have any goals set up yet.</p>
                    <a href="<?php echo URLROOT; ?>/goals/add" class="btn btn-primary">Add Goal</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for scores chart
    const dates = [];
    const readingScores = [];
    const writingScores = [];
    const listeningScores = [];
    const speakingScores = [];
    
    <?php
    // Get all test dates
    $allDates = [];
    
    if(!empty($data['readingScores'])) {
        foreach($data['readingScores'] as $score) {
            if(!in_array($score->test_date, $allDates)) {
                $allDates[] = $score->test_date;
            }
        }
    }
    
    if(!empty($data['writingScores'])) {
        foreach($data['writingScores'] as $score) {
            if(!in_array($score->test_date, $allDates)) {
                $allDates[] = $score->test_date;
            }
        }
    }
    
    if(!empty($data['listeningScores'])) {
        foreach($data['listeningScores'] as $score) {
            if(!in_array($score->test_date, $allDates)) {
                $allDates[] = $score->test_date;
            }
        }
    }
    
    if(!empty($data['speakingScores'])) {
        foreach($data['speakingScores'] as $score) {
            if(!in_array($score->test_date, $allDates)) {
                $allDates[] = $score->test_date;
            }
        }
    }
    
    // Sort dates in ascending order
    sort($allDates);
    
    // Output dates
    foreach($allDates as $date) {
        echo "dates.push('" . date('M j, Y', strtotime($date)) . "');\n";
        
        // Find scores for each section on this date
        $reading = 'null';
        $writing = 'null';
        $listening = 'null';
        $speaking = 'null';
        
        if(!empty($data['readingScores'])) {
            foreach($data['readingScores'] as $score) {
                if($score->test_date === $date) {
                    $reading = $score->score;
                    break;
                }
            }
        }
        
        if(!empty($data['writingScores'])) {
            foreach($data['writingScores'] as $score) {
                if($score->test_date === $date) {
                    $writing = $score->score;
                    break;
                }
            }
        }
        
        if(!empty($data['listeningScores'])) {
            foreach($data['listeningScores'] as $score) {
                if($score->test_date === $date) {
                    $listening = $score->score;
                    break;
                }
            }
        }
        
        if(!empty($data['speakingScores'])) {
            foreach($data['speakingScores'] as $score) {
                if($score->test_date === $date) {
                    $speaking = $score->score;
                    break;
                }
            }
        }
        
        echo "readingScores.push($reading);\n";
        echo "writingScores.push($writing);\n";
        echo "listeningScores.push($listening);\n";
        echo "speakingScores.push($speaking);\n";
    }
    ?>
    
    // Create the scores chart
    const ctx = document.getElementById('scoresChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Reading',
                    data: readingScores,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.2
                },
                {
                    label: 'Writing',
                    data: writingScores,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    tension: 0.2
                },
                {
                    label: 'Listening',
                    data: listeningScores,
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    tension: 0.2
                },
                {
                    label: 'Speaking',
                    data: speakingScores,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    tension: 0.2
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
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if(context.raw === null) {
                                return context.dataset.label + ': No score';
                            }
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>