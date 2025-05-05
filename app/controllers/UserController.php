<?php
/**
 * User Controller
 * Handles user registration, authentication, and profile management
 */
class UserController extends Controller {
    private $userModel;
    
    /**
     * Constructor - Initialize models
     */
    public function __construct() {
        $this->userModel = $this->model('User');
    }
    
    /**
     * Default method - Redirect to login
     */
    public function index() {
        redirect('users/login');
    }
    
    /**
     * Register new user
     */
    public function register() {
        // Check if user is already logged in
        if(isLoggedIn()) {
            redirect('dashboard');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'username' => trim($_POST['username']),
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'confirm_password' => trim($_POST['confirm_password']),
                'username_err' => '',
                'email_err' => '',
                'password_err' => '',
                'confirm_password_err' => ''
            ];
            
            // Validate Username
            if(empty($data['username'])) {
                $data['username_err'] = 'Please enter username';
            } elseif(!isValidUsername($data['username'])) {
                $data['username_err'] = 'Username can only contain letters, numbers, underscores, and hyphens (3-20 characters)';
            } elseif($this->userModel->findUserByUsername($data['username'])) {
                $data['username_err'] = 'Username is already taken';
            }
            
            // Validate Email
            if(empty($data['email'])) {
                $data['email_err'] = 'Please enter email';
            } elseif(!isValidEmail($data['email'])) {
                $data['email_err'] = 'Please enter a valid email';
            } elseif($this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'Email is already registered';
            }
            
            // Validate Password
            if(empty($data['password'])) {
                $data['password_err'] = 'Please enter password';
            } elseif(strlen($data['password']) < 6) {
                $data['password_err'] = 'Password must be at least 6 characters';
            } elseif(!isStrongPassword($data['password'])) {
                $data['password_err'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
            }
            
            // Validate Confirm Password
            if(empty($data['confirm_password'])) {
                $data['confirm_password_err'] = 'Please confirm password';
            } else {
                if($data['password'] != $data['confirm_password']) {
                    $data['confirm_password_err'] = 'Passwords do not match';
                }
            }
            
            // Make sure errors are empty
            if(empty($data['username_err']) && empty($data['email_err']) && empty($data['password_err']) && empty($data['confirm_password_err'])) {
                // Validated
                
                // Register User
                $userId = $this->userModel->register($data);
                
                if($userId) {
                    // Initialize default IELTS sections
                    $sectionModel = $this->model('IeltsSection');
                    $sectionModel->initDefaultSections();
                    
                    flash('register_success', 'You are registered and can now log in');
                    redirect('users/login');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('users/register', $data);
            }
        } else {
            // Init data
            $data = [
                'username' => '',
                'email' => '',
                'password' => '',
                'confirm_password' => '',
                'username_err' => '',
                'email_err' => '',
                'password_err' => '',
                'confirm_password_err' => ''
            ];
            
            // Load view
            $this->view('users/register', $data);
        }
    }
    
    /**
     * User login
     */
    public function login() {
        // Check if user is already logged in
        if(isLoggedIn()) {
            redirect('dashboard');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'email_err' => '',
                'password_err' => ''
            ];
            
            // Validate Email
            if(empty($data['email'])) {
                $data['email_err'] = 'Please enter email or username';
            }
            
            // Validate Password
            if(empty($data['password'])) {
                $data['password_err'] = 'Please enter password';
            }
            
            // Check for user/email
            if(!empty($data['email']) && !$this->userModel->findUserByEmail($data['email']) && !$this->userModel->findUserByUsername($data['email'])) {
                // User not found
                $data['email_err'] = 'No user found with that email or username';
            }
            
            // Make sure errors are empty
            if(empty($data['email_err']) && empty($data['password_err'])) {
                // Validated
                // Check and set logged in user
                $loggedInUser = $this->userModel->login($data['email'], $data['password']);
                
                if($loggedInUser) {
                    // Create Session
                    $this->createUserSession($loggedInUser);
                } else {
                    $data['password_err'] = 'Password incorrect';
                    $this->view('users/login', $data);
                }
            } else {
                // Load view with errors
                $this->view('users/login', $data);
            }
        } else {
            // Init data
            $data = [
                'email' => '',
                'password' => '',
                'email_err' => '',
                'password_err' => ''
            ];
            
            // Load view
            $this->view('users/login', $data);
        }
    }
    
    /**
     * Create user session
     *
     * @param object $user User object
     */
    public function createUserSession($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->username;
        
        // Set session timeout
        setSessionTimeout();
        
        redirect('dashboard');
    }
    
    /**
     * User logout
     */
    public function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        session_destroy();
        
