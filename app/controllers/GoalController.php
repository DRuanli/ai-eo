<?php
/**
 * Goal Controller
 * Handles study goals management
 */
class GoalController extends Controller {
    private $studyGoalModel;
    private $sectionModel;
    private $testScoreModel;
    
    /**
     * Constructor - Initialize models
     */
    public function __construct() {
        // Check if user is logged in for all methods
        if(!isLoggedIn()) {
            redirect('users/login');
        }
        
        // Load models
        $this->studyGoalModel = $this->model('StudyGoal');
        $this->sectionModel = $this->model('IeltsSection');
        $this->testScoreModel = $this->model('TestScore');
    }
    
    /**
     * Goals index page
     */
    public function index() {
        // Get user's goals
        $goals = $this->studyGoalModel->getUserGoals($_SESSION['user_id']);
        
        // Get current scores
        $currentScores = $this->testScoreModel->getLatestSectionScores($_SESSION['user_id']);
        
        // Organize current scores by section ID
        $scoresData = [];
        foreach($currentScores as $score) {
            if(isset($score->section_id)) {
                $scoresData[$score->section_id] = $score->score ?? 0;
            }
        }
        
        // Calculate overall score
        $overallScore = 0;
        $scoreCount = 0;
        
        foreach($scoresData as $score) {
            $overallScore += $score;
            $scoreCount++;
        }
        
        if($scoreCount > 0) {
            $overallScore = round($overallScore / $scoreCount * 2) / 2; // Round to nearest 0.5
        }
        
        // Check for goal achievements
        $this->studyGoalModel->checkGoalAchievement($_SESSION['user_id']);
        
        // Get IELTS sections
        $sections = $this->sectionModel->getAllSections();
        
        // Prepare data for view
        $data = [
            'goals' => $goals,
            'currentScores' => $scoresData,
            'overallScore' => $overallScore,
            'sections' => $sections
        ];
        
        $this->view('goals/index', $data);
    }
    
