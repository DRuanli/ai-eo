<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Study Goals</h1>
        <a href="<?php echo URLROOT; ?>/goals/add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Goal
        </a>
    </div>
    
    <!-- Current Scores Summary -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Current Scores</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th>Current Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['sections'] as $section): ?>
                                    <tr>
                                        <td><?php echo $section->name; ?></td>
                                        <td>
                                            <?php if(isset($data['currentScores'][$section->id])): ?>
                                                <span class="badge bg-primary"><?php echo $data['currentScores'][$section->id]; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No score</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td><strong>Overall</strong></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $data['overallScore']; ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container" style="position: relative; height:200px;">
                        <canvas id="currentScoresChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Goals List -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Your Goals</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['goals'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Target Score</th>
                                <th>Target Date</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['goals'] as $goal): ?>
                                <tr>
                                    <td><?php echo $goal->section_name; ?></td>
                                    <td><span class="badge bg-primary"><?php echo $goal->target_score; ?></span></td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($goal->target_date)); ?>
                                        <?php
                                        $daysLeft = floor((strtotime($goal->target_date) - time()) / (60 * 60 * 24));
                                        if($daysLeft > 0) {
                                            echo "<span class='badge bg-info'>$daysLeft days left</span>";
                                        } else {
                                            echo "<span class='badge bg-danger'>Past due</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($goal->achieved): ?>
                                            <span class="badge bg-success">Achieved</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="width: 150px;">
                                        <?php
                                        // Calculate progress
                                        $currentScore = isset($goal->section_id) ? 
                                            ($data['currentScores'][$goal->section_id] ?? 0) : 
                                            $data['overallScore'];
                                            
                                        $progress = min(100, round(($currentScore / $goal->target_score) * 100));
                                        
                                        $progressClass = 'bg-danger';
                                        if($progress >= 90) $progressClass = 'bg-success';
                                        elseif($progress >= 75) $progressClass = 'bg-info';
                                        elseif($progress >= 50) $progressClass = 'bg-warning';
                                        ?>
                                        
                                        <div class="progress">
                                            <div class="progress-bar <?php echo $progressClass; ?>" role="progressbar" 
                                                style="width: <?php echo $progress; ?>%" 
                                                aria-valuenow="<?php echo $progress; ?>" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $progress; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo URLROOT; ?>/goals/progress/<?php echo $goal->id; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                            <a href="<?php echo URLROOT; ?>/goals/edit/<?php echo $goal->id; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $goal->id; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <?php if(!$goal->achieved): ?>
                                            <form action="<?php echo URLROOT; ?>/goals/markAchieved/<?php echo $goal->id; ?>/1" method="post" class="d-inline ms-2">
                                                <button type="submit" class="btn btn-sm btn-success" title="Mark as Achieved">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?php echo URLROOT; ?>/goals/markAchieved/<?php echo $goal->id; ?>/0" method="post" class="d-inline ms-2">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Mark as Not Achieved">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $goal->id; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $goal->id; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $goal->id; ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete the <?php echo $goal->section_name; ?> goal (target: <?php echo $goal->target_score; ?>)?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="<?php echo URLROOT; ?>/goals/delete/<?php echo $goal->id; ?>" method="post">
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
                    <p>You haven't set any goals yet. Setting specific goals will help you track your progress and stay motivated.</p>
                </div>
                <div class="text-center">
                    <a href="<?php echo URLROOT; ?>/goals/add" class="btn btn-primary">Set Your First Goal</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(!empty($data['currentScores'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for current scores chart
    const sections = [];
    const scores = [];
    
    <?php 
    foreach($data['sections'] as $section): 
        if(isset($data['currentScores'][$section->id])):
    ?>
        sections.push('<?php echo $section->name; ?>');
        scores.push(<?php echo $data['currentScores'][$section->id]; ?>);
    <?php 
        endif;
    endforeach; 
    ?>
    
    // Create current scores chart
    const ctx = document.getElementById('currentScoresChart').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: sections,
            datasets: [{
                label: 'Current Scores',
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