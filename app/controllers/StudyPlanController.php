<?php
/**
 * Study Plan Controller
 * Handles study plans management and generation
 */
class StudyPlanController extends Controller {
    private $studyPlanModel;
    private $studyPlanItemModel;
    private $userModel;
    private $sectionModel;
    private $weakAreaModel;
    private $resourceModel;
    
    /**
     * Constructor - Initialize models
     */
    public function __construct() {
        // Check if user is logged in for all methods
        if(!isLoggedIn()) {
            redirect('users/login');
        }
        
        // Load models
        $this->studyPlanModel = $this->model('StudyPlan');
        $this->studyPlanItemModel = $this->model('StudyPlanItem');
        $this->userModel = $this->model('User');
        $this->sectionModel = $this->model('IeltsSection');
        $this->weakAreaModel = $this->model('WeakArea');
        $this->resourceModel = $this->model('StudyResource');
    }
    
    /**
     * Study plans index page
     */
    public function index() {
        // Get user's study plans
        $activePlans = $this->studyPlanModel->getUserPlans($_SESSION['user_id'], 'active');
        $completedPlans = $this->studyPlanModel->getUserPlans($_SESSION['user_id'], 'completed');
        
        // Get completion percentage for each plan
        foreach($activePlans as $plan) {
            $plan->completion_percentage = $this->studyPlanModel->getPlanCompletionPercentage($plan->id);
        }
        
        foreach($completedPlans as $plan) {
            $plan->completion_percentage = $this->studyPlanModel->getPlanCompletionPercentage($plan->id);
        }
        
        // Prepare data for view
        $data = [
            'activePlans' => $activePlans,
            'completedPlans' => $completedPlans
        ];
        
        $this->view('study_plans/index', $data);
    }
    
    /**
     * View single study plan
     *
     * @param int $id Plan ID
     */
    public function view($id) {
        // Get plan details
        $plan = $this->studyPlanModel->getPlanById($id);
        
        // Check if plan exists and belongs to user
        if(!$plan || $plan->user_id != $_SESSION['user_id']) {
            flash('plan_error', 'Study plan not found or access denied', 'alert alert-danger');
            redirect('study_plans');
        }
        
        // Get plan items
        $items = $this->studyPlanItemModel->getPlanItems($plan->id);
        
        // Get completion percentage
        $completionPercentage = $this->studyPlanModel->getPlanCompletionPercentage($plan->id);
        
        // Get time distribution by section
        $timeDistribution = $this->studyPlanItemModel->getTimeDistributionBySection($plan->id);
        
        // Get total study time
        $totalStudyTime = $this->studyPlanItemModel->getTotalStudyTime($plan->id);
        
        // Calculate days until plan end date
        $endDate = new DateTime($plan->end_date);
        $today = new DateTime();
        $daysRemaining = max(0, $today->diff($endDate)->days);
        
        // Group items by date
        $itemsByDate = [];
        
        foreach($items as $item) {
            $date = $item->scheduled_date;
            
            if(!isset($itemsByDate[$date])) {
                $itemsByDate[$date] = [];
            }
            
            $itemsByDate[$date][] = $item;
        }
        
        // Prepare data for view
        $data = [
            'plan' => $plan,
            'items' => $items,
            'itemsByDate' => $itemsByDate,
            'completionPercentage' => $completionPercentage,
            'timeDistribution' => $timeDistribution,
            'totalStudyTime' => $totalStudyTime,
            'daysRemaining' => $daysRemaining
        ];
        
        $this->view('study_plans/view', $data);
    }
    
