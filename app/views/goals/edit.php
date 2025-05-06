<?php
    ob_start();
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h2 class="card-title">Edit Goal</h2>
                </div>
                <div class="card-body">
                    <form action="<?php echo URLROOT; ?>/goals/edit/<?php echo $data['id']; ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="section_name">Section</label>
                            <input type="text" name="section_name" id="section_name" class="form-control" value="<?php echo $data['section_name']; ?>" readonly>
                            <small class="form-text text-muted">Section cannot be changed. Delete this goal and create a new one if needed.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="target_score">Target Score <sup>*</sup></label>
                            <select name="target_score" id="target_score" class="form-control <?php echo (!empty($data['target_score_err'])) ? 'is-invalid' : ''; ?>">
                                <?php 
                                $scores = ['5.0', '5.5', '6.0', '6.5', '7.0', '7.5', '8.0', '8.5', '9.0'];
                                foreach($scores as $score): 
                                ?>
                                <option value="<?php echo $score; ?>" <?php echo ($data['target_score'] == $score) ? 'selected' : ''; ?>><?php echo $score; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['target_score_err']; ?></span>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="target_date">Target Date <sup>*</sup></label>
                            <input type="date" name="target_date" id="target_date" class="form-control <?php echo (!empty($data['target_date_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['target_date']; ?>">
                            <span class="invalid-feedback"><?php echo $data['target_date_err']; ?></span>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Remember to set realistic goals. Consider your current progress and available study time when adjusting your target.
                        </div>
                        
                        <div class="form-group text-center mt-4">
                            <button type="submit" class="btn btn-warning">Update Goal</button>
                            <a href="<?php echo URLROOT; ?>/goals" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>