        redirect('users/login');
    }
    
    /**
     * Display user profile
     */
    public function profile() {
        // Check if user is logged in
        if(!isLoggedIn()) {
            redirect('users/login');
        }
        
        // Get user from database
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        
        // Get days until test
        $daysUntilTest = $this->userModel->getDaysUntilTest($_SESSION['user_id']);
        
        // Get study stats
        $studySessionModel = $this->model('StudySession');
        $totalStudyTime = $studySessionModel->getTotalStudyTime($_SESSION['user_id']);
        $studyTimePerSection = $studySessionModel->getTotalTimePerSection($_SESSION['user_id']);
        
        // Get test stats
        $practiceTestModel = $this->model('PracticeTest');
        $testCount = $practiceTestModel->countUserTests($_SESSION['user_id']);
        
        $data = [
            'user' => $user,
            'daysUntilTest' => $daysUntilTest,
            'totalStudyTime' => $totalStudyTime,
            'studyTimePerSection' => $studyTimePerSection,
            'testCount' => $testCount
        ];
        
        $this->view('users/profile', $data);
    }
    
    /**
     * Edit user profile
     */
    public function edit() {
        // Check if user is logged in
        if(!isLoggedIn()) {
            redirect('users/login');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'username' => trim($_POST['username']),
                'email' => trim($_POST['email']),
                'current_password' => trim($_POST['current_password']),
                'new_password' => trim($_POST['new_password']),
                'confirm_password' => trim($_POST['confirm_password']),
                'target_score' => trim($_POST['target_score']),
                'test_date' => trim($_POST['test_date']),
                'username_err' => '',
                'email_err' => '',
                'current_password_err' => '',
                'new_password_err' => '',
                'confirm_password_err' => '',
                'target_score_err' => '',
                'test_date_err' => ''
            ];
            
            // Get current user
            $user = $this->userModel->getUserById($_SESSION['user_id']);
            
            // Validate Username
            if(empty($data['username'])) {
                $data['username_err'] = 'Please enter username';
            } elseif(!isValidUsername($data['username'])) {
                $data['username_err'] = 'Username can only contain letters, numbers, underscores, and hyphens (3-20 characters)';
            } elseif($data['username'] !== $user->username && $this->userModel->findUserByUsername($data['username'])) {
                $data['username_err'] = 'Username is already taken';
            }
            
            // Validate Email
            if(empty($data['email'])) {
                $data['email_err'] = 'Please enter email';
            } elseif(!isValidEmail($data['email'])) {
                $data['email_err'] = 'Please enter a valid email';
            } elseif($data['email'] !== $user->email && $this->userModel->findUserByEmail($data['email'])) {
                $data['email_err'] = 'Email is already registered';
            }
            
            // Validate Current Password if changing password
            if(!empty($data['new_password'])) {
                if(empty($data['current_password'])) {
                    $data['current_password_err'] = 'Please enter current password';
                } elseif(!password_verify($data['current_password'], $user->password)) {
                    $data['current_password_err'] = 'Current password is incorrect';
                }
                
                // Validate New Password
                if(empty($data['new_password'])) {
                    $data['new_password_err'] = 'Please enter new password';
                } elseif(strlen($data['new_password']) < 6) {
                    $data['new_password_err'] = 'Password must be at least 6 characters';
                } elseif(!isStrongPassword($data['new_password'])) {
                    $data['new_password_err'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
                }
                
                // Validate Confirm Password
                if(empty($data['confirm_password'])) {
                    $data['confirm_password_err'] = 'Please confirm new password';
                } elseif($data['new_password'] != $data['confirm_password']) {
                    $data['confirm_password_err'] = 'Passwords do not match';
                }
            }
            
            // Validate Target Score
            if(!empty($data['target_score']) && !isValidIELTSScore($data['target_score'])) {
                $data['target_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            // Validate Test Date
            if(!empty($data['test_date']) && !isValidDate($data['test_date'])) {
                $data['test_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            }
            
            // Make sure errors are empty
            if(empty($data['username_err']) && empty($data['email_err']) && empty($data['current_password_err']) && 
               empty($data['new_password_err']) && empty($data['confirm_password_err']) && 
               empty($data['target_score_err']) && empty($data['test_date_err'])) {
                // Validated
                
                // Prepare update data
                $updateData = [
                    'username' => $data['username'],
                    'email' => $data['email']
                ];
                
                // Add password if changed
                if(!empty($data['new_password'])) {
                    $updateData['password'] = $data['new_password'];
                }
                
                // Add target score if provided
                if(!empty($data['target_score'])) {
                    $updateData['target_score'] = $data['target_score'];
                }
                
                // Add test date if provided
                if(!empty($data['test_date'])) {
                    $updateData['test_date'] = $data['test_date'];
                }
                
                // Update user
                if($this->userModel->updateProfile($_SESSION['user_id'], $updateData)) {
                    // Update session data
                    $_SESSION['user_email'] = $data['email'];
                    $_SESSION['user_name'] = $data['username'];
                    
                    flash('profile_success', 'Your profile has been updated');
                    redirect('users/profile');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('users/edit', $data);
            }
        } else {
            // Get user from database
            $user = $this->userModel->getUserById($_SESSION['user_id']);
            
            // Init data
            $data = [
                'username' => $user->username,
                'email' => $user->email,
                'current_password' => '',
                'new_password' => '',
                'confirm_password' => '',
                'target_score' => $user->target_score,
                'test_date' => $user->test_date,
                'username_err' => '',
                'email_err' => '',
                'current_password_err' => '',
                'new_password_err' => '',
                'confirm_password_err' => '',
                'target_score_err' => '',
                'test_date_err' => ''
            ];
            
            // Load view
            $this->view('users/edit', $data);
        }
    }
}