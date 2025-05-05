<?php
/**
 * Resource Controller
 * Handles study resources management
 */
class ResourceController extends Controller {
    private $studyResourceModel;
    private $userResourceModel;
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
        $this->studyResourceModel = $this->model('StudyResource');
        $this->userResourceModel = $this->model('UserResource');
        $this->sectionModel = $this->model('IeltsSection');
    }
    
    /**
     * Resources index page
     */
    public function index() {
        // Get all resources
        $resources = $this->studyResourceModel->getAllResources();
        
        // Get user's resources
        $userResources = $this->userResourceModel->getUserResources($_SESSION['user_id'], false);
        
        // Create a lookup array of resource IDs the user has
        $userResourceIds = [];
        foreach($userResources as $resource) {
            $userResourceIds[$resource->resource_id] = $resource->id;
        }
        
        // Get resource types
        $resourceTypes = $this->studyResourceModel->getResourceTypes();
        
        // Get sections
        $sections = $this->sectionModel->getAllSections();
        
        // Prepare data for view
        $data = [
            'resources' => $resources,
            'userResourceIds' => $userResourceIds,
            'resourceTypes' => $resourceTypes,
            'sections' => $sections
        ];
        
        $this->view('resources/index', $data);
    }
    
    /**
     * View single resource
     *
     * @param int $id Resource ID
     */
    public function view($id) {
        // Get resource details
        $resource = $this->studyResourceModel->getResourceById($id);
        
        // Check if resource exists
        if(!$resource) {
            flash('resource_error', 'Resource not found', 'alert alert-danger');
            redirect('resources');
        }
        
        // Check if user has this resource
        $userResource = $this->userResourceModel->getUserResource($_SESSION['user_id'], $id);
        
        // Prepare data for view
        $data = [
            'resource' => $resource,
            'userResource' => $userResource
        ];
        
        $this->view('resources/view', $data);
    }
    
    /**
     * Add a new resource
     */
    public function add() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'title' => trim($_POST['title']),
                'section_id' => trim($_POST['section_id']),
                'resource_type' => trim($_POST['resource_type']),
                'description' => trim($_POST['description']),
                'file_path' => '',
                'title_err' => '',
                'section_id_err' => '',
                'resource_type_err' => '',
                'file_err' => ''
            ];
            
            // Handle file upload if present
            if(isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] != UPLOAD_ERR_NO_FILE) {
                $fileResult = $this->handleFileUpload('resource_file');
                
                if($fileResult['success']) {
                    $data['file_path'] = $fileResult['file_path'];
                } else {
                    $data['file_err'] = $fileResult['error'];
                }
            }
            
            // Validate Title
            if(empty($data['title'])) {
                $data['title_err'] = 'Please enter resource title';
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
            
            // Validate Resource Type
            if(empty($data['resource_type'])) {
                $data['resource_type_err'] = 'Please enter resource type';
            }
            
            // Make sure errors are empty
            if(empty($data['title_err']) && empty($data['section_id_err']) && empty($data['resource_type_err']) && empty($data['file_err'])) {
                // Validated
                
                // Add resource
                $resourceId = $this->studyResourceModel->addResource(
                    $data['title'],
                    $data['section_id'],
                    $data['resource_type'],
                    $data['description'],
                    $data['file_path']
                );
                
                if($resourceId) {
                    // Add to user's resources
                    $this->userResourceModel->addUserResource($_SESSION['user_id'], $resourceId);
                    
                    flash('resource_success', 'Resource added successfully');
                    redirect('resources');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Get sections for form
                $sections = $this->sectionModel->getAllSections();
                $data['sections'] = $sections;
                
                // Get resource types
                $resourceTypes = $this->studyResourceModel->getResourceTypes();
                $data['resourceTypes'] = $resourceTypes;
                
                // Load view with errors
                $this->view('resources/add', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Get resource types
            $resourceTypes = $this->studyResourceModel->getResourceTypes();
            
            // Init data
            $data = [
                'title' => '',
                'section_id' => '',
                'resource_type' => '',
                'description' => '',
                'file_path' => '',
                'title_err' => '',
                'section_id_err' => '',
                'resource_type_err' => '',
                'file_err' => '',
                'sections' => $sections,
                'resourceTypes' => $resourceTypes
            ];
            
            // Load view
            $this->view('resources/add', $data);
        }
    }
    
    /**
     * Edit a resource
     *
     * @param int $id Resource ID
     */
    public function edit($id) {
        // Get resource details
        $resource = $this->studyResourceModel->getResourceById($id);
        
        // Check if resource exists
        if(!$resource) {
            flash('resource_error', 'Resource not found', 'alert alert-danger');
            redirect('resources');
        }
        
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'id' => $id,
                'title' => trim($_POST['title']),
                'section_id' => trim($_POST['section_id']),
                'resource_type' => trim($_POST['resource_type']),
                'description' => trim($_POST['description']),
                'file_path' => $resource->file_path,
                'title_err' => '',
                'section_id_err' => '',
                'resource_type_err' => '',
                'file_err' => ''
            ];
            
            // Handle file upload if present
            if(isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] != UPLOAD_ERR_NO_FILE) {
                $fileResult = $this->handleFileUpload('resource_file');
                
                if($fileResult['success']) {
                    $data['file_path'] = $fileResult['file_path'];
                } else {
                    $data['file_err'] = $fileResult['error'];
                }
            }
            
            // Validate Title
            if(empty($data['title'])) {
                $data['title_err'] = 'Please enter resource title';
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
            
            // Validate Resource Type
            if(empty($data['resource_type'])) {
                $data['resource_type_err'] = 'Please enter resource type';
            }
            
            // Make sure errors are empty
            if(empty($data['title_err']) && empty($data['section_id_err']) && empty($data['resource_type_err']) && empty($data['file_err'])) {
                // Validated
                
                // Update resource
                $updateData = [
                    'title' => $data['title'],
                    'section_id' => $data['section_id'],
                    'resource_type' => $data['resource_type'],
                    'description' => $data['description'],
                    'file_path' => $data['file_path']
                ];
                
                if($this->studyResourceModel->updateResource($id, $updateData)) {
                    flash('resource_success', 'Resource updated successfully');
                    redirect('resources/view/' . $id);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Get sections for form
                $sections = $this->sectionModel->getAllSections();
                $data['sections'] = $sections;
                
                // Get resource types
                $resourceTypes = $this->studyResourceModel->getResourceTypes();
                $data['resourceTypes'] = $resourceTypes;
                
                // Load view with errors
                $this->view('resources/edit', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Get resource types
            $resourceTypes = $this->studyResourceModel->getResourceTypes();
            
            // Init data
            $data = [
                'id' => $id,
                'title' => $resource->title,
                'section_id' => $resource->section_id,
                'resource_type' => $resource->resource_type,
                'description' => $resource->description,
                'file_path' => $resource->file_path,
                'title_err' => '',
                'section_id_err' => '',
                'resource_type_err' => '',
                'file_err' => '',
                'sections' => $sections,
                'resourceTypes' => $resourceTypes
            ];
            
            // Load view
            $this->view('resources/edit', $data);
        }
    }
    
    /**
     * Delete a resource
     *
     * @param int $id Resource ID
     */
    public function delete($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get resource details
            $resource = $this->studyResourceModel->getResourceById($id);
            
            // Check if resource exists
            if(!$resource) {
                flash('resource_error', 'Resource not found', 'alert alert-danger');
                redirect('resources');
            }
            
            // Delete file if exists
            if(!empty($resource->file_path) && file_exists(UPLOAD_DIR . $resource->file_path)) {
                unlink(UPLOAD_DIR . $resource->file_path);
            }
            
            // Delete resource
            if($this->studyResourceModel->deleteResource($id)) {
                flash('resource_success', 'Resource deleted successfully');
                redirect('resources');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('resources');
        }
    }
    
    /**
     * Add resource to user's collection
     *
     * @param int $id Resource ID
     */
    public function addToCollection($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get resource details
            $resource = $this->studyResourceModel->getResourceById($id);
            
            // Check if resource exists
            if(!$resource) {
                flash('resource_error', 'Resource not found', 'alert alert-danger');
                redirect('resources');
            }
            
            // Check if user already has this resource
            if($this->userResourceModel->getUserResource($_SESSION['user_id'], $id)) {
                flash('resource_error', 'This resource is already in your collection', 'alert alert-warning');
                redirect('resources');
            }
            
            // Add to user's collection
            if($this->userResourceModel->addUserResource($_SESSION['user_id'], $id)) {
                flash('resource_success', 'Resource added to your collection');
                redirect('resources/my_resources');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('resources');
        }
    }
    
    /**
     * Remove resource from user's collection
     *
     * @param int $id User resource ID
     */
    public function removeFromCollection($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get user resource details
            $userResource = $this->userResourceModel->getUserResourceById($id);
            
            // Check if user resource exists and belongs to user
            if(!$userResource || $userResource->user_id != $_SESSION['user_id']) {
                flash('resource_error', 'Resource not found or access denied', 'alert alert-danger');
                redirect('resources/my_resources');
            }
            
            // Remove from collection
            if($this->userResourceModel->removeUserResource($id)) {
                flash('resource_success', 'Resource removed from your collection');
                redirect('resources/my_resources');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('resources/my_resources');
        }
    }
    
    /**
     * Mark resource as completed/not completed
     *
     * @param int $id User resource ID
     * @param bool $completed Completed status
     */
    public function markCompleted($id, $completed = 1) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get user resource details
            $userResource = $this->userResourceModel->getUserResourceById($id);
            
            // Check if user resource exists and belongs to user
            if(!$userResource || $userResource->user_id != $_SESSION['user_id']) {
                flash('resource_error', 'Resource not found or access denied', 'alert alert-danger');
                redirect('resources/my_resources');
            }
            
            // Update completion status
            if($this->userResourceModel->markAsCompleted($id, $completed == 1)) {
                flash('resource_success', 'Resource ' . ($completed == 1 ? 'marked as completed' : 'marked as not completed'));
                redirect('resources/my_resources');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('resources/my_resources');
        }
    }
    
    /**
     * Rate a resource
     *
     * @param int $id User resource ID
     */
    public function rate($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get user resource details
            $userResource = $this->userResourceModel->getUserResourceById($id);
            
            // Check if user resource exists and belongs to user
            if(!$userResource || $userResource->user_id != $_SESSION['user_id']) {
                flash('resource_error', 'Resource not found or access denied', 'alert alert-danger');
                redirect('resources/my_resources');
            }
            
            // Get rating from POST
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            
            // Validate rating
            if($rating < 1 || $rating > 5) {
                flash('resource_error', 'Invalid rating value (1-5)', 'alert alert-danger');
                redirect('resources/my_resources');
            }
            
            // Update rating
            if($this->userResourceModel->rateResource($id, $rating)) {
                flash('resource_success', 'Resource rated successfully');
                redirect('resources/my_resources');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('resources/my_resources');
        }
    }
    
    /**
     * Show user's resources
     */
    public function myResources() {
        // Get user's resources
        $userResources = $this->userResourceModel->getUserResources($_SESSION['user_id']);
        
        // Get sections
        $sections = $this->sectionModel->getAllSections();
        
        // Get resource types
        $resourceTypes = $this->studyResourceModel->getResourceTypes();
        
        // Prepare data for view
        $data = [
            'userResources' => $userResources,
            'sections' => $sections,
            'resourceTypes' => $resourceTypes
        ];
        
        $this->view('resources/my_resources', $data);
    }
    
    /**
     * Show user's completed resources
     */
    public function completed() {
        // Get user's completed resources
        $completedResources = $this->userResourceModel->getCompletedResources($_SESSION['user_id']);
        
        // Prepare data for view
        $data = [
            'completedResources' => $completedResources
        ];
        
        $this->view('resources/completed', $data);
    }
    
    /**
     * Search resources
     */
    public function search() {
        // Check if search query exists
        if(!isset($_GET['q']) || empty($_GET['q'])) {
            redirect('resources');
        }
        
        $keyword = $_GET['q'];
        $sectionId = isset($_GET['section_id']) && !empty($_GET['section_id']) ? $_GET['section_id'] : null;
        $type = isset($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : null;
        
        // Search resources
        $resources = $this->studyResourceModel->searchResources($keyword, $sectionId, $type);
        
        // Get user's resources
        $userResources = $this->userResourceModel->getUserResources($_SESSION['user_id'], false);
        
        // Create a lookup array of resource IDs the user has
        $userResourceIds = [];
        foreach($userResources as $resource) {
            $userResourceIds[$resource->resource_id] = $resource->id;
        }
        
        // Get sections
        $sections = $this->sectionModel->getAllSections();
        
        // Get resource types
        $resourceTypes = $this->studyResourceModel->getResourceTypes();
        
        // Prepare data for view
        $data = [
            'resources' => $resources,
            'userResourceIds' => $userResourceIds,
            'sections' => $sections,
            'resourceTypes' => $resourceTypes,
            'keyword' => $keyword,
            'selectedSection' => $sectionId,
            'selectedType' => $type
        ];
        
        $this->view('resources/search', $data);
    }
    
    /**
     * Handle file upload
     *
     * @param string $fileInputName Name of the file input field
     * @return array Result array with status and file path or error
     */
    private function handleFileUpload($fileInputName) {
        // Check if upload directory exists, create if not
        if(!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        
        // Get allowed file types
        $allowedTypes = unserialize(ALLOWED_FILE_TYPES);
        
        // Check file size
        if($_FILES[$fileInputName]['size'] > MAX_UPLOAD_SIZE) {
            return [
                'success' => false,
                'error' => 'File size exceeds the limit of ' . (MAX_UPLOAD_SIZE / 1048576) . 'MB'
            ];
        }
        
        // Check file type
        if(!in_array($_FILES[$fileInputName]['type'], $allowedTypes)) {
            return [
                'success' => false,
                'error' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)
            ];
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('resource_') . '.' . $fileExtension;
        $targetFile = UPLOAD_DIR . $newFileName;
        
        // Upload file
        if(move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFile)) {
            return [
                'success' => true,
                'file_path' => $newFileName
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to upload file'
            ];
        }
    }
}