    /**
     * Add a new goal
     */
    public function add() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'section_id' => isset($_POST['section_id']) ? trim($_POST['section_id']) : null,
                'target_score' => trim($_POST['target_score']),
                'target_date' => trim($_POST['target_date']),
                'section_id_err' => '',
                'target_score_err' => '',
                'target_date_err' => ''
            ];
            
            // Validate Section ID if provided
            if($data['section_id'] !== null && !empty($data['section_id'])) {
                $section = $this->sectionModel->getSectionById($data['section_id']);
                if(!$section) {
                    $data['section_id_err'] = 'Invalid section selected';
                }
            }
            
            // Validate Target Score
            if(empty($data['target_score'])) {
                $data['target_score_err'] = 'Please enter target score';
            } elseif(!isValidIELTSScore($data['target_score'])) {
                $data['target_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            // Validate Target Date
            if(empty($data['target_date'])) {
                $data['target_date_err'] = 'Please enter target date';
            } elseif(!isValidDate($data['target_date'])) {
                $data['target_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            } elseif(strtotime($data['target_date']) < strtotime('today')) {
                $data['target_date_err'] = 'Target date cannot be in the past';
            }
            
            // Make sure errors are empty
            if(empty($data['section_id_err']) && empty($data['target_score_err']) && empty($data['target_date_err'])) {
                // Validated
                
                // Check if goal already exists for this section
                $existingGoal = null;
                
                if($data['section_id'] === null || empty($data['section_id'])) {
                    $existingGoal = $this->studyGoalModel->getOverallGoal($_SESSION['user_id']);
                } else {
                    $existingGoal = $this->studyGoalModel->getSectionGoal($_SESSION['user_id'], $data['section_id']);
                }
                
                if($existingGoal) {
                    // Update existing goal
                    $updateData = [
                        'target_score' => $data['target_score'],
                        'target_date' => $data['target_date'],
                        'achieved' => false
                    ];
                    
                    if($this->studyGoalModel->updateGoal($existingGoal->id, $updateData)) {
                        flash('goal_success', 'Goal updated successfully');
                        redirect('goals');
                    } else {
                        die('Something went wrong');
                    }
                } else {
                    // Add new goal
                    if($this->studyGoalModel->addGoal(
                        $_SESSION['user_id'],
                        $data['target_score'],
                        $data['target_date'],
                        $data['section_id'] !== null && !empty($data['section_id']) ? $data['section_id'] : null
                    )) {
                        flash('goal_success', 'Goal added successfully');
                        redirect('goals');
                    } else {
                        die('Something went wrong');
                    }
                }
            } else {
                // Get sections
                $sections = $this->sectionModel->getAllSections();
                $data['sections'] = $sections;
                
                // Load view with errors
                $this->view('goals/add', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Init data
            $data = [
                'section_id' => '',
                'target_score' => '',
                'target_date' => date('Y-m-d', strtotime('+30 days')),
                'section_id_err' => '',
                'target_score_err' => '',
                'target_date_err' => '',
                'sections' => $sections
            ];
            
            // Load view
            $this->view('goals/add', $data);
        }
    }
    
    /**
     * Edit a goal
     *
     * @param int $id Goal ID
     */
    public function edit($id) {
        // Get goal details
        $goal = $this->studyGoalModel->getGoalById($id);
        
        // Check if goal exists and belongs to user
        if(!$goal || $goal->user_id != $_SESSION['user_id']) {
            flash('goal_error', 'Goal not found or access denied', 'alert alert-danger');
            redirect('goals');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'id' => $id,
                'target_score' => trim($_POST['target_score']),
                'target_date' => trim($_POST['target_date']),
                'target_score_err' => '',
                'target_date_err' => ''
            ];
            
            // Validate Target Score
            if(empty($data['target_score'])) {
                $data['target_score_err'] = 'Please enter target score';
            } elseif(!isValidIELTSScore($data['target_score'])) {
                $data['target_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            // Validate Target Date
            if(empty($data['target_date'])) {
                $data['target_date_err'] = 'Please enter target date';
            } elseif(!isValidDate($data['target_date'])) {
                $data['target_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            } elseif(strtotime($data['target_date']) < strtotime('today')) {
                $data['target_date_err'] = 'Target date cannot be in the past';
            }
            
            // Make sure errors are empty
            if(empty($data['target_score_err']) && empty($data['target_date_err'])) {
                // Validated
                
                // Update goal
                $updateData = [
                    'target_score' => $data['target_score'],
                    'target_date' => $data['target_date']
                ];
                
                if($this->studyGoalModel->updateGoal($id, $updateData)) {
                    flash('goal_success', 'Goal updated successfully');
                    redirect('goals');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('goals/edit', $data);
            }
        } else {
            // Init data
            $data = [
                'id' => $id,
                'section_id' => $goal->section_id,
                'section_name' => $goal->section_name ?? 'Overall',
                'target_score' => $goal->target_score,
                'target_date' => $goal->target_date,
                'target_score_err' => '',
                'target_date_err' => ''
            ];
            
            // Load view
            $this->view('goals/edit', $data);
        }
    }
    
    /**
     * Delete a goal
     *
     * @param int $id Goal ID
     */
    public function delete($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get goal details
            $goal = $this->studyGoalModel->getGoalById($id);
            
            // Check if goal exists and belongs to user
            if(!$goal || $goal->user_id != $_SESSION['user_id']) {
                flash('goal_error', 'Goal not found or access denied', 'alert alert-danger');
                redirect('goals');
            }
            
            // Delete goal
            if($this->studyGoalModel->deleteGoal($id)) {
                flash('goal_success', 'Goal deleted successfully');
                redirect('goals');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('goals');
        }
    }
    
    /**
     * Mark goal as achieved/not achieved
     *
     * @param int $id Goal ID
     * @param bool $achieved Achievement status
     */
    public function markAchieved($id, $achieved = 1) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get goal details
            $goal = $this->studyGoalModel->getGoalById($id);
            
            // Check if goal exists and belongs to user
            if(!$goal || $goal->user_id != $_SESSION['user_id']) {
                flash('goal_error', 'Goal not found or access denied', 'alert alert-danger');
                redirect('goals');
            }
            
            // Update achievement status
            if($this->studyGoalModel->markAsAchieved($id, $achieved == 1)) {
                flash('goal_success', 'Goal ' . ($achieved == 1 ? 'marked as achieved' : 'marked as not achieved'));
                redirect('goals');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('goals');
        }
    }
    
    /**
     * View goal progress
     *
     * @param int $id Goal ID
     */
    public function progress($id) {
        // Get goal details
        $goal = $this->studyGoalModel->getGoalById($id);
        
        // Check if goal exists and belongs to user
        if(!$goal || $goal->user_id != $_SESSION['user_id']) {
            flash('goal_error', 'Goal not found or access denied', 'alert alert-danger');
            redirect('goals');
        }
        
        // Get score history for this section or overall
        $scoreHistory = [];
        
        if($goal->section_id === null) {
            // Get overall scores for each test
            $testModel = $this->model('PracticeTest');
            $tests = $testModel->getUserTests($_SESSION['user_id']);
            
            foreach($tests as $test) {
                $overallScore = $this->testScoreModel->calculateOverallScore($test->id);
                if($overallScore) {
                    $scoreHistory[] = [
                        'date' => $test->test_date,
                        'score' => $overallScore
                    ];
                }
            }
        } else {
            // Get section scores
            $sectionScores = $this->testScoreModel->getSectionScoreHistory($_SESSION['user_id'], $goal->section_id);
            
            foreach($sectionScores as $score) {
                $scoreHistory[] = [
                    'date' => $score->test_date,
                    'score' => $score->score
                ];
            }
        }
        
        // Calculate days remaining
        $targetDate = new DateTime($goal->target_date);
        $today = new DateTime();
        $daysRemaining = $today->diff($targetDate)->days;
        
        if($targetDate < $today) {
            $daysRemaining = 0;
        }
        
        // Get current score
        $currentScore = 0;
        
        if(count($scoreHistory) > 0) {
            $currentScore = $scoreHistory[0]['score'];
        }
        
        // Calculate score gap
        $scoreGap = max(0, $goal->target_score - $currentScore);
        
        // Calculate progress percentage
        $progressPercentage = 0;
        
        if($goal->target_score > 0) {
            $progressPercentage = min(100, ($currentScore / $goal->target_score) * 100);
        }
        
        // Prepare data for view
        $data = [
            'goal' => $goal,
            'scoreHistory' => $scoreHistory,
            'daysRemaining' => $daysRemaining,
            'currentScore' => $currentScore,
            'scoreGap' => $scoreGap,
            'progressPercentage' => $progressPercentage
        ];
        
        $this->view('goals/progress', $data);
    }
}