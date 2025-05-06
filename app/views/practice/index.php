<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Practice Tests</h1>
        <a href="<?php echo URLROOT; ?>/practice/add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Test
        </a>
    </div>
    
    <!-- Test Score Summary -->
    <?php if(!empty($data['tests'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Test Score Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:300px;">
                            <canvas id="overallScoreChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th>Latest Score</th>
                                        <th>Best Score</th>
                                        <th>Tests Taken</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Calculate section statistics
                                    $sections = [
                                        1 => ['name' => 'Reading', 'latest' => 0, 'best' => 0, 'count' => 0],
                                        2 => ['name' => 'Writing', 'latest' => 0, 'best' => 0, 'count' => 0],
                                        3 => ['name' => 'Listening', 'latest' => 0, 'best' => 0, 'count' => 0],
                                        4 => ['name' => 'Speaking', 'latest' => 0, 'best' => 0, 'count' => 0]
                                    ];
                                    
                                    foreach($data['tests'] as $test) {
                                        // Get test scores
                                        $testScoreModel = new TestScore();
                                        $testScores = $testScoreModel->getTestScores($test->id);
                                        
                                        foreach($testScores as $score) {
                                            $sectionId = $score->section_id;
                                            
                                            // Update section statistics
                                            if(isset($sections[$sectionId])) {
                                                $sections[$sectionId]['count']++;
                                                
                                                // Update latest score
                                                if($sections[$sectionId]['count'] == 1) {
                                                    $sections[$sectionId]['latest'] = $score->score;
                                                }
                                                
                                                // Update best score
                                                if($score->score > $sections[$sectionId]['best']) {
                                                    $sections[$sectionId]['best'] = $score->score;
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Display section statistics
                                    foreach($sections as $sectionId => $section):
                                    ?>
                                        <tr>
                                            <td><?php echo $section['name']; ?></td>
                                            <td>
                                                <?php if($section['latest'] > 0): ?>
                                                    <span class="badge bg-primary"><?php echo $section['latest']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($section['best'] > 0): ?>
                                                    <span class="badge bg-success"><?php echo $section['best']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $section['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Test List -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Practice Test History</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['tests'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Test Name</th>
                                <th class="text-center">Reading</th>
                                <th class="text-center">Writing</th>
                                <th class="text-center">Listening</th>
                                <th class="text-center">Speaking</th>
                                <th class="text-center">Overall</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['tests'] as $test): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($test->test_date)); ?></td>
                                    <td><?php echo $test->name; ?></td>
                                    <?php 
                                    // Get test scores
                                    $testScoreModel = new TestScore();
                                    $testScores = $testScoreModel->getTestScores($test->id);
                                    
                                    $scores = [
                                        1 => null, // Reading
                                        2 => null, // Writing
                                        3 => null, // Listening
                                        4 => null  // Speaking
                                    ];
                                    
                                    foreach($testScores as $score) {
                                        $scores[$score->section_id] = $score->score;
                                    }
                                    
                                    // Display section scores
                                    foreach($scores as $sectionId => $score):
                                    ?>
                                        <td class="text-center">
                                            <?php if($score !== null): ?>
                                                <span class="badge bg-primary"><?php echo $score; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <td class="text-center">
                                        <?php if($test->overall_score): ?>
                                            <span class="badge bg-success"><?php echo $test->overall_score; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo URLROOT; ?>/practice/view/<?php echo $test->id; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo URLROOT; ?>/practice/edit/<?php echo $test->id; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $test->id; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $test->id; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $test->id; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $test->id; ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete the test "<?php echo $test->name; ?>"?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="<?php echo URLROOT; ?>/practice/delete/<?php echo $test->id; ?>" method="post">
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You haven't recorded any practice tests yet. Regular practice tests help track your progress and identify areas for improvement.</p>
                </div>
                <div class="text-center">
                    <a href="<?php echo URLROOT; ?>/practice/add" class="btn btn-primary">Add Your First Practice Test</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(!empty($data['tests'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for overall score chart
    const dates = [];
    const scores = [];
    
    <?php 
    $lastFiveDates = [];
    $lastFiveScores = [];
    $count = 0;
    
    foreach(array_reverse($data['tests']) as $test) {
        if($count < 5 && $test->overall_score) {
            $lastFiveDates[] = date('M j, Y', strtotime($test->test_date));
            $lastFiveScores[] = $test->overall_score;
            $count++;
        }
    }
    
    // Reverse to chronological order
    $lastFiveDates = array_reverse($lastFiveDates);
    $lastFiveScores = array_reverse($lastFiveScores);
    
    foreach($lastFiveDates as $date) {
        echo "dates.push('$date');\n";
    }
    
    foreach($lastFiveScores as $score) {
        echo "scores.push($score);\n";
    }
    ?>
    
    // Create overall score chart
    const ctx = document.getElementById('overallScoreChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Overall Score',
                data: scores,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(75, 192, 192, 1)',
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
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Overall Score Trend (Last 5 Tests)'
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