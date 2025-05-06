<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>IELTS Test Preparation Summary</h1>
        <a href="<?php echo URLROOT; ?>/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    
    <!-- Test Information Banner -->
    <div class="card mb-4 bg-primary text-white">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <h2 class="display-4"><?php echo $data['daysRemaining']; ?></h2>
                    <h3>Days Remaining</h3>
                </div>
                <div class="col-md-8">
                    <h4><i class="fas fa-calendar"></i> Test Date: <?php echo date('F j, Y', strtotime($data['user']->test_date)); ?></h4>
                    <h4><i class="fas fa-star"></i> Target Score: <?php echo $data['user']->target_score ?: 'Not set'; ?></h4>
                    <?php if($data['daysRemaining'] < 7): ?>
                        <div class="alert alert-danger mt-2 mb-0">
                            <strong>Test day is approaching!</strong> Focus on review and practice tests now.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Current Score Summary -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Current Scores & Study Needs</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Section</th>
                            <th>Current Score</th>
                            <th>Target Score</th>
                            <th>Gap</th>
                            <th>Recommended Study Hours</th>
                            <th>Minutes Per Day</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['studyTimeNeeded'] as $sectionId => $section): ?>
                            <tr>
                                <td><strong><?php echo $section['name']; ?></strong></td>
                                <td>
                                    <?php if($section['current_score'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo $section['current_score']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No score</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-success"><?php echo $section['target_score']; ?></span></td>
                                <td>
                                    <?php if($section['score_gap'] > 0): ?>
                                        <span class="badge bg-warning text-dark">+<?php echo $section['score_gap']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Target reached</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $section['recommended_hours']; ?> hours</td>
                                <td>
                                    <?php if($section['daily_minutes'] > 0): ?>
                                        <span class="<?php echo $section['daily_minutes'] > 60 ? 'text-danger' : 'text-success'; ?>">
                                            <strong><?php echo $section['daily_minutes']; ?> min</strong>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success">No additional study needed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-info mt-3">
                <p><strong>Note:</strong> Recommended study hours are calculated based on a general guideline of approximately 20 hours of focused study per 1.0 band score improvement needed.</p>
            </div>
        </div>
    </div>
    
    <!-- Weak Areas to Focus On -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Weak Areas to Focus On</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($data['weakAreas'])): ?>
                        <ul class="list-group">
                            <?php foreach($data['weakAreas'] as $area): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-secondary me-2"><?php echo $area->section_name; ?></span>
                                        <strong><?php echo $area->sub_skill; ?></strong>
                                    </div>
                                    <span class="badge bg-danger rounded-pill">
                                        <?php for($i = 0; $i < $area->priority; $i++) echo '★'; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3 text-center">
                            <a href="<?php echo URLROOT; ?>/weak_areas" class="btn btn-outline-danger">Manage Weak Areas</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>You haven't identified any weak areas yet. Adding weak areas helps focus your study efforts effectively.</p>
                            <a href="<?php echo URLROOT; ?>/weak_areas/add" class="btn btn-primary">Add Weak Area</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">Active Goals</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($data['goals'])): ?>
                        <ul class="list-group">
                            <?php foreach($data['goals'] as $goal): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-secondary me-2">
                                            <?php echo isset($goal->section_name) ? $goal->section_name : 'Overall'; ?>
                                        </span>
                                        <strong>Target: <?php echo $goal->target_score; ?></strong>
                                    </div>
                                    <span class="badge <?php echo $goal->achieved ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                        <?php echo date('M j, Y', strtotime($goal->target_date)); ?>
                                        (<?php 
                                            $daysLeft = floor((strtotime($goal->target_date) - time()) / (60 * 60 * 24));
                                            echo $daysLeft > 0 ? $daysLeft . ' days left' : 'Due now'; 
                                        ?>)
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3 text-center">
                            <a href="<?php echo URLROOT; ?>/goals" class="btn btn-outline-warning">Manage Goals</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>You don't have any active goals set. Setting specific goals helps track your progress.</p>
                            <a href="<?php echo URLROOT; ?>/goals/add" class="btn btn-primary">Add Goal</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Study Plan -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Upcoming Study Plan</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['upcomingItems'])): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Section</th>
                                <th>Task</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['upcomingItems'] as $item): ?>
                                <tr>
                                    <td><?php echo date('M j (D)', strtotime($item->scheduled_date)); ?></td>
                                    <td>
                                        <?php if(isset($item->section_name)): ?>
                                            <span class="badge bg-secondary"><?php echo $item->section_name; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $item->title; ?></td>
                                    <td><?php echo $item->duration; ?> min</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <a href="<?php echo URLROOT; ?>/study_plans" class="btn btn-outline-info">View Full Study Plan</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You don't have any upcoming study tasks planned. Creating a study plan helps organize your preparation.</p>
                    <div class="mt-2">
                        <a href="<?php echo URLROOT; ?>/study_plans/add" class="btn btn-outline-primary me-2">Create Study Plan</a>
                        <a href="<?php echo URLROOT; ?>/study_plans/generate" class="btn btn-primary">Generate Study Plan</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Study Recommendations -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Study Recommendations</h5>
        </div>
        <div class="card-body">
            <?php
            // Generate recommendations based on days remaining
            $recommendations = [];
            
            if($data['daysRemaining'] > 60) {
                $recommendations[] = [
                    'title' => 'Focus on building skills',
                    'icon' => 'fas fa-tools',
                    'content' => 'You have plenty of time to improve. Work on building your fundamental skills in all sections, with extra attention to your weak areas.'
                ];
            } elseif($data['daysRemaining'] > 30) {
                $recommendations[] = [
                    'title' => 'Start taking practice tests',
                    'icon' => 'fas fa-clipboard-check',
                    'content' => 'You should start taking regular practice tests to familiarize yourself with the exam format and identify remaining weak areas.'
                ];
            } elseif($data['daysRemaining'] > 14) {
                $recommendations[] = [
                    'title' => 'Focus on weak sections',
                    'icon' => 'fas fa-exclamation-triangle',
                    'content' => 'Time is getting shorter - focus your remaining study time on your weakest sections to maximize your score improvement.'
                ];
            } else {
                $recommendations[] = [
                    'title' => 'Final review and practice',
                    'icon' => 'fas fa-flag-checkered',
                    'content' => 'The test is approaching! Do a full practice test under timed conditions, then spend your remaining days on light review and rest.'
                ];
            }
            
            // Additional recommendations based on gaps
            $maxGapSection = null;
            $maxGap = 0;
            
            foreach($data['studyTimeNeeded'] as $sectionId => $section) {
                if($section['score_gap'] > $maxGap) {
                    $maxGap = $section['score_gap'];
                    $maxGapSection = $section;
                }
            }
            
            if($maxGapSection && $maxGap > 1) {
                $recommendations[] = [
                    'title' => 'Prioritize ' . $maxGapSection['name'],
                    'icon' => 'fas fa-bullseye',
                    'content' => 'Your ' . $maxGapSection['name'] . ' score needs the most improvement. Consider allocating up to 40% of your study time to this section.'
                ];
            }
            
            // Add recommendation based on daily minutes
            $totalDailyMinutes = 0;
            foreach($data['studyTimeNeeded'] as $section) {
                $totalDailyMinutes += $section['daily_minutes'];
            }
            
            if($totalDailyMinutes > 180) {
                $recommendations[] = [
                    'title' => 'Manage your study schedule carefully',
                    'icon' => 'fas fa-clock',
                    'content' => 'You need to study ' . round($totalDailyMinutes/60, 1) . ' hours daily to reach your targets. Consider extending your preparation timeline or adjusting your targets.'
                ];
            }
            ?>
            
            <div class="row">
                <?php foreach($recommendations as $recommendation): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="<?php echo $recommendation['icon']; ?> me-2"></i>
                                    <?php echo $recommendation['title']; ?>
                                </h5>
                                <p class="card-text"><?php echo $recommendation['content']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="alert alert-primary mt-3">
                <p><strong>Pro Tip:</strong> In the last week before your test, focus on familiarizing yourself with the test format rather than trying to learn new material. Get plenty of rest, especially the night before the test.</p>
            </div>
        </div>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>