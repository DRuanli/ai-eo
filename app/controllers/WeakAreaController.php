<?php
/**
 * Weak Area Controller
 * Handles weak areas identification and management
 */
class WeakAreaController extends Controller {
    private $weakAreaModel;
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
        $this->weakAreaModel = $this->model('WeakArea');
        $this->sectionModel = $this->model('IeltsSection');
        $this->testScoreModel = $this->model('TestScore');
    }
    
    /**
     * Weak areas index page
     */
    public function index() {
        // Get user's weak areas
        $weakAreas = $this->weakAreaModel->getUserWeakAreas($_SESSION['user_id']);
        
        // Get section weak area counts
        $sectionCounts = $this->weakAreaModel->countWeakAreasBySection($_SESSION['user_id']);
        
        // Get latest section scores
        $latestScores = $this->testScoreModel->getLatestSectionScores($_SESSION['user_id']);
        
        // Identify weak sections based on scores
        $weakSections = $this->testScoreModel->identifyWeakSections($_SESSION['user_id'], 2);
        
        // Get IELTS sections
        $sections = $this->sectionModel->getAllSections();
        
        // Prepare sections data with common sub-skills
        $sectionsData = [];
        foreach($sections as $section) {
            $sectionsData[$section->id] = [
                'id' => $section->id,
                'name' => $section->name,
                'common_sub_skills' => $this->weakAreaModel->getCommonSubSkills($section->id)
            ];
        }
        
        // Prepare data for view
        $data = [
            'weakAreas' => $weakAreas,
            'sectionCounts' => $sectionCounts,
            'latestScores' => $latestScores,
            'weakSections' => $weakSections,
            'sections' => $sections,
            'sectionsData' => $sectionsData
        ];
        
        $this->view('weak_areas/index', $data);
    }
    
    /**
     * Add a new weak area
     */
    public function add() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'section_id' => trim($_POST['section_id']),
                'sub_skill' => trim($_POST['sub_skill']),
                'priority' => isset($_POST['priority']) ? intval($_POST['priority']) : 1,
                'section_id_err' => '',
                'sub_skill_err' => '',
                'priority_err' => ''
            ];
            
            // If using custom sub-skill
            if(isset($_POST['custom_sub_skill']) && !empty($_POST['custom_sub_skill'])) {
                $data['sub_skill'] = trim($_POST['custom_sub_skill']);
            }
            
            // Validate Section ID
            if(empty($data['section_id'])) {
                $data['section_id_err'] = 'Please select a section';
            } else {
                $section = $this->sectionModel->getSectionById($data['section_id']);
                if(!$section) {
                    $data['section_id_err'] = 'Invalid section selected';
                }
            }
            
            // Validate Sub-skill
            if(empty($data['sub_skill'])) {
                $data['sub_skill_err'] = 'Please select or enter a sub-skill';
            }
            
            // Validate Priority
            if($data['priority'] < 1 || $data['priority'] > 5) {
                $data['priority_err'] = 'Priority must be between 1 and 5';
            }
            
            // Check if weak area already exists
            if(empty($data['section_id_err']) && empty($data['sub_skill_err']) && 
               $this->weakAreaModel->weakAreaExists($_SESSION['user_id'], $data['section_id'], $data['sub_skill'])) {
                $data['sub_skill_err'] = 'This weak area already exists for the selected section';
            }
            
            // Make sure errors are empty
            if(empty($data['section_id_err']) && empty($data['sub_skill_err']) && empty($data['priority_err'])) {
                // Validated
                
                // Add weak area
                if($this->weakAreaModel->addWeakArea(
                    $_SESSION['user_id'],
                    $data['section_id'],
                    $data['sub_skill'],
                    $data['priority']
                )) {
                    flash('weak_area_success', 'Weak area added successfully');
                    redirect('weak_areas');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Get sections
                $sections = $this->sectionModel->getAllSections();
                
                // Prepare sections data with common sub-skills
                $sectionsData = [];
                foreach($sections as $section) {
                    $sectionsData[$section->id] = [
                        'id' => $section->id,
                        'name' => $section->name,
                        'common_sub_skills' => $this->weakAreaModel->getCommonSubSkills($section->id)
                    ];
                }
                
                $data['sections'] = $sections;
                $data['sectionsData'] = $sectionsData;
                
                // Load view with errors
                $this->view('weak_areas/add', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Prepare sections data with common sub-skills
            $sectionsData = [];
            foreach($sections as $section) {
                $sectionsData[$section->id] = [
                    'id' => $section->id,
                    'name' => $section->name,
                    'common_sub_skills' => $this->weakAreaModel->getCommonSubSkills($section->id)
                ];
            }
            
            // Init data
            $data = [
                'section_id' => '',
                'sub_skill' => '',
                'priority' => 3,
                'section_id_err' => '',
                'sub_skill_err' => '',
                'priority_err' => '',
                'sections' => $sections,
                'sectionsData' => $sectionsData
            ];
            
            // Pre-select section if provided in URL
            if(isset($_GET['section']) && is_numeric($_GET['section'])) {
                $sectionId = intval($_GET['section']);
                if($this->sectionModel->getSectionById($sectionId)) {
                    $data['section_id'] = $sectionId;
                }
            }
            
            // Load view
            $this->view('weak_areas/add', $data);
        }
    }
    
    /**
     * Edit a weak area
     *
     * @param int $id Weak area ID
     */
    public function edit($id) {
        // Get weak area details
        $weakArea = $this->weakAreaModel->getWeakAreaById($id);
        
        // Check if weak area exists and belongs to user
        if(!$weakArea || $weakArea->user_id != $_SESSION['user_id']) {
            flash('weak_area_error', 'Weak area not found or access denied', 'alert alert-danger');
            redirect('weak_areas');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'id' => $id,
                'sub_skill' => trim($_POST['sub_skill']),
                'priority' => isset($_POST['priority']) ? intval($_POST['priority']) : 1,
                'sub_skill_err' => '',
                'priority_err' => ''
            ];
            
            // Validate Sub-skill
            if(empty($data['sub_skill'])) {
                $data['sub_skill_err'] = 'Please enter a sub-skill';
            }
            
            // Validate Priority
            if($data['priority'] < 1 || $data['priority'] > 5) {
                $data['priority_err'] = 'Priority must be between 1 and 5';
            }
            
            // Make sure errors are empty
            if(empty($data['sub_skill_err']) && empty($data['priority_err'])) {
                // Validated
                
                // Update weak area
                $updateData = [
                    'sub_skill' => $data['sub_skill'],
                    'priority' => $data['priority']
                ];
                
                if($this->weakAreaModel->updateWeakArea($id, $updateData)) {
                    flash('weak_area_success', 'Weak area updated successfully');
                    redirect('weak_areas');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Add section data to view data
                $data['section_id'] = $weakArea->section_id;
                $data['section_name'] = $weakArea->section_name;
                
                // Load view with errors
                $this->view('weak_areas/edit', $data);
            }
        } else {
            // Init data
            $data = [
                'id' => $id,
                'section_id' => $weakArea->section_id,
                'section_name' => $weakArea->section_name,
                'sub_skill' => $weakArea->sub_skill,
                'priority' => $weakArea->priority,
                'sub_skill_err' => '',
                'priority_err' => ''
            ];
            
            // Load view
            $this->view('weak_areas/edit', $data);
        }
    }
    
    /**
     * Delete a weak area
     *
     * @param int $id Weak area ID
     */
    public function delete($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get weak area details
            $weakArea = $this->weakAreaModel->getWeakAreaById($id);
            
            // Check if weak area exists and belongs to user
            if(!$weakArea || $weakArea->user_id != $_SESSION['user_id']) {
                flash('weak_area_error', 'Weak area not found or access denied', 'alert alert-danger');
                redirect('weak_areas');
            }
            
            // Delete weak area
            if($this->weakAreaModel->deleteWeakArea($id)) {
                flash('weak_area_success', 'Weak area deleted successfully');
                redirect('weak_areas');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('weak_areas');
        }
    }
    
    /**
     * Update priority for a weak area
     *
     * @param int $id Weak area ID
     */
    public function updatePriority($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get weak area details
            $weakArea = $this->weakAreaModel->getWeakAreaById($id);
            
            // Check if weak area exists and belongs to user
            if(!$weakArea || $weakArea->user_id != $_SESSION['user_id']) {
                flash('weak_area_error', 'Weak area not found or access denied', 'alert alert-danger');
                redirect('weak_areas');
            }
            
            // Get priority from POST
            $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 1;
            
            // Validate priority
            if($priority < 1 || $priority > 5) {
                flash('weak_area_error', 'Priority must be between 1 and 5', 'alert alert-danger');
                redirect('weak_areas');
            }
            
            // Update priority
            if($this->weakAreaModel->updatePriority($id, $priority)) {
                flash('weak_area_success', 'Priority updated successfully');
                redirect('weak_areas');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('weak_areas');
        }
    }
    
    /**
     * Show weak areas by section
     *
     * @param int $sectionId Section ID
     */
    public function bySection($sectionId) {
        // Validate section ID
        $section = $this->sectionModel->getSectionById($sectionId);
        
        if(!$section) {
            flash('section_error', 'Section not found', 'alert alert-danger');
            redirect('weak_areas');
        }
        
        // Get section weak areas
        $weakAreas = $this->weakAreaModel->getWeakAreasBySection($_SESSION['user_id'], $sectionId);
        
        // Get common sub-skills for this section
        $commonSubSkills = $this->weakAreaModel->getCommonSubSkills($sectionId);
        
        // Prepare data for view
        $data = [
            'section' => $section,
            'weakAreas' => $weakAreas,
            'commonSubSkills' => $commonSubSkills
        ];
        
        $this->view('weak_areas/by_section', $data);
    }
    
    /**
     * Auto-identify weak areas based on test scores
     */
    public function autoIdentify() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Identify weak sections based on scores
            $weakSections = $this->testScoreModel->identifyWeakSections($_SESSION['user_id']);
            
            // For each weak section, add a generic weak area
            $addedCount = 0;
            
            foreach($weakSections as $section) {
                // Get common sub-skills for this section
                $commonSubSkills = $this->weakAreaModel->getCommonSubSkills($section->id);
                
                // Add first 2 common sub-skills if they exist
                $skillsToAdd = array_slice($commonSubSkills, 0, 2);
                
                foreach($skillsToAdd as $skill) {
                    // Check if weak area already exists
                    if(!$this->weakAreaModel->weakAreaExists($_SESSION['user_id'], $section->id, $skill)) {
                        $this->weakAreaModel->addWeakArea(
                            $_SESSION['user_id'],
                            $section->id,
                            $skill,
                            4 // High priority
                        );
                        $addedCount++;
                    }
                }
            }
            
            if($addedCount > 0) {
                flash('weak_area_success', $addedCount . ' weak areas automatically identified and added');
            } else {
                flash('weak_area_info', 'No new weak areas identified', 'alert alert-info');
            }
            
            redirect('weak_areas');
        } else {
            redirect('weak_areas');
        }
    }
}