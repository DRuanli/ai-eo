<?php
/**
 * Assessment Controller
 * Handles assessment functionality for practice tests and scoring
 */
class AssessmentController extends Controller {
    private $testScoreModel;
    private $practiceTestModel;
    private $sectionModel;
    
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
        $this->practiceTestModel = $this->model('PracticeTest');
        $this->sectionModel = $this->model('IeltsSection');
    }
    
    /**
     * Assessment index page
     */
    public function index() {
        // Get user's recent test scores
        $latestTest = $this->practiceTestModel->getLatestTest($_SESSION['user_id']);
        $latestScores = [];
        
        if($latestTest) {
            $latestScores = $this->testScoreModel->getTestScores($latestTest->id);
        }
        
        // Get all IELTS sections
        $sections = $this->sectionModel->getAllSections();
        
        // Prepare data for view
        $data = [
            'latestTest' => $latestTest,
            'latestScores' => $latestScores,
            'sections' => $sections
        ];
        
        $this->view('assessments/index', $data);
    }
    
    /**
     * Quick assessment form
     */
    public function quick() {
        // Get sections for form
        $sections = $this->sectionModel->getAllSections();
        
        $data = [
            'sections' => $sections
        ];
        
        $this->view('assessments/quick', $data);
    }
    
    /**
     * Save quick assessment
     */
    public function save() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process quick assessment
            // Redirect to practice tests
            redirect('practice');
        } else {
            redirect('assessments/quick');
        }
    }
}