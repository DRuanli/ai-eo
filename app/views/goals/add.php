<?php
    ob_start();
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title">Add New Goal</h2>
                </div>
                <div class="card-body">
                    <form action="<?php echo URLROOT; ?>/goals/add" method="post">
                        <div class="form-group mb-3">
                            <label for="section_id">Section</label>
                            <select name="section_id" id="section_id" class="form-control <?php echo (!empty($data['section_id_err'])) ? 'is-invalid' : ''; ?>">
                                <option value="">Overall (All Sections)</option>
                                <?php foreach($data['sections'] as $section): ?>
                                    <option value="<?php echo $section->id; ?>" <?php echo ($data['section_id'] == $section->id) ? 'selected' : ''; ?>>
                                        <?php echo $section->name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['section_id_err']; ?></span>
                            <small class="form-text text-muted">Select "Overall" for an overall score goal, or choose a specific section.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="target_score">Target Score <sup>*</sup></label>
                            <select name="target_score" id="target_score" class="form-control <?php echo (!empty($data['target_score_err'])) ? 'is-invalid' : ''; ?>">
                                <option value="">Select target score</option>
                                <?php 
                                $scores = ['5.0', '5.5', '6.0', '6.5', '7.0', '7.5', '8.0', '8.5', '9.0'];
                                foreach($scores as $score): 
                                ?>
                                <option value="<?php echo $score; ?>" <?php echo ($data['target_score'] == $score) ? 'selected' : ''; ?>><?php echo $score; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['target_score_err']; ?></span>
                            <small class="form-text text-muted">Choose your target IELTS band score.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="target_date">Target Date <sup>*</sup></label>
                            <input type="date" name="target_date" id="target_date" class="form-control <?php echo (!empty($data['target_date_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['target_date']; ?>">
                            <span class="invalid-feedback"><?php echo $data['target_date_err']; ?></span>
                            <small class="form-text text-muted">Set a deadline to achieve your target score.</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Setting realistic goals is important for effective study planning. Consider your current score, available study time, and the difficulty of improving in each band level.
                        </div>
                        
                        <div class="form-group text-center mt-4">
                            <button type="submit" class="btn btn-primary">Add Goal</button>
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