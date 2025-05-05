<?php
/**
 * Study Session Controller
 * Handles study session tracking functionality
 */
class StudySessionController extends Controller {
    private $studySessionModel;
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
        $this->studySessionModel = $this->model('StudySession');
        $this->sectionModel = $this->model('IeltsSection');
    }
    
    /**
     * Study sessions index page
     */
    public function index() {
        // Get user's study sessions
        $sessions = $this->studySessionModel->getUserSessions($_SESSION['user_id']);
        
        // Get active session if exists
        $activeSession = $this->studySessionModel->getActiveSession($_SESSION['user_id']);
        
        // Get study time statistics
        $totalTime = $this->studySessionModel->getTotalStudyTime($_SESSION['user_id']);
        $timeBySection = $this->studySessionModel->getTotalTimePerSection($_SESSION['user_id']);
        
        // Get IELTS sections
        $sections = $this->sectionModel->getAllSections();
        
        // Prepare data for view
        $data = [
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'totalTime' => $totalTime,
            'timeBySection' => $timeBySection,
            'sections' => $sections
        ];
        
        $this->view('study_sessions/index', $data);
    }
    
    /**
     * Start a new study session
     */
    public function start() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Check if section ID is provided
            if(!isset($_POST['section_id']) || empty($_POST['section_id'])) {
                flash('session_error', 'Please select a section', 'alert alert-danger');
                redirect('study_sessions');
            }
            
            // Validate section ID
            $sectionId = $_POST['section_id'];
            $section = $this->sectionModel->getSectionById($sectionId);
            
            if(!$section) {
                flash('session_error', 'Invalid section selected', 'alert alert-danger');
                redirect('study_sessions');
            }
            
            // Check if user already has an active session
            $activeSession = $this->studySessionModel->getActiveSession($_SESSION['user_id']);
            
            if($activeSession) {
                flash('session_error', 'You already have an active study session. Please end it before starting a new one.', 'alert alert-danger');
                redirect('study_sessions');
            }
            
            // Start new session
            $sessionId = $this->studySessionModel->startSession($_SESSION['user_id'], $sectionId);
            
            if($sessionId) {
                flash('session_success', 'Study session started successfully');
                redirect('study_sessions');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('study_sessions');
        }
    }
    
    /**
     * End an active study session
     *
     * @param int $id Session ID
     */
    public function end($id = null) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get active session if ID not provided
            if($id === null) {
                $activeSession = $this->studySessionModel->getActiveSession($_SESSION['user_id']);
                
                if($activeSession) {
                    $id = $activeSession->id;
                } else {
                    flash('session_error', 'No active study session found', 'alert alert-danger');
                    redirect('study_sessions');
                }
            }
            
            // Get session details
            $session = $this->studySessionModel->getSessionById($id);
            
            // Check if session exists and belongs to user
            if(!$session || $session->user_id != $_SESSION['user_id']) {
                flash('session_error', 'Session not found or access denied', 'alert alert-danger');
                redirect('study_sessions');
            }
            
            // Check if session is already ended
            if($session->end_time !== null) {
                flash('session_error', 'This session is already ended', 'alert alert-danger');
                redirect('study_sessions');
            }
            
            // End session
            if($this->studySessionModel->endSession($id)) {
                flash('session_success', 'Study session ended successfully');
                redirect('study_sessions');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('study_sessions');
        }
    }
    
    /**
     * Delete a study session
     *
     * @param int $id Session ID
     */
    public function delete($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get session details
            $session = $this->studySessionModel->getSessionById($id);
            
            // Check if session exists and belongs to user
            if(!$session || $session->user_id != $_SESSION['user_id']) {
                flash('session_error', 'Session not found or access denied', 'alert alert-danger');
                redirect('study_sessions');
            }
            
            // Delete session
            if($this->studySessionModel->deleteSession($id)) {
                flash('session_success', 'Study session deleted successfully');
                redirect('study_sessions');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('study_sessions');
        }
    }
    
    /**
     * View study session details
     *
     * @param int $id Session ID
     */
    public function view($id) {
        // Get session details
        $session = $this->studySessionModel->getSessionById($id);
        
        // Check if session exists and belongs to user
        if(!$session || $session->user_id != $_SESSION['user_id']) {
            flash('session_error', 'Session not found or access denied', 'alert alert-danger');
            redirect('study_sessions');
        }
        
        // Calculate duration if session is still active
        if($session->end_time === null) {
            $startTime = new DateTime($session->start_time);
            $now = new DateTime();
            $interval = $startTime->diff($now);
            $durationMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            $session->current_duration = $durationMinutes;
        }
        
        // Prepare data for view
        $data = [
            'session' => $session
        ];
        
        $this->view('study_sessions/view', $data);
    }
    
    /**
     * Show study time analytics
     */
    public function analytics() {
        // Get date range
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-30 days'));
        
        if(isset($_GET['start_date']) && isset($_GET['end_date'])) {
            if(isValidDate($_GET['start_date']) && isValidDate($_GET['end_date'])) {
                $startDate = $_GET['start_date'];
                $endDate = $_GET['end_date'];
            }
        }
        
        // Get sessions by date range
        $sessions = $this->studySessionModel->getSessionsByDateRange($_SESSION['user_id'], $startDate, $endDate);
        
        // Calculate daily study time
        $dailyStudyTime = [];
        $currentDate = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        
        // Initialize days
        while($currentDate <= $endDateTime) {
            $dateKey = $currentDate->format('Y-m-d');
            $dailyStudyTime[$dateKey] = 0;
            $currentDate->modify('+1 day');
        }
        
        // Fill in study time
        foreach($sessions as $session) {
            if($session->duration) {
                $sessionDate = date('Y-m-d', strtotime($session->start_time));
                if(isset($dailyStudyTime[$sessionDate])) {
                    $dailyStudyTime[$sessionDate] += $session->duration;
                }
            }
        }
        
        // Get study time by section
        $studyTimeBySection = $this->studySessionModel->getTotalTimePerSection($_SESSION['user_id'], $startDate, $endDate);
        
        // Prepare data for view
        $data = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sessions' => $sessions,
            'dailyStudyTime' => $dailyStudyTime,
            'studyTimeBySection' => $studyTimeBySection
        ];
        
        $this->view('study_sessions/analytics', $data);
    }
    
    /**
     * Show study sessions by section
     *
     * @param int $sectionId Section ID
     */
    public function bySection($sectionId) {
        // Validate section ID
        $section = $this->sectionModel->getSectionById($sectionId);
        
        if(!$section) {
            flash('section_error', 'Section not found', 'alert alert-danger');
            redirect('study_sessions');
        }
        
        // Get section sessions
        $sessions = $this->studySessionModel->getUserSessionsBySection($_SESSION['user_id'], $sectionId);
        
        // Calculate total study time for section
        $totalTime = 0;
        foreach($sessions as $session) {
            if($session->duration) {
                $totalTime += $session->duration;
            }
        }
        
        // Prepare data for view
        $data = [
            'section' => $section,
            'sessions' => $sessions,
            'totalTime' => $totalTime
        ];
        
        $this->view('study_sessions/by_section', $data);
    }
    
    /**
     * Add a manual study session
     */
    public function addManual() {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Process form
            
            // Sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Init data
            $data = [
                'section_id' => trim($_POST['section_id']),
                'date' => trim($_POST['date']),
                'duration' => trim($_POST['duration']),
                'section_id_err' => '',
                'date_err' => '',
                'duration_err' => ''
            ];
            
            // Validate Section ID
            if(empty($data['section_id'])) {
                $data['section_id_err'] = 'Please select a section';
            } else {
                $section = $this->sectionModel->getSectionById($data['section_id']);
                if(!$section) {
                    $data['section_id_err'] = 'Invalid section selected';
                }
            }
            
            // Validate Date
            if(empty($data['date'])) {
                $data['date_err'] = 'Please enter a date';
            } elseif(!isValidDate($data['date'])) {
                $data['date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            }
            
            // Validate Duration
            if(empty($data['duration'])) {
                $data['duration_err'] = 'Please enter duration in minutes';
            } elseif(!is_numeric($data['duration']) || $data['duration'] <= 0) {
                $data['duration_err'] = 'Please enter a valid duration in minutes';
            }
            
            // Make sure errors are empty
            if(empty($data['section_id_err']) && empty($data['date_err']) && empty($data['duration_err'])) {
                // Validated
                
                // Create start and end times
                $startTime = $data['date'] . ' 00:00:00';
                $endTime = $data['date'] . ' 00:00:00';
                
                // Add session directly in model
                $this->db->query("INSERT INTO study_sessions (user_id, section_id, start_time, end_time, duration) 
                                VALUES (:user_id, :section_id, :start_time, :end_time, :duration)");
                
                $this->db->bind(':user_id', $_SESSION['user_id']);
                $this->db->bind(':section_id', $data['section_id']);
                $this->db->bind(':start_time', $startTime);
                $this->db->bind(':end_time', $endTime);
                $this->db->bind(':duration', $data['duration']);
                
                if($this->db->execute()) {
                    flash('session_success', 'Manual study session added successfully');
                    redirect('study_sessions');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Get sections for form
                $sections = $this->sectionModel->getAllSections();
                $data['sections'] = $sections;
                
                // Load view with errors
                $this->view('study_sessions/add_manual', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Init data
            $data = [
                'section_id' => '',
                'date' => date('Y-m-d'),
                'duration' => '',
                'section_id_err' => '',
                'date_err' => '',
                'duration_err' => '',
                'sections' => $sections
            ];
            
            // Load view
            $this->view('study_sessions/add_manual', $data);
        }
    }
}