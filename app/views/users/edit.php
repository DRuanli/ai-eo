<?php
    ob_start();
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-body bg-light">
            <h2 class="mb-4">Edit Profile</h2>
            <form action="<?php echo URLROOT; ?>/users/edit" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Account Information</h4>
                        <div class="form-group mb-3">
                            <label for="username">Username <sup>*</sup></label>
                            <input type="text" name="username" class="form-control <?php echo (!empty($data['username_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['username']; ?>">
                            <span class="invalid-feedback"><?php echo $data['username_err']; ?></span>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email <sup>*</sup></label>
                            <input type="email" name="email" class="form-control <?php echo (!empty($data['email_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['email']; ?>">
                            <span class="invalid-feedback"><?php echo $data['email_err']; ?></span>
                        </div>
                        
                        <h5 class="mt-4">Change Password</h5>
                        <div class="form-group mb-3">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" class="form-control <?php echo (!empty($data['current_password_err'])) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $data['current_password_err']; ?></span>
                            <small class="form-text text-muted">Leave blank if you don't want to change your password</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" class="form-control <?php echo (!empty($data['new_password_err'])) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $data['new_password_err']; ?></span>
                        </div>
                        <div class="form-group mb-3">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control <?php echo (!empty($data['confirm_password_err'])) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $data['confirm_password_err']; ?></span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>IELTS Test Information</h4>
                        <div class="form-group mb-3">
                            <label for="test_date">Test Date</label>
                            <input type="date" name="test_date" class="form-control <?php echo (!empty($data['test_date_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['test_date']; ?>">
                            <span class="invalid-feedback"><?php echo $data['test_date_err']; ?></span>
                        </div>
                        <div class="form-group mb-3">
                            <label for="target_score">Target Score</label>
                            <select name="target_score" class="form-control <?php echo (!empty($data['target_score_err'])) ? 'is-invalid' : ''; ?>">
                                <option value="">Select a target score</option>
                                <?php 
                                $scores = ['5.0', '5.5', '6.0', '6.5', '7.0', '7.5', '8.0', '8.5', '9.0'];
                                foreach($scores as $score): 
                                ?>
                                <option value="<?php echo $score; ?>" <?php echo ($data['target_score'] == $score) ? 'selected' : ''; ?>><?php echo $score; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="invalid-feedback"><?php echo $data['target_score_err']; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?php echo URLROOT; ?>/users/profile" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
    $content = ob_get_clean();
    include APPROOT . '/views/layouts/default.php';
?>