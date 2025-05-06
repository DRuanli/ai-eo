<?php
    ob_start();
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Weak Areas</h1>
        <div>
            <form action="<?php echo URLROOT; ?>/weak_areas/autoIdentify" method="post" class="d-inline">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-magic"></i> Auto-Identify Weak Areas
                </button>
            </form>
            <a href="<?php echo URLROOT; ?>/weak_areas/add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Weak Area
            </a>
        </div>
    </div>
    
    <!-- Section Overview -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Section Overview</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?php if(!empty($data['latestScores'])): ?>
                        <div class="chart-container" style="position: relative; height:250px;">
                            <canvas id="sectionScoresChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>No test scores recorded yet. Take practice tests to identify your weak sections.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <?php if(!empty($data['sectionCounts'])): ?>
                        <div class="chart-container" style="position: relative; height:250px;">
                            <canvas id="weakAreasCountChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>No weak areas identified yet. Add weak areas to track your improvement needs.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if(!empty($data['weakSections'])): ?>
                <div class="alert alert-warning mt-3">
                    <h5><i class="fas fa-exclamation-triangle"></i> Your weakest sections based on test scores:</h5>
                    <ul>
                        <?php foreach($data['weakSections'] as $section): ?>
                            <li>
                                <strong><?php echo $section->name; ?></strong> 
                                (Avg. Score: <?php echo number_format($section->avg_score, 1); ?>)
                                <a href="<?php echo URLROOT; ?>/weak_areas/bySection/<?php echo $section->id; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                    View Weak Areas
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Weak Areas List -->
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0">Your Weak Areas</h5>
        </div>
        <div class="card-body">
            <?php if(!empty($data['weakAreas'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Sub-skill</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['weakAreas'] as $area): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $area->section_name; ?></span>
                                    </td>
                                    <td><?php echo $area->sub_skill; ?></td>
                                    <td>
                                        <div class="priority-stars">
                                            <?php 
                                            for($i = 1; $i <= 5; $i++) {
                                                if($i <= $area->priority) {
                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-secondary"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo URLROOT; ?>/weak_areas/edit/<?php echo $area->id; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $area->id; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Priority Dropdown -->
                                        <div class="dropdown d-inline ms-2">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="priorityDropdown<?php echo $area->id; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Priority
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="priorityDropdown<?php echo $area->id; ?>">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <li>
                                                        <form action="<?php echo URLROOT; ?>/weak_areas/updatePriority/<?php echo $area->id; ?>" method="post">
                                                            <input type="hidden" name="priority" value="<?php echo $i; ?>">
                                                            <button type="submit" class="dropdown-item <?php echo ($area->priority == $i) ? 'active' : ''; ?>">
                                                                <?php 
                                                                for($j = 1; $j <= $i; $j++) {
                                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                                }
                                                                ?>
                                                                Priority <?php echo $i; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endfor; ?>
                                            </ul>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $area->id; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $area->id; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $area->id; ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete the weak area "<?php echo $area->sub_skill; ?>" for <?php echo $area->section_name; ?>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="<?php echo URLROOT; ?>/weak_areas/delete/<?php echo $area->id; ?>" method="post">
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
                    <p>You haven't identified any weak areas yet. Identifying specific weak areas will help you focus your studying effectively.</p>
                </div>
                <div class="text-center">
                    <a href="<?php echo URLROOT; ?>/weak_areas/add" class="btn btn-primary">Add Your First Weak Area</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Section-based Weak Areas Accordion -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Weak Areas by Section</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="sectionAccordion">
                <?php foreach($data['sections'] as $section): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $section->id; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $section->id; ?>" aria-expanded="false" aria-controls="collapse<?php echo $section->id; ?>">
                                <strong><?php echo $section->name; ?></strong>
                                <?php 
                                $count = 0;
                                foreach($data['sectionCounts'] as $secCount) {
                                    if($secCount->id == $section->id) {
                                        $count = $secCount->weak_area_count;
                                        break;
                                    }
                                }
                                ?>
                                <span class="badge bg-danger ms-2"><?php echo $count; ?> weak areas</span>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $section->id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $section->id; ?>" data-bs-parent="#sectionAccordion">
                            <div class="accordion-body">
                                <?php
                                $sectionWeakAreas = array_filter($data['weakAreas'], function($area) use ($section) {
                                    return $area->section_id == $section->id;
                                });
                                
                                if(!empty($sectionWeakAreas)):
                                ?>
                                    <ul class="list-group">
                                        <?php foreach($sectionWeakAreas as $area): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo $area->sub_skill; ?>
                                                <span class="priority-stars">
                                                    <?php 
                                                    for($i = 1; $i <= 5; $i++) {
                                                        if($i <= $area->priority) {
                                                            echo '<i class="fas fa-star text-warning"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star text-secondary"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-light">
                                        <p>No weak areas identified for <?php echo $section->name; ?> yet.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="<?php echo URLROOT; ?>/weak_areas/add?section=<?php echo $section->id; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Weak Area for <?php echo $section->name; ?>
                                    </a>
                                    <a href="<?php echo URLROOT; ?>/weak_areas/bySection/<?php echo $section->id; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> View All
                                    </a>
                                </div>
                                
                                <div class="mt-3">
                                    <h6>Common <?php echo $section->name; ?> Sub-skills:</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php 
                                        $commonSubSkills = $data['sectionsData'][$section->id]['common_sub_skills'];
                                        foreach($commonSubSkills as $skill):
                                        ?>
                                            <span class="badge bg-light text-dark border"><?php echo $skill; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(!empty($data['latestScores'])): ?>
    // Prepare data for section scores chart
    const sectionNames = [];
    const sectionScores = [];
    
    <?php 
    foreach($data['latestScores'] as $score): 
        if(isset($score->score)):
    ?>
        sectionNames.push('<?php echo $score->section_name; ?>');
        sectionScores.push(<?php echo $score->score; ?>);
    <?php 
        endif;
    endforeach; 
    ?>
    
    // Create section scores chart
    const scoresCtx = document.getElementById('sectionScoresChart').getContext('2d');
    new Chart(scoresCtx, {
        type: 'bar',
        data: {
            labels: sectionNames,
            datasets: [{
                label: 'Latest Scores',
                data: sectionScores,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(255, 99, 132, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
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
                        stepSize: 1
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Latest Section Scores'
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if(!empty($data['sectionCounts'])): ?>
    // Prepare data for weak areas count chart
    const sectionLabels = [];
    const weakAreaCounts = [];
    
    <?php foreach($data['sectionCounts'] as $count): ?>
        sectionLabels.push('<?php echo $count->name; ?>');
        weakAreaCounts.push(<?php echo $count->weak_area_count; ?>);
    <?php endforeach; ?>
    
    // Create weak areas count chart
    const countsCtx = document.getElementById('weakAreasCountChart').getContext('2d');
    new Chart(countsCtx, {
        type: 'doughnut',
        data: {
            labels: sectionLabels,
            datasets: [{
                data: weakAreaCounts,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Weak Areas by Section'
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