<?php
/**
 * Dashboard Controller
 * Handles the main dashboard page and overview functionality
 */
class DashboardController extends Controller {
    private $userModel;
    private $practiceTestModel;
    private $testScoreModel;
    private $studySessionModel;
    private $weakAreaModel;
    private $studyGoalModel;
    private $studyPlanItemModel;
    
    /**
     * Constructor - Initialize models
     */
    public function __construct() {
        // Check if user is logged in for all methods
        if(!isLoggedIn()) {
            redirect('users/login');
        }
        
        // Load models
        $this->userModel = $this->model('User');
        $this->practiceTestModel = $this->model('PracticeTest');
        $this->testScoreModel = $this->model('TestScore');
        $this->studySessionModel = $this->model('StudySession');
        $this->weakAreaModel = $this->model('WeakArea');
        $this->studyGoalModel = $this->model('StudyGoal');
        $this->studyPlanItemModel = $this->model('StudyPlanItem');
    }
    
    /**
     * Dashboard index page
     */
    public function index() {
        // Get user info
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        
        // Get days until test
        $daysUntilTest = $this->userModel->getDaysUntilTest($_SESSION['user_id']);
        
        // Get latest test scores
        $latestScores = $this->testScoreModel->getLatestSectionScores($_SESSION['user_id']);
        
        // Get active study session
        $activeSession = $this->studySessionModel->getActiveSession($_SESSION['user_id']);
        
        // Get today's plan items
        $todayItems = $this->studyPlanItemModel->getTodayItems($_SESSION['user_id']);
        
        // Get top weak areas
        $weakAreas = $this->weakAreaModel->getTopWeakAreas($_SESSION['user_id'], 3);
        
        // Get total study time this week
        $weeklyStudyTime = $this->studySessionModel->getTotalStudyTime($_SESSION['user_id'], 'week');
        
        // Get recent progress
        $latestTest = $this->practiceTestModel->getLatestTest($_SESSION['user_id']);
        $recentScores = [];
        
        if($latestTest) {
            $recentScores = $this->testScoreModel->getTestScores($latestTest->id);
        }
        
        // Get upcoming goals
        $upcomingGoals = $this->studyGoalModel->getUpcomingGoals($_SESSION['user_id']);
        
        // Prepare data for view
        $data = [
            'user' => $user,
            'daysUntilTest' => $daysUntilTest,
            'latestScores' => $latestScores,
            'activeSession' => $activeSession,
            'todayItems' => $todayItems,
            'weakAreas' => $weakAreas,
            'weeklyStudyTime' => $weeklyStudyTime,
            'recentScores' => $recentScores,
            'upcomingGoals' => $upcomingGoals
        ];
        
        $this->view('dashboard/index', $data);
    }
    
    /**
     * Show progress overview
     */
    public function progress() {
        // Get all practice tests
        $tests = $this->practiceTestModel->getUserTests($_SESSION['user_id']);
        
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
            'tests' => $tests,
            'readingScores' => $readingScores,
            'writingScores' => $writingScores,
            'listeningScores' => $listeningScores,
            'speakingScores' => $speakingScores,
            'studyTimeBySection' => $studyTimeBySection,
            'weakAreasBySection' => $weakAreasBySection,
            'goalProgress' => $goalProgress
        ];
        
