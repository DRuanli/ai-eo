<?php
/**
 * Practice Controller
 * Handles practice tests and scores management
 */
class PracticeController extends Controller {
    private $practiceTestModel;
    private $testScoreModel;
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
        $this->practiceTestModel = $this->model('PracticeTest');
        $this->testScoreModel = $this->model('TestScore');
        $this->sectionModel = $this->model('IeltsSection');
    }
    
    /**
     * Practice tests index page
     */
    public function index() {
        // Get all user's practice tests
        $tests = $this->practiceTestModel->getUserTests($_SESSION['user_id']);
        
        // Calculate overall scores
        foreach($tests as $test) {
            $test->overall_score = $this->testScoreModel->calculateOverallScore($test->id);
        }
        
        // Prepare data for view
        $data = [
            'tests' => $tests
        ];
        
        $this->view('practice/index', $data);
    }
    
    /**
     * View single practice test
     *
     * @param int $id Test ID
     */
    public function view($id) {
        // Get test details
        $test = $this->practiceTestModel->getTestById($id);
        
        // Check if test exists and belongs to user
        if(!$test || $test->user_id != $_SESSION['user_id']) {
            flash('test_error', 'Test not found or access denied', 'alert alert-danger');
            redirect('practice');
        }
        
        // Get test scores
        $scores = $this->testScoreModel->getTestScores($test->id);
        
        // Calculate overall score
        $overallScore = $this->testScoreModel->calculateOverallScore($test->id);
        
        // Prepare data for view
        $data = [
            'test' => $test,
            'scores' => $scores,
            'overall_score' => $overallScore
        ];
        
        $this->view('practice/view', $data);
    }
    
    /**
     * Add new practice test
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
                'test_date' => trim($_POST['test_date']),
                'notes' => trim($_POST['notes']),
                'reading_score' => isset($_POST['reading_score']) ? trim($_POST['reading_score']) : '',
                'writing_score' => isset($_POST['writing_score']) ? trim($_POST['writing_score']) : '',
                'listening_score' => isset($_POST['listening_score']) ? trim($_POST['listening_score']) : '',
                'speaking_score' => isset($_POST['speaking_score']) ? trim($_POST['speaking_score']) : '',
                'reading_time' => isset($_POST['reading_time']) ? trim($_POST['reading_time']) : '',
                'writing_time' => isset($_POST['writing_time']) ? trim($_POST['writing_time']) : '',
                'listening_time' => isset($_POST['listening_time']) ? trim($_POST['listening_time']) : '',
                'speaking_time' => isset($_POST['speaking_time']) ? trim($_POST['speaking_time']) : '',
                'name_err' => '',
                'test_date_err' => '',
                'reading_score_err' => '',
                'writing_score_err' => '',
                'listening_score_err' => '',
                'speaking_score_err' => ''
            ];
            
            // Validate Name
            if(empty($data['name'])) {
                $data['name_err'] = 'Please enter test name';
            }
            
            // Validate Test Date
            if(empty($data['test_date'])) {
                $data['test_date_err'] = 'Please enter test date';
            } elseif(!isValidDate($data['test_date'])) {
                $data['test_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            }
            
            // Validate Scores if provided
            if(!empty($data['reading_score']) && !isValidIELTSScore($data['reading_score'])) {
                $data['reading_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            if(!empty($data['writing_score']) && !isValidIELTSScore($data['writing_score'])) {
                $data['writing_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            if(!empty($data['listening_score']) && !isValidIELTSScore($data['listening_score'])) {
                $data['listening_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            if(!empty($data['speaking_score']) && !isValidIELTSScore($data['speaking_score'])) {
                $data['speaking_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            // Make sure errors are empty
            if(empty($data['name_err']) && empty($data['test_date_err']) && 
               empty($data['reading_score_err']) && empty($data['writing_score_err']) && 
               empty($data['listening_score_err']) && empty($data['speaking_score_err'])) {
                // Validated
                
                // Add practice test
                $testId = $this->practiceTestModel->addTest(
                    $_SESSION['user_id'],
                    $data['name'],
                    $data['notes'],
                    $data['test_date']
                );
                
                if($testId) {
                    // Add scores if provided
                    if(!empty($data['reading_score'])) {
                        $this->testScoreModel->addScore(
                            $testId,
                            1, // Reading section ID
                            $data['reading_score'],
                            !empty($data['reading_time']) ? $data['reading_time'] : null
                        );
                    }
                    
                    if(!empty($data['writing_score'])) {
                        $this->testScoreModel->addScore(
                            $testId,
                            2, // Writing section ID
                            $data['writing_score'],
                            !empty($data['writing_time']) ? $data['writing_time'] : null
                        );
                    }
                    
                    if(!empty($data['listening_score'])) {
                        $this->testScoreModel->addScore(
                            $testId,
                            3, // Listening section ID
                            $data['listening_score'],
                            !empty($data['listening_time']) ? $data['listening_time'] : null
                        );
                    }
                    
                    if(!empty($data['speaking_score'])) {
                        $this->testScoreModel->addScore(
                            $testId,
                            4, // Speaking section ID
                            $data['speaking_score'],
                            !empty($data['speaking_time']) ? $data['speaking_time'] : null
                        );
                    }
                    
                    flash('test_success', 'Practice test added successfully');
                    redirect('practice');
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('practice/add', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Init data
            $data = [
                'name' => '',
                'test_date' => date('Y-m-d'),
                'notes' => '',
                'reading_score' => '',
                'writing_score' => '',
                'listening_score' => '',
                'speaking_score' => '',
                'reading_time' => '',
                'writing_time' => '',
                'listening_time' => '',
                'speaking_time' => '',
                'name_err' => '',
                'test_date_err' => '',
                'reading_score_err' => '',
                'writing_score_err' => '',
                'listening_score_err' => '',
                'speaking_score_err' => '',
                'sections' => $sections
            ];
            
            // Load view
            $this->view('practice/add', $data);
        }
    }
    
    /**
     * Edit practice test
     *
     * @param int $id Test ID
     */
    public function edit($id) {
        // Get test details
        $test = $this->practiceTestModel->getTestById($id);
        
        // Check if test exists and belongs to user
        if(!$test || $test->user_id != $_SESSION['user_id']) {
            flash('test_error', 'Test not found or access denied', 'alert alert-danger');
            redirect('practice');
        }
        
        // Get test scores
        $scores = $this->testScoreModel->getTestScores($test->id);
        $scoresData = [];
        
        // Organize scores by section ID
        foreach($scores as $score) {
            $scoresData[$score->section_id] = $score;
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
                'test_date' => trim($_POST['test_date']),
                'notes' => trim($_POST['notes']),
                'reading_score' => isset($_POST['reading_score']) ? trim($_POST['reading_score']) : '',
                'writing_score' => isset($_POST['writing_score']) ? trim($_POST['writing_score']) : '',
                'listening_score' => isset($_POST['listening_score']) ? trim($_POST['listening_score']) : '',
                'speaking_score' => isset($_POST['speaking_score']) ? trim($_POST['speaking_score']) : '',
                'reading_time' => isset($_POST['reading_time']) ? trim($_POST['reading_time']) : '',
                'writing_time' => isset($_POST['writing_time']) ? trim($_POST['writing_time']) : '',
                'listening_time' => isset($_POST['listening_time']) ? trim($_POST['listening_time']) : '',
                'speaking_time' => isset($_POST['speaking_time']) ? trim($_POST['speaking_time']) : '',
                'name_err' => '',
                'test_date_err' => '',
                'reading_score_err' => '',
                'writing_score_err' => '',
                'listening_score_err' => '',
                'speaking_score_err' => ''
            ];
            
            // Validate Name
            if(empty($data['name'])) {
                $data['name_err'] = 'Please enter test name';
            }
            
            // Validate Test Date
            if(empty($data['test_date'])) {
                $data['test_date_err'] = 'Please enter test date';
            } elseif(!isValidDate($data['test_date'])) {
                $data['test_date_err'] = 'Please enter a valid date (YYYY-MM-DD)';
            }
            
            // Validate Scores if provided
            if(!empty($data['reading_score']) && !isValidIELTSScore($data['reading_score'])) {
                $data['reading_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            if(!empty($data['writing_score']) && !isValidIELTSScore($data['writing_score'])) {
                $data['writing_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            if(!empty($data['listening_score']) && !isValidIELTSScore($data['listening_score'])) {
                $data['listening_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            if(!empty($data['speaking_score']) && !isValidIELTSScore($data['speaking_score'])) {
                $data['speaking_score_err'] = 'Please enter a valid IELTS score (0-9 in 0.5 increments)';
            }
            
            // Make sure errors are empty
            if(empty($data['name_err']) && empty($data['test_date_err']) && 
               empty($data['reading_score_err']) && empty($data['writing_score_err']) && 
               empty($data['listening_score_err']) && empty($data['speaking_score_err'])) {
                // Validated
                
                // Update practice test
                $updateData = [
                    'name' => $data['name'],
                    'notes' => $data['notes'],
                    'test_date' => $data['test_date']
                ];
                
                if($this->practiceTestModel->updateTest($id, $updateData)) {
                    // Update scores
                    
                    // Reading score
                    if(!empty($data['reading_score'])) {
                        $readingScore = isset($scoresData[1]) ? $scoresData[1]->id : null;
                        
                        if($readingScore) {
                            // Update existing score
                            $this->testScoreModel->updateScore($readingScore, [
                                'score' => $data['reading_score'],
                                'time_spent' => !empty($data['reading_time']) ? $data['reading_time'] : null
                            ]);
                        } else {
                            // Add new score
                            $this->testScoreModel->addScore(
                                $id,
                                1, // Reading section ID
                                $data['reading_score'],
                                !empty($data['reading_time']) ? $data['reading_time'] : null
                            );
                        }
                    }
                    
                    // Writing score
                    if(!empty($data['writing_score'])) {
                        $writingScore = isset($scoresData[2]) ? $scoresData[2]->id : null;
                        
                        if($writingScore) {
                            // Update existing score
                            $this->testScoreModel->updateScore($writingScore, [
                                'score' => $data['writing_score'],
                                'time_spent' => !empty($data['writing_time']) ? $data['writing_time'] : null
                            ]);
                        } else {
                            // Add new score
                            $this->testScoreModel->addScore(
                                $id,
                                2, // Writing section ID
                                $data['writing_score'],
                                !empty($data['writing_time']) ? $data['writing_time'] : null
                            );
                        }
                    }
                    
                    // Listening score
                    if(!empty($data['listening_score'])) {
                        $listeningScore = isset($scoresData[3]) ? $scoresData[3]->id : null;
                        
                        if($listeningScore) {
                            // Update existing score
                            $this->testScoreModel->updateScore($listeningScore, [
                                'score' => $data['listening_score'],
                                'time_spent' => !empty($data['listening_time']) ? $data['listening_time'] : null
                            ]);
                        } else {
                            // Add new score
                            $this->testScoreModel->addScore(
                                $id,
                                3, // Listening section ID
                                $data['listening_score'],
                                !empty($data['listening_time']) ? $data['listening_time'] : null
                            );
                        }
                    }
                    
                    // Speaking score
                    if(!empty($data['speaking_score'])) {
                        $speakingScore = isset($scoresData[4]) ? $scoresData[4]->id : null;
                        
                        if($speakingScore) {
                            // Update existing score
                            $this->testScoreModel->updateScore($speakingScore, [
                                'score' => $data['speaking_score'],
                                'time_spent' => !empty($data['speaking_time']) ? $data['speaking_time'] : null
                            ]);
                        } else {
                            // Add new score
                            $this->testScoreModel->addScore(
                                $id,
                                4, // Speaking section ID
                                $data['speaking_score'],
                                !empty($data['speaking_time']) ? $data['speaking_time'] : null
                            );
                        }
                    }
                    
                    flash('test_success', 'Practice test updated successfully');
                    redirect('practice/view/' . $id);
                } else {
                    die('Something went wrong');
                }
            } else {
                // Load view with errors
                $this->view('practice/edit', $data);
            }
        } else {
            // Get sections
            $sections = $this->sectionModel->getAllSections();
            
            // Prepare score data
            $readingScore = isset($scoresData[1]) ? $scoresData[1]->score : '';
            $writingScore = isset($scoresData[2]) ? $scoresData[2]->score : '';
            $listeningScore = isset($scoresData[3]) ? $scoresData[3]->score : '';
            $speakingScore = isset($scoresData[4]) ? $scoresData[4]->score : '';
            
            $readingTime = isset($scoresData[1]) ? $scoresData[1]->time_spent : '';
            $writingTime = isset($scoresData[2]) ? $scoresData[2]->time_spent : '';
            $listeningTime = isset($scoresData[3]) ? $scoresData[3]->time_spent : '';
            $speakingTime = isset($scoresData[4]) ? $scoresData[4]->time_spent : '';
            
            // Init data
            $data = [
                'id' => $id,
                'name' => $test->name,
                'test_date' => $test->test_date,
                'notes' => $test->notes,
                'reading_score' => $readingScore,
                'writing_score' => $writingScore,
                'listening_score' => $listeningScore,
                'speaking_score' => $speakingScore,
                'reading_time' => $readingTime,
                'writing_time' => $writingTime,
                'listening_time' => $listeningTime,
                'speaking_time' => $speakingTime,
                'name_err' => '',
                'test_date_err' => '',
                'reading_score_err' => '',
                'writing_score_err' => '',
                'listening_score_err' => '',
                'speaking_score_err' => '',
                'sections' => $sections
            ];
            
            // Load view
            $this->view('practice/edit', $data);
        }
    }
    
    /**
     * Delete practice test
     *
     * @param int $id Test ID
     */
    public function delete($id) {
        // Check for POST
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get test details
            $test = $this->practiceTestModel->getTestById($id);
            
            // Check if test exists and belongs to user
            if(!$test || $test->user_id != $_SESSION['user_id']) {
                flash('test_error', 'Test not found or access denied', 'alert alert-danger');
                redirect('practice');
            }
            
            // Delete test
            if($this->practiceTestModel->deleteTest($id)) {
                flash('test_success', 'Practice test deleted successfully');
                redirect('practice');
            } else {
                die('Something went wrong');
            }
        } else {
            redirect('practice');
        }
    }
    
    /**
     * Show score history for a specific section
     *
     * @param int $sectionId Section ID
     */
    public function sectionScores($sectionId) {
        // Validate section ID
        $section = $this->sectionModel->getSectionById($sectionId);
        
        if(!$section) {
            flash('section_error', 'Section not found', 'alert alert-danger');
            redirect('practice');
        }
        
        // Get score history
        $scores = $this->testScoreModel->getSectionScoreHistory($_SESSION['user_id'], $sectionId);
        
        // Prepare data for view
        $data = [
            'section' => $section,
            'scores' => $scores
        ];
        
        $this->view('practice/section_scores', $data);
    }
}