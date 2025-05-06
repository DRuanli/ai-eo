<?php
/**
 * Progress Controller
 * Handles progress tracking and reporting functionality
 */
class ProgressController extends Controller {
    private $testScoreModel;
    private $studySessionModel;
    private $weakAreaModel;
    private $studyGoalModel;
    
    /**
     * Constructor - Initialize models
     */
    public function __construct() {
        // Check if user is logged in for all methods
        if(!isLoggedIn()) {
            redirect('users/login');
        }
        
        // Load models
        $this->testScoreModel = $this->model('TestScore');
        $this->studySessionModel = $this->model('StudySession');
        $this->weakAreaModel = $this->model('WeakArea');
        $this->studyGoalModel = $this->model('StudyGoal');
    }
    
    /**
     * Progress index page
     */
    public function index() {
        // Get section scores over time
        $readingScores = $this->testScoreModel->getSectionScoreHistory($_SESSION['user_id'], 1);
        $writingScores = $this->testScoreModel->getSectionScoreHistory($_SESSION['user_id'], 2);
        $listeningScores = $this->testScoreModel->getSectionScoreHistory($_SESSION['user_id'], 3);
        $speakingScores = $this->testScoreModel->getSectionScoreHistory($_SESSION['user_id'], 4);
        
        // Get total study time by section
        $studyTimeBySection = $this->studySessionModel->getTotalTimePerSection($_SESSION['user_id']);
        
        // Get weak areas by section
        $weakAreasBySection = $this->weakAreaModel->countWeakAreasBySection($_SESSION['user_id']);
        
        // Get goal progress
        $goalProgress = $this->studyGoalModel->getGoalsProgress($_SESSION['user_id']);
        
        // Prepare data for view
        $data = [
            'readingScores' => $readingScores,
            'writingScores' => $writingScores,
            'listeningScores' => $listeningScores,
            'speakingScores' => $speakingScores,
            'studyTimeBySection' => $studyTimeBySection,
            'weakAreasBySection' => $weakAreasBySection,
            'goalProgress' => $goalProgress
        ];
        
        $this->view('progress/index', $data);
    }
    
    /**
     * Show detailed progress report
     */
    public function report() {
        // Get data for detailed report
        
        $data = [];
        
        $this->view('progress/report', $data);
    }
}