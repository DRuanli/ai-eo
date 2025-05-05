<?php
/**
 * Study Goal Model
 * Handles operations related to study goals
 */
class StudyGoal extends Model {
    // Table name
    private $table = 'study_goals';
    
    /**
     * Get all goals for a user
     *
     * @param int $userId User ID
     * @param bool $includeSection Include section details (optional)
     * @return array User goals
     */
    public function getUserGoals($userId, $includeSection = true) {
        if($includeSection) {
            $this->db->query("SELECT sg.*, COALESCE(is.name, 'Overall') as section_name
                              FROM {$this->table} sg
                              LEFT JOIN ielts_sections is ON sg.section_id = is.id
                              WHERE sg.user_id = :user_id
                              ORDER BY sg.target_date ASC");
        } else {
            $this->db->query("SELECT * FROM {$this->table} 
                              WHERE user_id = :user_id
                              ORDER BY target_date ASC");
        }
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get goal by ID
     *
     * @param int $id Goal ID
     * @param bool $includeSection Include section details (optional)
     * @return object Goal data
     */
    public function getGoalById($id, $includeSection = true) {
        if($includeSection) {
            $this->db->query("SELECT sg.*, COALESCE(is.name, 'Overall') as section_name
                              FROM {$this->table} sg
                              LEFT JOIN ielts_sections is ON sg.section_id = is.id
                              WHERE sg.id = :id");
        } else {
            $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        }
        
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Get overall goal for a user
     *
     * @param int $userId User ID
     * @return object|bool Goal data or false if not found
     */
    public function getOverallGoal($userId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND section_id IS NULL
                          ORDER BY target_date ASC
                          LIMIT 1");
        
        $this->db->bind(':user_id', $userId);
        
        $goal = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $goal;
        } else {
            return false;
        }
    }
    
    /**
     * Get section goal for a user
     *
     * @param int $userId User ID
     * @param int $sectionId Section ID
     * @return object|bool Goal data or false if not found
     */
    public function getSectionGoal($userId, $sectionId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND section_id = :section_id
                          ORDER BY target_date ASC
                          LIMIT 1");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':section_id', $sectionId);
        
        $goal = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $goal;
        } else {
            return false;
        }
    }
    
    /**
     * Add a new goal
     *
     * @param int $userId User ID
     * @param float $targetScore Target score
     * @param string $targetDate Target date (YYYY-MM-DD)
     * @param int|null $sectionId Section ID (null for overall goal)
     * @return int|bool Goal ID if successful, false otherwise
     */
    public function addGoal($userId, $targetScore, $targetDate, $sectionId = null) {
        $data = [
            'user_id' => $userId,
            'target_score' => $targetScore,
            'target_date' => $targetDate,
            'achieved' => false
        ];
        
        if($sectionId !== null) {
            $data['section_id'] = $sectionId;
        }
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update a goal
     *
     * @param int $id Goal ID
     * @param array $data Update data
     * @return bool True on success
     */
    public function updateGoal($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Mark goal as achieved
     *
     * @param int $id Goal ID
     * @param bool $achieved Achievement status
     * @return bool True on success
     */
    public function markAsAchieved($id, $achieved = true) {
        $data = ['achieved' => $achieved];
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete a goal
     *
     * @param int $id Goal ID
     * @return bool True on success
     */
    public function deleteGoal($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Get upcoming goals
     *
     * @param int $userId User ID
     * @param int $days Number of days to look ahead
     * @return array Upcoming goals
     */
    public function getUpcomingGoals($userId, $days = 30) {
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $this->db->query("SELECT sg.*, COALESCE(is.name, 'Overall') as section_name
                          FROM {$this->table} sg
                          LEFT JOIN ielts_sections is ON sg.section_id = is.id
                          WHERE sg.user_id = :user_id
                          AND sg.target_date BETWEEN CURDATE() AND :end_date
                          AND sg.achieved = 0
                          ORDER BY sg.target_date ASC");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':end_date', $endDate);
        
        return $this->db->resultSet();
    }
    
    /**
     * Check goal achievement
     *
     * @param int $userId User ID
     * @return array Goals with achievement status
     */
    public function checkGoalAchievement($userId) {
        // For overall goals
        $this->db->query("SELECT sg.id, sg.target_score, 
                          (SELECT AVG(score) FROM test_scores 
                           WHERE practice_test_id IN 
                             (SELECT id FROM practice_tests WHERE user_id = :user_id)
                          ) as current_score
                          FROM {$this->table} sg
                          WHERE sg.user_id = :user_id 
                          AND sg.section_id IS NULL
                          AND sg.achieved = 0");
        
        $this->db->bind(':user_id', $userId);
        $overallGoals = $this->db->resultSet();
        
        // For section goals
        $this->db->query("SELECT sg.id, sg.section_id, sg.target_score,
                          (SELECT AVG(score) FROM test_scores 
                           WHERE section_id = sg.section_id
                           AND practice_test_id IN 
                             (SELECT id FROM practice_tests WHERE user_id = :user_id)
                          ) as current_score
                          FROM {$this->table} sg
                          WHERE sg.user_id = :user_id 
                          AND sg.section_id IS NOT NULL
                          AND sg.achieved = 0");
        
        $this->db->bind(':user_id', $userId);
        $sectionGoals = $this->db->resultSet();
        
        // Combine results
        $allGoals = array_merge($overallGoals, $sectionGoals);
        
        $result = [];
        foreach($allGoals as $goal) {
            if($goal->current_score !== null && $goal->current_score >= $goal->target_score) {
                $this->markAsAchieved($goal->id, true);
                $goal->achieved = true;
            } else {
                $goal->achieved = false;
            }
            
            $result[] = $goal;
        }
        
        return $result;
    }
    
    /**
     * Get progress towards goals
     *
     * @param int $userId User ID
     * @return array Goals with progress info
     */
    public function getGoalsProgress($userId) {
        $this->db->query("SELECT sg.id, sg.section_id, sg.target_score, sg.target_date, sg.achieved,
                          COALESCE(is.name, 'Overall') as section_name,
                          CASE 
                            WHEN sg.section_id IS NULL THEN 
                              (SELECT AVG(score) FROM test_scores 
                               WHERE practice_test_id IN 
                                 (SELECT id FROM practice_tests WHERE user_id = :user_id)
                              )
                            ELSE 
                              (SELECT AVG(score) FROM test_scores 
                               WHERE section_id = sg.section_id
                               AND practice_test_id IN 
                                 (SELECT id FROM practice_tests WHERE user_id = :user_id)
                              )
                          END as current_score,
                          DATEDIFF(sg.target_date, CURDATE()) as days_remaining
                          FROM {$this->table} sg
                          LEFT JOIN ielts_sections is ON sg.section_id = is.id
                          WHERE sg.user_id = :user_id
                          ORDER BY sg.target_date ASC");
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
}