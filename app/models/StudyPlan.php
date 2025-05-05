<?php
/**
 * Study Plan Model
 * Handles operations related to study plans
 */
class StudyPlan extends Model {
    // Table name
    private $table = 'study_plans';
    
    /**
     * Get all study plans for a user
     *
     * @param int $userId User ID
     * @param string $status Status filter (optional)
     * @return array Study plans
     */
    public function getUserPlans($userId, $status = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id";
        
        if($status !== null) {
            $sql .= " AND status = :status";
        }
        
        $sql .= " ORDER BY start_date ASC";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        
        if($status !== null) {
            $this->db->bind(':status', $status);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get study plan by ID
     *
     * @param int $id Plan ID
     * @return object Plan data
     */
    public function getPlanById($id) {
        return $this->findById($this->table, $id);
    }
    
    /**
     * Add a new study plan
     *
     * @param int $userId User ID
     * @param string $name Plan name
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param string $status Status (active, completed, cancelled)
     * @return int|bool Plan ID if successful, false otherwise
     */
    public function addPlan($userId, $name, $startDate, $endDate, $status = 'active') {
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status
        ];
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update a study plan
     *
     * @param int $id Plan ID
     * @param array $data Update data
     * @return bool True on success
     */
    public function updatePlan($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Update plan status
     *
     * @param int $id Plan ID
     * @param string $status New status
     * @return bool True on success
     */
    public function updateStatus($id, $status) {
        $data = ['status' => $status];
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete a study plan
     *
     * @param int $id Plan ID
     * @return bool True on success
     */
    public function deletePlan($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Get active study plan for user
     *
     * @param int $userId User ID
     * @return object|bool Active plan or false if none
     */
    public function getActivePlan($userId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND status = 'active'
                          AND CURDATE() BETWEEN start_date AND end_date
                          ORDER BY start_date ASC
                          LIMIT 1");
        
        $this->db->bind(':user_id', $userId);
        
        $plan = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $plan;
        } else {
            return false;
        }
    }
    
    /**
     * Get future study plans
     *
     * @param int $userId User ID
     * @return array Future plans
     */
    public function getFuturePlans($userId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND start_date > CURDATE()
                          ORDER BY start_date ASC");
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get completed study plans
     *
     * @param int $userId User ID
     * @return array Completed plans
     */
    public function getCompletedPlans($userId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND (status = 'completed' OR end_date < CURDATE())
                          ORDER BY end_date DESC");
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get plan completion percentage
     *
     * @param int $planId Plan ID
     * @return float Completion percentage
     */
    public function getPlanCompletionPercentage($planId) {
        $this->db->query("SELECT 
                          (SELECT COUNT(*) FROM study_plan_items WHERE study_plan_id = :plan_id) as total_items,
                          (SELECT COUNT(*) FROM study_plan_items WHERE study_plan_id = :plan_id AND completed = 1) as completed_items");
        
        $this->db->bind(':plan_id', $planId);
        $result = $this->db->single();
        
        if($result->total_items > 0) {
            return ($result->completed_items / $result->total_items) * 100;
        } else {
            return 0;
        }
    }
    
    /**
     * Check for plan overlaps
     *
     * @param int $userId User ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param int $excludePlanId Plan ID to exclude (optional)
     * @return bool True if overlap exists
     */
    public function checkOverlap($userId, $startDate, $endDate, $excludePlanId = null) {
        $sql = "SELECT COUNT(*) as overlap_count 
                FROM {$this->table} 
                WHERE user_id = :user_id 
                AND status = 'active'
                AND (
                    (start_date BETWEEN :start_date AND :end_date) OR
                    (end_date BETWEEN :start_date AND :end_date) OR
                    (:start_date BETWEEN start_date AND end_date) OR
                    (:end_date BETWEEN start_date AND end_date)
                )";
        
        if($excludePlanId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        
        if($excludePlanId !== null) {
            $this->db->bind(':exclude_id', $excludePlanId);
        }
        
        $result = $this->db->single();
        return ($result->overlap_count > 0);
    }
    
    /**
     * Generate a study plan based on a test date and weak areas
     *
     * @param int $userId User ID
     * @param string $testDate Test date (YYYY-MM-DD)
     * @param string $planName Plan name (optional)
     * @return int|bool Plan ID if successful, false otherwise
     */
    public function generatePlan($userId, $testDate, $planName = null) {
        // Calculate start date (today)
        $startDate = date('Y-m-d');
        
        // Set plan name if not provided
        if($planName === null) {
            $planName = 'Study Plan for Test on ' . date('j M Y', strtotime($testDate));
        }
        
        // Create the plan
        $planId = $this->addPlan($userId, $planName, $startDate, $testDate);
        
        if(!$planId) {
            return false;
        }
        
        // Initialize StudyPlanItem model to add items
        $planItemModel = new StudyPlanItem();
        
        // Get weak areas to prioritize
        $weakAreaModel = new WeakArea();
        $weakAreas = $weakAreaModel->getUserWeakAreas($userId, true);
        
        // Get test score model to identify current scores
        $scoreModel = new TestScore();
        $currentScores = $scoreModel->getLatestSectionScores($userId);
        
        // Get section model
        $sectionModel = new IeltsSection();
        $sections = $sectionModel->getAllSections();
        
        // Create a dictionary of sections
        $sectionDict = [];
        foreach($sections as $section) {
            $sectionDict[$section->id] = $section;
        }
        
        // Calculate number of days until test
        $daysUntilTest = (strtotime($testDate) - strtotime('today')) / (60 * 60 * 24);
        
        // Generate plan items
        if($daysUntilTest > 0) {
            // Create weekly plans for each section
            $weeksLeft = ceil($daysUntilTest / 7);
            
            // Distribute study days based on weak areas
            $studyDayDistribution = $this->calculateStudyDistribution($weakAreas, $currentScores, $sections, $daysUntilTest);
            
            // Generate plan items for each section
            foreach($studyDayDistribution as $sectionId => $daysCount) {
                $section = $sectionDict[$sectionId];
                $sectionWeakAreas = array_filter($weakAreas, function($area) use ($sectionId) {
                    return $area->section_id == $sectionId;
                });
                
                // Generate items for this section
                $this->generateSectionPlanItems($planId, $sectionId, $sectionWeakAreas, $daysCount, $startDate, $testDate, $planItemModel);
            }
            
            // Add final review items in the last week
            $finalReviewStart = date('Y-m-d', strtotime($testDate . ' -7 days'));
            foreach($sections as $section) {
                $reviewDate = date('Y-m-d', strtotime($testDate . ' -' . ($section->id) . ' days'));
                
                $planItemModel->addPlanItem(
                    $planId,
                    $section->id,
                    'Final Review: ' . $section->name,
                    'Complete a full practice test and review all weak areas for ' . $section->name,
                    $reviewDate,
                    120, // 2 hours
                    false,
                    null
                );
            }
            
            // Add a mock test 3 days before the actual test
            $mockTestDate = date('Y-m-d', strtotime($testDate . ' -3 days'));
            $planItemModel->addPlanItem(
                $planId,
                null,
                'Full Mock Test',
                'Complete a full IELTS mock test under timed conditions',
                $mockTestDate,
                180, // 3 hours
                false,
                null
            );
            
            return $planId;
        }
        
        return false;
    }
    
    /**
     * Calculate study day distribution among sections
     *
     * @param array $weakAreas User's weak areas
     * @param array $currentScores User's current scores
     * @param array $sections All IELTS sections
     * @param int $totalDays Total days available
     * @return array Days allocated to each section
     */
    private function calculateStudyDistribution($weakAreas, $currentScores, $sections, $totalDays) {
        // Map sections to scores
        $sectionScores = [];
        foreach($currentScores as $score) {
            if(isset($score->section_id)) {
                $sectionScores[$score->section_id] = $score->score ?? 0;
            }
        }
        
        // Count weak areas per section
        $weakAreaCounts = [];
        foreach($weakAreas as $area) {
            if(!isset($weakAreaCounts[$area->section_id])) {
                $weakAreaCounts[$area->section_id] = 0;
            }
            $weakAreaCounts[$area->section_id] += $area->priority;
        }
        
        // Calculate weights for each section based on scores and weak areas
        $weights = [];
        $totalWeight = 0;
        
        foreach($sections as $section) {
            $score = $sectionScores[$section->id] ?? 5; // Default to 5 if no score
            $weakCount = $weakAreaCounts[$section->id] ?? 1; // Default to 1 if no weak areas
            
            // Inverse relationship with score (lower score = higher weight)
            $scoreWeight = max(1, 10 - $score);
            
            // Direct relationship with weak area count
            $weakAreaWeight = $weakCount;
            
            // Combined weight
            $weight = ($scoreWeight * 0.7) + ($weakAreaWeight * 0.3);
            $weights[$section->id] = $weight;
            $totalWeight += $weight;
        }
        
        // Distribute days based on weights
        $distribution = [];
        $allocatedDays = 0;
        
        foreach($weights as $sectionId => $weight) {
            // Calculate days proportionally to weight
            $sectionDays = max(1, round(($weight / $totalWeight) * ($totalDays - 7))); // Reserve 7 days for final review
            $distribution[$sectionId] = $sectionDays;
            $allocatedDays += $sectionDays;
        }
        
        // Adjust if we allocated too many or too few days
        $diff = $allocatedDays - ($totalDays - 7);
        
        if($diff > 0) {
            // Remove extra days from the section with the most days
            arsort($distribution);
            foreach($distribution as $sectionId => $days) {
                if($diff <= 0) break;
                $reduce = min($diff, max(1, floor($days * 0.2))); // Reduce by up to 20%, keeping at least 1 day
                $distribution[$sectionId] -= $reduce;
                $diff -= $reduce;
            }
        } elseif($diff < 0) {
            // Add additional days to the section with the least days
            asort($distribution);
            foreach($distribution as $sectionId => $days) {
                if($diff >= 0) break;
                $add = min(abs($diff), ceil($days * 0.2)); // Add up to 20% more days
                $distribution[$sectionId] += $add;
                $diff += $add;
            }
        }
        
        return $distribution;
    }
    
    /**
     * Generate plan items for a specific section
     *
     * @param int $planId Plan ID
     * @param int $sectionId Section ID
     * @param array $weakAreas Weak areas for this section
     * @param int $dayCount Number of days allocated
     * @param string $startDate Start date
     * @param string $testDate Test date
     * @param StudyPlanItem $planItemModel StudyPlanItem model instance
     * @return bool Success status
     */
    private function generateSectionPlanItems($planId, $sectionId, $weakAreas, $dayCount, $startDate, $testDate, $planItemModel) {
        // Get section name
        $sectionModel = new IeltsSection();
        $section = $sectionModel->getSectionById($sectionId);
        
        if(!$section) {
            return false;
        }
        
        // Study activities for each section
        $activities = [
            // Reading
            1 => [
                'Reading Practice: Skimming and Scanning',
                'Reading Practice: Vocabulary in Context',
                'Reading Practice: True/False/Not Given Questions',
                'Reading Practice: Matching Headings',
                'Reading Practice: Multiple Choice Questions'
            ],
            // Writing
            2 => [
                'Writing Practice: Task 1 (Graph Description)',
                'Writing Practice: Task 1 (Process Description)',
                'Writing Practice: Task 2 (Essay Structure)',
                'Writing Practice: Task 2 (Argument Development)',
                'Writing Practice: Grammar and Vocabulary Review'
            ],
            // Listening
            3 => [
                'Listening Practice: Section 1 (Form Completion)',
                'Listening Practice: Section 2 (Note Taking)',
                'Listening Practice: Section 3 (Multiple Choice)',
                'Listening Practice: Section 4 (Summary Completion)',
                'Listening Practice: Understanding Speaker Opinions'
            ],
            // Speaking
            4 => [
                'Speaking Practice: Part 1 (Introduction and Interview)',
                'Speaking Practice: Part 2 (Individual Long Turn)',
                'Speaking Practice: Part 3 (Two-way Discussion)',
                'Speaking Practice: Pronunciation and Fluency',
                'Speaking Practice: Vocabulary for Common Topics'
            ]
        ];
        
        $descriptions = [
            // Reading
            1 => [
                'Practice skimming for main ideas and scanning for specific information in IELTS-style passages.',
                'Focus on understanding vocabulary in context and identifying synonyms in reading passages.',
                'Practice identifying whether statements are true, false, or not given based on the text.',
                'Work on matching headings to paragraphs and understanding paragraph main ideas.',
                'Practice answering multiple choice questions by eliminating incorrect options.'
            ],
            // Writing
            2 => [
                'Practice describing graphs, charts, and tables using appropriate structure and vocabulary.',
                'Practice describing processes and diagrams using appropriate sequencing language.',
                'Focus on essay structure, including introduction, body paragraphs, and conclusion.',
                'Practice developing arguments, providing examples, and expressing opinions clearly.',
                'Review common grammar mistakes and expand vocabulary for formal writing.'
            ],
            // Listening
            3 => [
                'Practice form completion tasks focusing on spelling and number recognition.',
                'Practice taking notes and identifying key information from monologues.',
                'Practice multiple choice questions and identifying detailed information.',
                'Practice summary completion tasks from academic lectures.',
                'Focus on identifying opinions, attitudes, and purpose of speakers.'
            ],
            // Speaking
            4 => [
                'Practice answering common Part 1 questions about yourself and familiar topics.',
                'Practice 2-minute talks on given topics, focusing on organization and timing.',
                'Practice discussing abstract concepts and providing detailed responses.',
                'Work on pronunciation, intonation, and speaking fluently without hesitation.',
                'Build vocabulary for common IELTS speaking topics and practice using them.'
            ]
        ];
        
        // Study durations (in minutes)
        $durations = [60, 90, 120];
        
        // Distribute days evenly across the period until test date
        $periodDays = max(1, (strtotime($testDate) - strtotime($startDate)) / (60 * 60 * 24) - 7); // Excluding last week
        $dayInterval = max(1, floor($periodDays / $dayCount));
        
        // Generate plan items
        $weakAreaFocus = array_map(function($area) {
            return $area->sub_skill;
        }, $weakAreas);
        
        // If no specific weak areas, use general activities
        if(empty($weakAreaFocus)) {
            $weakAreaFocus = $activities[$sectionId] ?? ["Practice {$section->name}"];
        }
        
        // Cycle through weak areas and activities
        for($i = 0; $i < $dayCount; $i++) {
            // Calculate study date
            $studyDate = date('Y-m-d', strtotime($startDate . " +{$i} weeks +" . ($i % 7) . " days"));
            
            // Skip if date is beyond test date or in the last week
            if(strtotime($studyDate) >= strtotime($testDate) || 
               strtotime($studyDate) >= strtotime($testDate . ' -7 days')) {
                continue;
            }
            
            // Select activity and duration
            $activityIndex = $i % count($activities[$sectionId]);
            $activity = $activities[$sectionId][$activityIndex];
            $description = $descriptions[$sectionId][$activityIndex];
            
            // If we have weak areas, focus on them
            if(count($weakAreaFocus) > 0) {
                $weakAreaIndex = $i % count($weakAreaFocus);
                $weakAreaDescription = "Focus area: " . $weakAreaFocus[$weakAreaIndex];
                $description .= "\n\n" . $weakAreaDescription;
            }
            
            // Randomize duration slightly
            $duration = $durations[$i % count($durations)];
            
            // Add plan item
            $planItemModel->addPlanItem(
                $planId,
                $sectionId,
                $activity,
                $description,
                $studyDate,
                $duration,
                false,
                null
            );
        }
        
        return true;
    }
}