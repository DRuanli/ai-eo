<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $data['test']->name; ?></h1>
        <div>
            <a href="<?php echo URLROOT; ?>/practice/edit/<?php echo $data['test']->id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="<?php echo URLROOT; ?>/practice" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Tests
            </a>
        </div>
    </div>
    
    <!-- Test Info -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Test Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($data['test']->test_date)); ?></p>
                    <p>
                        <strong>Overall Score:</strong> 
                        <?php if($data['overall_score']): ?>
                            <span class="badge bg-success fs-5"><?php echo $data['overall_score']; ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No scores yet</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <?php if(!empty($data['test']->notes)): ?>
                        <p><strong>Notes:</strong></p>
                        <div class="border p-2 rounded bg-light">
                            <?php echo nl2br($data['test']->notes); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section Scores -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Section Scores</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['scores'])): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Section</th>
                                        <th>Score</th>
                                        <th>Time Spent</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($data['scores'] as $score): ?>
                                        <tr>
                                            <td><strong><?php echo $score->section_name; ?></strong></td>
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
                                                <a href="<?php echo URLROOT; ?>/practice/sectionScores/<?php echo $score->section_id; ?>" class="btn btn-sm btn-outline-primary">
                                                    View History
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="sectionScoresChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No section scores have been recorded for this test yet. Edit the test to add scores.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Score Comparison -->
    <?php if($data['overall_score']): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Score Comparison</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-center mb-3">IELTS Band Score Distribution</h6>
                        <div class="card">
                            <div class="card-body p-2">
                                <div class="score-range">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Poor</small>
                                        <small>Fair</small>
                                        <small>Good</small>
                                        <small>Very Good</small>
                                        <small>Expert</small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" style="width: 22%;">0-2</div>
                                        <div class="progress-bar bg-warning" style="width: 22%;">2.5-4.5</div>
                                        <div class="progress-bar bg-info" style="width: 22%;">5-6</div>
                                        <div class="progress-bar bg-primary" style="width: 22%;">6.5-7.5</div>
                                        <div class="progress-bar bg-success" style="width: 12%;">8-9</div>
                                    </div>
                                    <div class="mt-2 d-flex justify-content-center">
                                        <?php
                                        $position = min(100, max(0, ($data['overall_score'] / 9) * 100));
                                        ?>
                                        <div style="position: relative; width: 100%; height: 25px;">
                                            <div style="position: absolute; left: <?php echo $position; ?>%; transform: translateX(-50%);">
                                                <i class="fas fa-arrow-up"></i><br>
                                                <span class="badge bg-dark"><?php echo $data['overall_score']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-center mb-3">Common University Requirements</h6>
                        <div class="card">
                            <div class="card-body p-3">
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Basic Admission Requirement
                                        <span class="badge bg-secondary">5.5 - 6.0</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Undergraduate Programs
                                        <span class="badge bg-primary">6.0 - 6.5</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Graduate Programs
                                        <span class="badge bg-info">6.5 - 7.0</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Competitive Programs
                                        <span class="badge bg-success">7.0 - 7.5</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Next Steps -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="card-title mb-0">Next Steps</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-bullseye"></i> Identify Weak Areas</h5>
                            <p class="card-text">Analyze your test performance and identify areas where you need improvement.</p>
                            <a href="<?php echo URLROOT; ?>/weak_areas/add" class="btn btn-outline-primary">Add Weak Area</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-tasks"></i> Set New Goals</h5>
                            <p class="card-text">Based on your current scores, set new target goals for each section.</p>
                            <a href="<?php echo URLROOT; ?>/goals/add" class="btn btn-outline-primary">Set Goals</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-book"></i> Study Resources</h5>
                            <p class="card-text">Find study resources to help you improve your weak areas.</p>
                            <a href="<?php echo URLROOT; ?>/resources" class="btn btn-outline-primary">Find Resources</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($data['scores'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for section scores chart
    const sections = [];
    const scores = [];
    
    <?php foreach($data['scores'] as $score): ?>
        sections.push('<?php echo $score->section_name; ?>');
        scores.push(<?php echo $score->score; ?>);
    <?php endforeach; ?>
    
    // Create section scores chart
    const ctx = document.getElementById('sectionScoresChart').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: sections,
            datasets: [{
                label: 'Section Scores',
                data: scores,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    min: 0,
                    max: 9,
                    ticks: {
                        stepSize: 1
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