    /**
     * Add a new study plan
     */
    public function add() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'name' => trim($_POST['name']),
                'start_date' => trim($_POST['start_date']),
                'end_date' => trim($_POST['end_date']),
                'name_err' => '',
                'start_date_err' => '',
                'end_date_err' => ''
            ];
            
            // Validate Name
            if(empty($data['name'])) {
                $data['name_err'] = 'Please enter plan name';
            }
            
            // Validate Start Date
            if(empty($data['start_date'])) {
                $data['start_date_err'] = 'Please enter start date';
            } elseif(!isValidDate($data['start_date'])) {
                $data['start_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            }
            
            // Validate End Date
            if(empty($data['end_date'])) {
                $data['end_date_err'] = 'Please enter end date';
            } elseif(!isValidDate($data['end_date'])) {
                $data['end_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            } elseif(strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                $data['end_date_err'] = 'End date must be after start date';
            }
            
            // Check for plan overlaps
            if(empty($data['start_date_err']) && empty($data['end_date_err'])) {
                if($this->studyPlanModel->checkOverlap($_SESSION['user_id'], $data['start_date'], $data['end_date'])) {
                    $data['start_date_err'] = 'This date range overlaps with an existing active plan';
                }
            }
            
            // Make sure errors are empty
            if(empty($data['name_err']) && empty($data['start_date_err']) && empty($data['end_date_err'])) {
                // Validated
                
                // Add study plan
                $planId = $this->studyPlanModel->addPlan(
                    $_SESSION['user_id'],
                    $data['name'],
                    $data['start_date'],
                    $data['end_date']
                );
                
                if($planId) {
                    flash('plan_success', 'Study plan added successfully. Now you can add items to your plan.');
                    redirect('study_plans/add_items/' . $planId);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('study_plans/add', $data);
            }
        } else {
            // Init data
            $data = [
                'name' => '',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+30 days')),
                'name_err' => '',
                'start_date_err' => '',
                'end_date_err' => ''
            ];
            
            // Load view
            $this->view('study_plans/add', $data);
        }
    }
    
    /**
     * Edit a study plan
     *
     * @param int $id Plan ID
     */
    public function edit($id) {
        // Get plan details
        $plan = $this->studyPlanModel->getPlanById($id);
        
        // Check if plan exists and belongs to user
        if(!$plan || $plan->user_id != $_SESSION['user_id']) {
            flash('plan_error', 'Study plan not found or access denied', 'alert alert-danger');
            redirect('study_plans');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'id' => $id,
                'name' => trim($_POST['name']),
                'start_date' => trim($_POST['start_date']),
                'end_date' => trim($_POST['end_date']),
                'status' => trim($_POST['status']),
                'name_err' => '',
                'start_date_err' => '',
                'end_date_err' => '',
                'status_err' => ''
            ];
            
            // Validate Name
            if(empty($data['name'])) {
                $data['name_err'] = 'Please enter plan name';
            }
            
            // Validate Start Date
            if(empty($data['start_date'])) {
                $data['start_date_err'] = 'Please enter start date';
            } elseif(!isValidDate($data['start_date'])) {
                $data['start_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            }
            
            // Validate End Date
            if(empty($data['end_date'])) {
                $data['end_date_err'] = 'Please enter end date';
            } elseif(!isValidDate($data['end_date'])) {
                $data['end_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            } elseif(strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                $data['end_date_err'] = 'End date must be after start date';
            }
            
            // Validate Status
            if(empty($data['status'])) {
                $data['status_err'] = 'Please select status';
            } elseif(!in_array($data['status'], ['active', 'completed', 'cancelled'])) {
                $data['status_err'] = 'Invalid status';
            }
            
            // Check for plan overlaps (only if status is active)
            if(empty($data['start_date_err']) && empty($data['end_date_err']) && $data['status'] == 'active') {
                if($this->studyPlanModel->checkOverlap($_SESSION['user_id'], $data['start_date'], $data['end_date'], $id)) {
                    $data['start_date_err'] = 'This date range overlaps with an existing active plan';
                }
            }
            
            // Make sure errors are empty
            if(empty($data['name_err']) && empty($data['start_date_err']) && 
               empty($data['end_date_err']) && empty($data['status_err'])) {
                // Validated
                
                // Update study plan
                $updateData = [
                    'name' => $data['name'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => $data['status']
                ];
                
                if($this->studyPlanModel->updatePlan($id, $updateData)) {
                    flash('plan_success', 'Study plan updated successfully');
                    redirect('study_plans/view/' . $id);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('study_plans/edit', $data);
            }
        } else {
            // Init data
            $data = [
                'id' => $id,
                'name' => $plan->name,
                'start_date' => $plan->start_date,
                'end_date' => $plan->end_date,
                'status' => $plan->status,
                'name_err' => '',
                'start_date_err' => '',
                'end_date_err' => '',
                'status_err' => ''
            ];
            
            // Load view
            $this->view('study_plans/edit', $data);
        }
    }
    
    /**
     * Delete a study plan
     *
     * @param int $id Plan ID
     */
    public function delete($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get plan details
            $plan = $this->studyPlanModel->getPlanById($id);
            
            // Check if plan exists and belongs to user
            if(!$plan || $plan->user_id != $_SESSION['user_id']) {
                flash('plan_error', 'Study plan not found or access denied', 'alert alert-danger');
                redirect('study_plans');
            }
            
            // Delete plan
            if($this->studyPlanModel->deletePlan($id)) {
                flash('plan_success', 'Study plan deleted successfully');
                redirect('study_plans');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('study_plans');
        }
    }
    
    /**
     * Update plan status
     *
     * @param int $id Plan ID
     * @param string $status New status
     */
    public function updateStatus($id, $status) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get plan details
            $plan = $this->studyPlanModel->getPlanById($id);
            
            // Check if plan exists and belongs to user
            if(!$plan || $plan->user_id != $_SESSION['user_id']) {
                flash('plan_error', 'Study plan not found or access denied', 'alert alert-danger');
                redirect('study_plans');
            }
            
            // Validate status
            if(!in_array($status, ['active', 'completed', 'cancelled'])) {
                flash('plan_error', 'Invalid status', 'alert alert-danger');
                redirect('study_plans/view/' . $id);
            }
            
            // Update status
            if($this->studyPlanModel->updateStatus($id, $status)) {
                flash('plan_success', 'Study plan status updated to ' . ucfirst($status));
                redirect('study_plans/view/' . $id);
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('study_plans/view/' . $id);
        }
    }
    
    /**
     * Add items to plan
     *
     * @param int $id Plan ID
     */
    public function addItems($id) {
        // Get plan details
        $plan = $this->studyPlanModel->getPlanById($id);
        
        // Check if plan exists and belongs to user
        if(!$plan || $plan->user_id != $_SESSION['user_id']) {
            flash('plan_error', 'Study plan not found or access denied', 'alert alert-danger');
            redirect('study_plans');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'plan_id' => $id,
                'section_id' => isset($_POST['section_id']) ? trim($_POST['section_id']) : '',
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'scheduled_date' => trim($_POST['scheduled_date']),
                'duration' => trim($_POST['duration']),
                'resource_id' => isset($_POST['resource_id']) ? trim($_POST['resource_id']) : null,
                'section_id_err' => '',
                'title_err' => '',
                'scheduled_date_err' => '',
                'duration_err' => ''
            ];
            
            // Validate Section ID if provided
            if(!empty($data['section_id'])) {
                $section = $this->sectionModel->getSectionById($data['section_id']);
                if(!$section) {
                    $data['section_id_err'] = 'Invalid section selected';
                }
            }
            
            // Validate Title
            if(empty($data['title'])) {
                $data['title_err'] = 'Please enter item title';
            }
            
            // Validate Scheduled Date
            if(empty($data['scheduled_date'])) {
                $data['scheduled_date_err'] = 'Please enter scheduled date';
            } elseif(!isValidDate($data['scheduled_date'])) {
                $data['scheduled_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            } elseif(strtotime($data['scheduled_date']) < strtotime($plan->start_date) || 
                     strtotime($data['scheduled_date']) > strtotime($plan->end_date)) {
                $data['scheduled_date_err'] = 'Scheduled date must be within plan date range';
            }
            
            // Validate Duration
            if(empty($data['duration'])) {
                $data['duration_err'] = 'Please enter duration in minutes';
            } elseif(!is_numeric($data['duration']) || $data['duration'] <= 0) {
                $data['duration_err'] = 'Please enter a valid duration in minutes';
            }
            
            // Make sure errors are empty
            if(empty($data['section_id_err']) && empty($data['title_err']) && 
               empty($data['scheduled_date_err']) && empty($data['duration_err'])) {
                // Validated
                
                // Add plan item
                if($this->studyPlanItemModel->addPlanItem(
                    $id,
                    $data['section_id'] ? $data['section_id'] : null,
                    $data['title'],
                    $data['description'],
                    $data['scheduled_date'],
                    $data['duration'],
                    false,
                    $data['resource_id'] ? $data['resource_id'] : null
                )) {
                    flash('item_success', 'Study plan item added successfully');
                    
                    // Redirect back to add items page to add more
                    redirect('study_plans/add_items/' . $id);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Get sections
                $sections = $this->sectionModel->getAllSections();
                $data['sections'] = $sections;
                
                // Get resources
                $resources = $this->resourceModel->getAllResources();
                $data['resources'] = $resources;
                
                // Add plan to data
                $data['plan'] = $plan;
                
                // Load view with errors
                $this->view('study_plans/add_items', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Get resources
            $resources = $this->resourceModel->getAllResources();
            
            // Init data
            $data = [
                'plan_id' => $id,
                'plan' => $plan,
                'section_id' => '',
                'title' => '',
                'description' => '',
                'scheduled_date' => '',
                'duration' => 60,
                'resource_id' => null,
                'section_id_err' => '',
                'title_err' => '',
                'scheduled_date_err' => '',
                'duration_err' => '',
                'sections' => $sections,
                'resources' => $resources
            ];
            
            // Pre-select date if provided in URL
            if(isset($_GET['date']) && isValidDate($_GET['date'])) {
                $data['scheduled_date'] = $_GET['date'];
            } else {
                // Default to plan start date
                $data['scheduled_date'] = $plan->start_date;
            }
            
            // Pre-select section if provided in URL
            if(isset($_GET['section']) && is_numeric($_GET['section'])) {
                $sectionId = intval($_GET['section']);
                if($this->sectionModel->getSectionById($sectionId)) {
                    $data['section_id'] = $sectionId;
                }
            }
            
            // Load view
            $this->view('study_plans/add_items', $data);
        }
    }
    
    /**
     * Edit a plan item
     *
     * @param int $id Item ID
     */
    public function editItem($id) {
        // Get item details
        $item = $this->studyPlanItemModel->getItemById($id);
        
        // Check if item exists
        if(!$item) {
            flash('item_error', 'Item not found', 'alert alert-danger');
            redirect('study_plans');
        }
        
        // Get plan details
        $plan = $this->studyPlanModel->getPlanById($item->study_plan_id);
        
        // Check if plan belongs to user
        if(!$plan || $plan->user_id != $_SESSION['user_id']) {
            flash('plan_error', 'Access denied', 'alert alert-danger');
            redirect('study_plans');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'id' => $id,
                'plan_id' => $item->study_plan_id,
                'section_id' => isset($_POST['section_id']) ? trim($_POST['section_id']) : '',
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'scheduled_date' => trim($_POST['scheduled_date']),
                'duration' => trim($_POST['duration']),
                'completed' => isset($_POST['completed']) ? 1 : 0,
                'resource_id' => isset($_POST['resource_id']) ? trim($_POST['resource_id']) : null,
                'section_id_err' => '',
                'title_err' => '',
                'scheduled_date_err' => '',
                'duration_err' => ''
            ];
            
            // Validate Section ID if provided
            if(!empty($data['section_id'])) {
                $section = $this->sectionModel->getSectionById($data['section_id']);
                if(!$section) {
                    $data['section_id_err'] = 'Invalid section selected';
                }
            }
            
            // Validate Title
            if(empty($data['title'])) {
                $data['title_err'] = 'Please enter item title';
            }
            
            // Validate Scheduled Date
            if(empty($data['scheduled_date'])) {
                $data['scheduled_date_err'] = 'Please enter scheduled date';
            } elseif(!isValidDate($data['scheduled_date'])) {
                $data['scheduled_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            } elseif(strtotime($data['scheduled_date']) < strtotime($plan->start_date) || 
                     strtotime($data['scheduled_date']) > strtotime($plan->end_date)) {
                $data['scheduled_date_err'] = 'Scheduled date must be within plan date range';
            }
            
            // Validate Duration
            if(empty($data['duration'])) {
                $data['duration_err'] = 'Please enter duration in minutes';
            } elseif(!is_numeric($data['duration']) || $data['duration'] <= 0) {
                $data['duration_err'] = 'Please enter a valid duration in minutes';
            }
            
            // Make sure errors are empty
            if(empty($data['section_id_err']) && empty($data['title_err']) && 
               empty($data['scheduled_date_err']) && empty($data['duration_err'])) {
                // Validated
                
                // Update plan item
                $updateData = [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'scheduled_date' => $data['scheduled_date'],
                    'duration' => $data['duration'],
                    'completed' => $data['completed'],
                    'resource_id' => $data['resource_id'] ? $data['resource_id'] : null
                ];
                
                if(!empty($data['section_id'])) {
                    $updateData['section_id'] = $data['section_id'];
                } else {
                    $updateData['section_id'] = null;
                }
                
                if($this->studyPlanItemModel->updateItem($id, $updateData)) {
                    flash('item_success', 'Study plan item updated successfully');
                    redirect('study_plans/view/' . $item->study_plan_id);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Get sections
                $sections = $this->sectionModel->getAllSections();
                $data['sections'] = $sections;
                
                // Get resources
                $resources = $this->resourceModel->getAllResources();
                $data['resources'] = $resources;
                
                // Add plan to data
                $data['plan'] = $plan;
                
                // Load view with errors
                $this->view('study_plans/edit_item', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Get resources
            $resources = $this->resourceModel->getAllResources();
            
            // Init data
            $data = [
                'id' => $id,
                'plan_id' => $item->study_plan_id,
                'plan' => $plan,
                'section_id' => $item->section_id,
                'title' => $item->title,
                'description' => $item->description,
                'scheduled_date' => $item->scheduled_date,
                'duration' => $item->duration,
                'completed' => $item->completed,
                'resource_id' => $item->resource_id,
                'section_id_err' => '',
                'title_err' => '',
                'scheduled_date_err' => '',
                'duration_err' => '',
                'sections' => $sections,
                'resources' => $resources
            ];
            
            // Load view
            $this->view('study_plans/edit_item', $data);
        }
    }
    
    /**
     * Delete a plan item
     *
     * @param int $id Item ID
     */
    public function deleteItem($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get item details
            $item = $this->studyPlanItemModel->getItemById($id);
            
            // Check if item exists
            if(!$item) {
                flash('item_error', 'Item not found', 'alert alert-danger');
                redirect('study_plans');
            }
            
            // Get plan details
            $plan = $this->studyPlanModel->getPlanById($item->study_plan_id);
            
            // Check if plan belongs to user
            if(!$plan || $plan->user_id != $_SESSION['user_id']) {
                flash('plan_error', 'Access denied', 'alert alert-danger');
                redirect('study_plans');
            }
            
            // Delete item
            if($this->studyPlanItemModel->deleteItem($id)) {
                flash('item_success', 'Study plan item deleted successfully');
                redirect('study_plans/view/' . $item->study_plan_id);
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('study_plans');
        }
    }
    
    /**
     * Mark item as completed/not completed
     *
     * @param int $id Item ID
     * @param bool $completed Completed status
     */
    public function markCompleted($id, $completed = 1) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get item details
            $item = $this->studyPlanItemModel->getItemById($id);
            
            // Check if item exists
            if(!$item) {
                flash('item_error', 'Item not found', 'alert alert-danger');
                redirect('study_plans');
            }
            
            // Get plan details
            $plan = $this->studyPlanModel->getPlanById($item->study_plan_id);
            
            // Check if plan belongs to user
            if(!$plan || $plan->user_id != $_SESSION['user_id']) {
                flash('plan_error', 'Access denied', 'alert alert-danger');
                redirect('study_plans');
            }
            
            // Update completion status
            if($this->studyPlanItemModel->markAsCompleted($id, $completed == 1)) {
                flash('item_success', 'Item ' . ($completed == 1 ? 'marked as completed' : 'marked as not completed'));
                redirect('study_plans/view/' . $item->study_plan_id);
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('study_plans');
        }
    }
    
    /**
     * Generate a study plan based on a test date
     */
    public function generate() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'test_date' => trim($_POST['test_date']),
                'plan_name' => trim($_POST['plan_name']),
                'test_date_err' => '',
                'plan_name_err' => ''
            ];
            
            // Validate Test Date
            if(empty($data['test_date'])) {
                $data['test_date_err'] = 'Please enter test date';
            } elseif(!isValidDate($data['test_date'])) {
                $data['test_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            } elseif(strtotime($data['test_date']) <= strtotime('today')) {
                $data['test_date_err'] = 'Test date must be in the future';
            }
            
            // Validate Plan Name
            if(empty($data['plan_name'])) {
                $data['plan_name_err'] = 'Please enter plan name';
            }
            
            // Make sure errors are empty
            if(empty($data['test_date_err']) && empty($data['plan_name_err'])) {
                // Validated
                
                // Generate plan
                $planId = $this->studyPlanModel->generatePlan(
                    $_SESSION['user_id'],
                    $data['test_date'],
                    $data['plan_name']
                );
                
                if($planId) {
                    flash('plan_success', 'Study plan generated successfully');
                    redirect('study_plans/view/' . $planId);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('study_plans/generate', $data);
            }
        } else {
            // Get user's test date if set
            $user = $this->userModel->getUserById($_SESSION['user_id']);
            $testDate = $user->test_date ?? date('Y-m-d', strtotime('+30 days'));
            
            // Generate default plan name
            $planName = 'Study Plan for IELTS Test on ' . date('j M Y', strtotime($testDate));
            
            // Init data
            $data = [
                'test_date' => $testDate,
                'plan_name' => $planName,
                'test_date_err' => '',
                'plan_name_err' => ''
            ];
            
            // Load view
            $this->view('study_plans/generate', $data);
        }
    }
    
    /**
     * View today's plan
     */
    public function today() {
        // Get today's items
        $todayItems = $this->studyPlanItemModel->getTodayItems($_SESSION['user_id']);
        
        // Get overdue items
        $overdueItems = $this->studyPlanItemModel->getOverdueItems($_SESSION['user_id']);
        
        // Prepare data for view
        $data = [
            'todayItems' => $todayItems,
            'overdueItems' => $overdueItems,
            'today' => date('Y-m-d')
        ];
        
        $this->view('study_plans/today', $data);
    }
    
    /**
     * View upcoming items
     */
    public function upcoming() {
        // Get number of days to look ahead
        $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
        
        // Validate days
        if($days < 1 || $days > 30) {
            $days = 7;
        }
        
        // Get upcoming items
        $upcomingItems = $this->studyPlanItemModel->getUpcomingItems($_SESSION['user_id'], $days);
        
        // Group items by date
        $itemsByDate = [];
        
        foreach($upcomingItems as $item) {
            $date = $item->scheduled_date;
            
            if(!isset($itemsByDate[$date])) {
                $itemsByDate[$date] = [];
            }
            
            $itemsByDate[$date][] = $item;
        }
        
        // Prepare data for view
        $data = [
            'upcomingItems' => $upcomingItems,
            'itemsByDate' => $itemsByDate,
            'days' => $days
        ];
        
        $this->view('study_plans/upcoming', $data);
    }
}