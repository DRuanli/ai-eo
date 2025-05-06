<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Study Analytics</h1>
        <a href="<?php echo URLROOT; ?>/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo URLROOT; ?>/dashboard/analytics" method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $data['startDate']; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $data['endDate']; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Test Score Trends -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Test Score Trends</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['scoresByDate'])): ?>
                <div class="chart-container" style="position: relative; height:400px;">
                    <canvas id="scoresTrendChart"></canvas>
                </div>
                
                <div class="mt-4">
                    <h6>Section Performance Analysis</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Strongest Section</h6>
                                    <p class="card-text">
                                        <span class="badge bg-success"><?php echo $data['sectionNames'][$data['strongestSection']]; ?></span>
                                        Average Score: <strong><?php echo number_format($data['sectionAvgScores'][$data['strongestSection']], 1); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Weakest Section</h6>
                                    <p class="card-text">
                                        <span class="badge bg-danger"><?php echo $data['sectionNames'][$data['weakestSection']]; ?></span>
                                        Average Score: <strong><?php echo number_format($data['sectionAvgScores'][$data['weakestSection']], 1); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No test scores available for the selected date range.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Study Time Analysis -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Study Time Analysis</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['weeklyStudyTime'])): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container" style="position: relative; height:300px;">
                            <canvas id="weeklyStudyChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container" style="position: relative; height:300px;">
                            <canvas id="sectionTimeChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>Study Sessions Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr>
                                <th>Total Sessions:</th>
                                <td><?php echo count($data['sessions']); ?></td>
                                <th>Total Study Time:</th>
                                <td>
                                    <?php 
                                        $totalMinutes = 0;
                                        foreach($data['sessions'] as $session) {
                                            $totalMinutes += $session->duration ?: 0;
                                        }
                                        $hours = floor($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;
                                        echo "{$hours} hr {$minutes} min";
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Average Session Length:</th>
                                <td>
                                    <?php 
                                        $avgMinutes = count($data['sessions']) > 0 ? $totalMinutes / count($data['sessions']) : 0;
                                        echo round($avgMinutes) . ' min';
                                    ?>
                                </td>
                                <th>Average Study Time per Week:</th>
                                <td>
                                    <?php 
                                        $weeks = max(1, count($data['weeklyStudyTime']));
                                        $avgWeekMinutes = $totalMinutes / $weeks;
                                        $avgWeekHours = floor($avgWeekMinutes / 60);
                                        $avgWeekMins = round($avgWeekMinutes % 60);
                                        echo "{$avgWeekHours} hr {$avgWeekMins} min";
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No study sessions available for the selected date range.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Detailed Session List -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Study Session Details</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['sessions'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Section</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['sessions'] as $session): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($session->start_time)); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $session->section_name; ?></span></td>
                                    <td>
                                        <?php if($session->duration): ?>
                                            <?php 
                                                $hours = floor($session->duration / 60);
                                                $minutes = $session->duration % 60;
                                                echo ($hours > 0 ? $hours . ' hr ' : '') . $minutes . ' min';
                                            ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo URLROOT; ?>/study_sessions/view/<?php echo $session->id; ?>" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No study sessions available for the selected date range.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(!empty($data['scoresByDate'])): ?>
    // Prepare data for scores trend chart
    const scoreDates = [];
    const overallScores = [];
    const readingScores = [];
    const writingScores = [];
    const listeningScores = [];
    const speakingScores = [];
    
    <?php 
    foreach($data['scoresByDate'] as $date => $scoreData): 
        echo "scoreDates.push('".date('M j, Y', strtotime($date))."');\n";
        echo "overallScores.push(".$scoreData['overall'].");\n";
        
        $r = 'null'; $w = 'null'; $l = 'null'; $s = 'null';
        foreach($scoreData['scores'] as $score) {
            if($score->section_id == 1) $r = $score->score;
            if($score->section_id == 2) $w = $score->score;
            if($score->section_id == 3) $l = $score->score;
            if($score->section_id == 4) $s = $score->score;
        }
        
        echo "readingScores.push($r);\n";
        echo "writingScores.push($w);\n";
        echo "listeningScores.push($l);\n";
        echo "speakingScores.push($s);\n";
    endforeach; 
    ?>
    
    // Create scores trend chart
    const scoresCtx = document.getElementById('scoresTrendChart').getContext('2d');
    new Chart(scoresCtx, {
        type: 'line',
        data: {
            labels: scoreDates,
            datasets: [
                {
                    label: 'Overall',
                    data: overallScores,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Reading',
                    data: readingScores,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Writing',
                    data: writingScores,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Listening',
                    data: listeningScores,
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Speaking',
                    data: speakingScores,
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.1
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
    <?php endif; ?>
    
    <?php if(!empty($data['weeklyStudyTime'])): ?>
    // Prepare data for weekly study chart
    const weeks = [];
    const weeklyMinutes = [];
    
    <?php 
    foreach($data['weeklyStudyTime'] as $date => $minutes): 
        // Format the date as week of year
        $weekStart = date('M j', strtotime($date));
        $weekEnd = date('M j', strtotime($date . ' +6 days'));
        echo "weeks.push('$weekStart - $weekEnd');\n";
        echo "weeklyMinutes.push($minutes);\n";
    endforeach; 
    ?>
    
    // Create weekly study chart
    const weeklyCtx = document.getElementById('weeklyStudyChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: weeks,
            datasets: [{
                label: 'Study Time (minutes)',
                data: weeklyMinutes,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Minutes'
                    }
                }
            }
        }
    });
    
    // Prepare data for section time chart
    const sectionNames = [];
    const sectionMinutes = [];
    const sectionColors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(40, 167, 69, 0.7)',
        'rgba(220, 53, 69, 0.7)',
        'rgba(255, 193, 7, 0.7)'
    ];
    
    <?php 
    foreach($data['studyTimeBySection'] as $index => $section): 
        if($section->total_minutes > 0): // Only include sections with study time
            echo "sectionNames.push('".$section->name."');\n";
            echo "sectionMinutes.push(".$section->total_minutes.");\n";
        endif;
    endforeach; 
    ?>
    
    // Create section time chart
    const sectionCtx = document.getElementById('sectionTimeChart').getContext('2d');
    new Chart(sectionCtx, {
        type: 'doughnut',
        data: {
            labels: sectionNames,
            datasets: [{
                data: sectionMinutes,
                backgroundColor: sectionColors.slice(0, sectionNames.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const minutes = context.raw;
                            const hours = Math.floor(minutes / 60);
                            const mins = minutes % 60;
                            return context.label + ': ' + hours + 'h ' + mins + 'm';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>