        $this->view('dashboard/progress', $data);
    }
    
    /**
     * Show study analytics
     */
    public function analytics() {
        // Get start and end dates for analysis
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-3 months'));
        
        if(isset($_GET['start_date']) && isset($_GET['end_date'])) {
            if(isValidDate($_GET['start_date']) && isValidDate($_GET['end_date'])) {
                $startDate = $_GET['start_date'];
                $endDate = $_GET['end_date'];
            }
        }
        
        // Get practice tests in date range
        $tests = $this->practiceTestModel->getTestsBetweenDates($_SESSION['user_id'], $startDate, $endDate);
        
        // Get study sessions in date range
        $sessions = $this->studySessionModel->getSessionsByDateRange($_SESSION['user_id'], $startDate, $endDate);
        
        // Calculate study time by week
        $weeklyStudyTime = [];
        $currentWeek = date('W', strtotime($startDate));
        $currentYear = date('Y', strtotime($startDate));
        $endWeek = date('W', strtotime($endDate));
        $endYear = date('Y', strtotime($endDate));
        
        // Initialize weeks
        while($currentYear < $endYear || ($currentYear == $endYear && $currentWeek <= $endWeek)) {
            $weekKey = $currentYear . '-W' . str_pad($currentWeek, 2, '0', STR_PAD_LEFT);
            $weeklyStudyTime[$weekKey] = 0;
            
            $currentWeek++;
            if($currentWeek > 52) {
                $currentWeek = 1;
                $currentYear++;
            }
        }
        
        // Fill in study time
        foreach($sessions as $session) {
            if($session->duration) {
                $sessionWeek = date('Y-W', strtotime($session->start_time));
                if(isset($weeklyStudyTime[$sessionWeek])) {
                    $weeklyStudyTime[$sessionWeek] += $session->duration;
                }
            }
        }
        
        // Calculate test score trends
        $scoresByDate = [];
        foreach($tests as $test) {
            $testScores = $this->testScoreModel->getTestScores($test->id);
            $overallScore = $this->testScoreModel->calculateOverallScore($test->id);
            
            $scoresByDate[date('Y-m-d', strtotime($test->test_date))] = [
                'overall' => $overallScore,
                'scores' => $testScores
            ];
        }
        
        // Identify strongest and weakest sections
        $sectionAvgScores = [
            1 => 0, // Reading
            2 => 0, // Writing
            3 => 0, // Listening
            4 => 0  // Speaking
        ];
        
        $sectionScoreCount = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0
        ];
        
        // Calculate average scores by section
        foreach($scoresByDate as $dateScores) {
            foreach($dateScores['scores'] as $score) {
                if(isset($sectionAvgScores[$score->section_id])) {
                    $sectionAvgScores[$score->section_id] += $score->score;
                    $sectionScoreCount[$score->section_id]++;
                }
            }
        }
        
        // Calculate averages
        foreach($sectionAvgScores as $sectionId => $total) {
            if($sectionScoreCount[$sectionId] > 0) {
                $sectionAvgScores[$sectionId] = $total / $sectionScoreCount[$sectionId];
            }
        }
        
        // Sort to find strongest and weakest
        arsort($sectionAvgScores);
        $strongestSection = key($sectionAvgScores);
        
        asort($sectionAvgScores);
        $weakestSection = key($sectionAvgScores);
        
        // Get section names
        $sectionModel = $this->model('IeltsSection');
        $sections = $sectionModel->getAllSections();
        $sectionNames = [];
        
        foreach($sections as $section) {
            $sectionNames[$section->id] = $section->name;
        }
        
        // Prepare data for view
        $data = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tests' => $tests,
            'sessions' => $sessions,
            'weeklyStudyTime' => $weeklyStudyTime,
            'scoresByDate' => $scoresByDate,
            'sectionAvgScores' => $sectionAvgScores,
            'strongestSection' => $strongestSection,
            'weakestSection' => $weakestSection,
            'sectionNames' => $sectionNames
        ];
        
        $this->view('dashboard/analytics', $data);
    }
    
    /**
     * Show summary for specific test date preparation
     */
    public function testPrepSummary() {
        // Get user's test date
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        
        if(!$user->test_date) {
            flash('test_prep_error', 'Please set your IELTS test date in your profile first', 'alert alert-danger');
            redirect('users/profile');
        }
        
        // Calculate days remaining
        $testDate = new DateTime($user->test_date);
        $today = new DateTime();
        $daysRemaining = $today->diff($testDate)->days;
        
        if($testDate < $today) {
            $daysRemaining = 0;
        }
        
        // Get current scores
        $latestScores = $this->testScoreModel->getLatestSectionScores($_SESSION['user_id']);
        
        // Get goals
        $goals = $this->studyGoalModel->getUserGoals($_SESSION['user_id']);
        
        // Get weak areas
        $weakAreas = $this->weakAreaModel->getUserWeakAreas($_SESSION['user_id']);
        
        // Get study plan items coming up
        $upcomingItems = $this->studyPlanItemModel->getUpcomingItems($_SESSION['user_id'], 14);
        
        // Calculate study time needed per section
        $studyTimeNeeded = [];
        $sectionModel = $this->model('IeltsSection');
        $sections = $sectionModel->getAllSections();
        
        foreach($sections as $section) {
            // Find current score for this section
            $currentScore = 0;
            foreach($latestScores as $score) {
                if(isset($score->section_id) && $score->section_id == $section->id) {
                    $currentScore = $score->score ?? 0;
                    break;
                }
            }
            
            // Find target score for this section
            $targetScore = $user->target_score; // Default to overall target
            foreach($goals as $goal) {
                if(isset($goal->section_id) && $goal->section_id == $section->id) {
                    $targetScore = $goal->target_score;
                    break;
                }
            }
            
            // Calculate gap
            $scoreGap = max(0, $targetScore - $currentScore);
            
            // Calculate recommended study hours (simple formula: 20 hours per 1.0 band score gap)
            $recommendedHours = ceil($scoreGap * 20);
            
            // Calculate daily study time
            $dailyStudyMinutes = $daysRemaining > 0 ? ceil(($recommendedHours * 60) / $daysRemaining) : 0;
            
            $studyTimeNeeded[$section->id] = [
                'name' => $section->name,
                'current_score' => $currentScore,
                'target_score' => $targetScore,
                'score_gap' => $scoreGap,
                'recommended_hours' => $recommendedHours,
                'daily_minutes' => $dailyStudyMinutes
            ];
        }
        
        // Prepare data for view
        $data = [
            'user' => $user,
            'daysRemaining' => $daysRemaining,
            'latestScores' => $latestScores,
            'goals' => $goals,
            'weakAreas' => $weakAreas,
            'upcomingItems' => $upcomingItems,
            'studyTimeNeeded' => $studyTimeNeeded
        ];
        
        $this->view('dashboard/test_prep_summary', $data);
    }
}