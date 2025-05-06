<?php
    ob_start();
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-body bg-light">
            <h2 class="mb-4">Your Profile</h2>
            <div class="row">
                <div class="col-md-6">
                    <h4>Account Information</h4>
                    <p><strong>Username:</strong> <?php echo $data['user']->username; ?></p>
                    <p><strong>Email:</strong> <?php echo $data['user']->email; ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($data['user']->created_at)); ?></p>
                    
                    <h4 class="mt-4">IELTS Test Information</h4>
                    <?php if($data['user']->test_date): ?>
                        <p><strong>Test Date:</strong> <?php echo date('F j, Y', strtotime($data['user']->test_date)); ?></p>
                        <p><strong>Days Until Test:</strong> 
                            <?php if($data['daysUntilTest'] > 0): ?>
                                <span class="badge bg-primary"><?php echo $data['daysUntilTest']; ?> days</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Test date passed</span>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p><strong>Test Date:</strong> <span class="text-muted">Not set</span></p>
                    <?php endif; ?>
                    
                    <p><strong>Target Score:</strong> 
                        <?php if($data['user']->target_score): ?>
                            <span class="badge bg-success"><?php echo $data['user']->target_score; ?></span>
                        <?php else: ?>
                            <span class="text-muted">Not set</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="col-md-6">
                    <h4>Study Statistics</h4>
                    <p><strong>Total Study Time:</strong> 
                        <?php 
                            $hours = floor($data['totalStudyTime'] / 60);
                            $minutes = $data['totalStudyTime'] % 60;
                            echo $hours . ' hr ' . $minutes . ' min';
                        ?>
                    </p>
                    <p><strong>Practice Tests Taken:</strong> <?php echo $data['testCount']; ?></p>
                    
                    <?php if(!empty($data['studyTimePerSection'])): ?>
                        <h5 class="mt-3">Study Time by Section</h5>
                        <div class="chart-container mb-4" style="position: relative; height:200px;">
                            <canvas id="sectionTimeChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?php echo URLROOT; ?>/users/edit" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($data['studyTimePerSection'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for section time chart
    const sectionNames = [];
    const sectionTimes = [];
    const colors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)'
    ];
    
    <?php 
    $i = 0;
    foreach($data['studyTimePerSection'] as $section): 
        if($section->total_minutes > 0):
    ?>
        sectionNames.push('<?php echo $section->name; ?>');
        sectionTimes.push(<?php echo $section->total_minutes; ?>);
    <?php 
        $i++;
        endif;
    endforeach; 
    ?>
    
    // Create the chart
    const ctx = document.getElementById('sectionTimeChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: sectionNames,
            datasets: [{
                data: sectionTimes,
                backgroundColor: colors.slice(0, sectionNames.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const hours = Math.floor(value / 60);
                            const minutes = value % 60;
                            return context.label + ': ' + hours + 'h ' + minutes + 'm';
                        }
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