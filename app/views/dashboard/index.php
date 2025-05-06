<?php
    ob_start();
?>

<div class="container">
    <h1 class="mb-4">IELTS Study Dashboard</h1>
    
    <!-- Test Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Test Information</h5>
                </div>
                <div class="card-body">
                    <?php if($data['user']->test_date): ?>
                        <h3>
                            <i class="fas fa-calendar"></i> Test Date: 
                            <span class="badge bg-info"><?php echo date('F j, Y', strtotime($data['user']->test_date)); ?></span>
                        </h3>
                        <h4>
                            <i class="fas fa-hourglass-half"></i> Days Remaining: 
                            <?php if($data['daysUntilTest'] > 0): ?>
                                <span class="badge bg-warning"><?php echo $data['daysUntilTest']; ?> days</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Test date passed</span>
                            <?php endif; ?>
                        </h4>
                        <h4>
                            <i class="fas fa-star"></i> Target Score: 
                            <span class="badge bg-success"><?php echo $data['user']->target_score ?: 'Not set'; ?></span>
                        </h4>
                        <div class="mt-3">
                            <a href="<?php echo URLROOT; ?>/dashboard/test_prep_summary" class="btn btn-outline-primary">View Test Preparation Summary</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>You haven't set your IELTS test date yet. Setting a test date helps you plan your study effectively.</p>
                            <a href="<?php echo URLROOT; ?>/users/edit" class="btn btn-primary">Set Test Date</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Current Scores</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($data['latestScores'])): ?>
                        <div class="row">
                            <?php foreach($data['latestScores'] as $score): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5><?php echo $score->section_name; ?>:</h5>
                                        <?php if(isset($score->score)): ?>
                                            <span class="badge bg-primary fs-5"><?php echo $score->score; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No score</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo URLROOT; ?>/practice/add" class="btn btn-outline-success">Add New Test Score</a>
                            <a href="<?php echo URLROOT; ?>/dashboard/progress" class="btn btn-outline-info">View Progress</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>You haven't recorded any practice test scores yet.</p>
                            <a href="<?php echo URLROOT; ?>/practice/add" class="btn btn-primary">Add Practice Test</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Weekly Study Summary -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">Weekly Study Summary</h5>
                </div>
                <div class="card-body">
                    <h4>
                        <i class="fas fa-clock"></i> Total Study Time This Week: 
                        <?php 
                            $hours = floor($data['weeklyStudyTime'] / 60);
                            $minutes = $data['weeklyStudyTime'] % 60;
                            echo "<span class='badge bg-warning text-dark'>{$hours} hr {$minutes} min</span>";
                        ?>
                    </h4>
                    
                    <?php if($data['activeSession']): ?>
                        <div class="alert alert-warning mt-3">
                            <p><strong>Active Study Session:</strong> 
                                <?php echo $data['activeSession']->section_name; ?> 
                                (Started: <?php echo date('g:i A', strtotime($data['activeSession']->start_time)); ?>)
                            </p>
                            <form action="<?php echo URLROOT; ?>/study_sessions/end" method="post">
                                <button type="submit" class="btn btn-danger">End Session</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="mt-3">
                            <a href="<?php echo URLROOT; ?>/study_sessions" class="btn btn-outline-warning">Start Study Session</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Focus Areas</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($data['weakAreas'])): ?>
                        <h6>Top Weak Areas to Focus On:</h6>
                        <ul class="list-group">
                            <?php foreach($data['weakAreas'] as $area): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-secondary me-2"><?php echo $area->section_name; ?></span>
                                        <?php echo $area->sub_skill; ?>
                                    </div>
                                    <span class="badge bg-danger rounded-pill">
                                        <?php for($i = 0; $i < $area->priority; $i++) echo '★'; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3">
                            <a href="<?php echo URLROOT; ?>/weak_areas" class="btn btn-outline-danger">View All Weak Areas</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>You haven't identified any weak areas yet.</p>
                            <a href="<?php echo URLROOT; ?>/weak_areas/add" class="btn btn-primary">Add Weak Area</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Plan -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Today's Study Plan</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['todayItems'])): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Task</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['todayItems'] as $item): ?>
                                <tr>
                                    <td>
                                        <?php if(isset($item->section_name)): ?>
                                            <span class="badge bg-secondary"><?php echo $item->section_name; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $item->title; ?></td>
                                    <td><?php echo $item->duration; ?> min</td>
                                    <td>
                                        <?php if($item->completed): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo URLROOT; ?>/study_plans/view/<?php echo $item->study_plan_id; ?>" class="btn btn-sm btn-info">View</a>
                                        <?php if(!$item->completed): ?>
                                            <form class="d-inline" action="<?php echo URLROOT; ?>/study_plans/markCompleted/<?php echo $item->id; ?>" method="post">
                                                <button type="submit" class="btn btn-sm btn-success">Mark Complete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo URLROOT; ?>/study_plans/today" class="btn btn-outline-info">View All Today's Tasks</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You don't have any study tasks scheduled for today.</p>
                    <a href="<?php echo URLROOT; ?>/study_plans" class="btn btn-primary">View Study Plans</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Upcoming Goals -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Upcoming Goals</h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($data['upcomingGoals'])): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th>Target Score</th>
                                        <th>Target Date</th>
                                        <th>Days Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($data['upcomingGoals'] as $goal): ?>
                                        <tr>
                                            <td><?php echo $goal->section_name; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $goal->target_score; ?></span></td>
                                            <td><?php echo date('M j, Y', strtotime($goal->target_date)); ?></td>
                                            <td>
                                                <?php 
                                                    $daysLeft = floor((strtotime($goal->target_date) - time()) / (60 * 60 * 24));
                                                    echo "<span class='badge " . ($daysLeft < 7 ? 'bg-danger' : 'bg-info') . "'>{$daysLeft} days</span>";
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo URLROOT; ?>/goals" class="btn btn-outline-secondary">View All Goals</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>You don't have any upcoming goals.</p>
                            <a href="<?php echo URLROOT; ?>/goals/add" class="btn btn-primary">Add Goal</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>