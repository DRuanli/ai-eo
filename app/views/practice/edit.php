<?php
    ob_start();
?>

<div class="container">
    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h2 class="card-title">Edit Practice Test</h2>
        </div>
        <div class="card-body">
            <form action="<?php echo URLROOT; ?>/practice/edit/<?php echo $data['id']; ?>" method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name">Test Name <sup>*</sup></label>
                            <input type="text" name="name" class="form-control <?php echo (!empty($data['name_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['name']; ?>">
                            <span class="invalid-feedback"><?php echo $data['name_err']; ?></span>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="test_date">Test Date <sup>*</sup></label>
                            <input type="date" name="test_date" class="form-control <?php echo (!empty($data['test_date_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['test_date']; ?>">
                            <span class="invalid-feedback"><?php echo $data['test_date_err']; ?></span>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="notes">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $data['notes']; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Section Scores</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach($data['sections'] as $section): ?>
                                        <div class="col-md-6 mb-3">
                                            <?php 
                                            $scoreField = strtolower($section->name) . '_score';
                                            $timeField = strtolower($section->name) . '_time';
                                            $errorField = $scoreField . '_err';
                                            ?>
                                            
                                            <div class="form-group">
                                                <label for="<?php echo $scoreField; ?>"><?php echo $section->name; ?> Score</label>
                                                <select name="<?php echo $scoreField; ?>" class="form-control <?php echo (!empty($data[$errorField])) ? 'is-invalid' : ''; ?>">
                                                    <option value="">Select score</option>
                                                    <?php 
                                                    $scores = ['0', '0.5', '1', '1.5', '2', '2.5', '3', '3.5', '4', '4.5', '5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9'];
                                                    foreach($scores as $score): 
                                                    ?>
                                                    <option value="<?php echo $score; ?>" <?php echo ($data[$scoreField] == $score) ? 'selected' : ''; ?>><?php echo $score; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span class="invalid-feedback"><?php echo isset($data[$errorField]) ? $data[$errorField] : ''; ?></span>
                                            </div>
                                            
                                            <div class="form-group mt-2">
                                                <label for="<?php echo $timeField; ?>">Time Spent (minutes)</label>
                                                <input type="number" name="<?php echo $timeField; ?>" class="form-control" value="<?php echo $data[$timeField]; ?>" min="0">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-warning">Update Test</button>
                    <a href="<?php echo URLROOT; ?>/practice/view/<?php echo $data['id']; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>