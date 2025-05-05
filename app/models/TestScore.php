<?php
/**
 * Test Score Model
 * Handles operations related to test scores
 */
class TestScore extends Model {
    // Table name
    private $table = 'test_scores';
    
    /**
     * Get all scores for a practice test
     *
     * @param int $testId Practice test ID
     * @return array Scores for the test
     */
    public function getTestScores($testId) {
        $this->db->query("SELECT ts.*, is.name as section_name 
                          FROM {$this->table} ts
                          JOIN ielts_sections is ON ts.section_id = is.id
                          WHERE ts.practice_test_id = :test_id
                          ORDER BY is.name ASC");
        
        $this->db->bind(':test_id', $testId);
        return $this->db->resultSet();
    }
    
    /**
     * Get score by ID
     *
     * @param int $id Score ID
     * @return object Score data
     */
    public function getScoreById($id) {
        return $this->findById($this->table, $id);
    }
    
    /**
     * Get score for a specific section in a test
     *
     * @param int $testId Practice test ID
     * @param int $sectionId Section ID
     * @return object|bool Score data or false if not found
     */
    public function getSectionScore($testId, $sectionId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE practice_test_id = :test_id 
                          AND section_id = :section_id");
        
        $this->db->bind(':test_id', $testId);
        $this->db->bind(':section_id', $sectionId);
        
        $score = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $score;
        } else {
            return false;
        }
    }
    
    /**
     * Add a score for a section in a test
     *
     * @param int $testId Practice test ID
     * @param int $sectionId Section ID
     * @param float $score Score value
     * @param int $timeSpent Time spent in minutes (optional)
     * @param string $details Additional details in JSON format (optional)
     * @return int|bool Score ID if successful, false otherwise
     */
    public function addScore($testId, $sectionId, $score, $timeSpent = null, $details = null) {
        $data = [
            'practice_test_id' => $testId,
            'section_id' => $sectionId,
            'score' => $score,
            'time_spent' => $timeSpent,
            'details' => $details
        ];
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update a score
     *
     * @param int $id Score ID
     * @param array $data Update data
     * @return bool True on success
     */
    public function updateScore($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete a score
     *
     * @param int $id Score ID
     * @return bool True on success
     */
    public function deleteScore($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Calculate overall score for a test
     *
     * @param int $testId Practice test ID
     * @return float|bool Overall score or false if no scores exist
     */
    public function calculateOverallScore($testId) {
        $this->db->query("SELECT AVG(score) as average FROM {$this->table} 
                          WHERE practice_test_id = :test_id");
        
        $this->db->bind(':test_id', $testId);
        $result = $this->db->single();
        
        if($result && isset($result->average)) {
            // Round to nearest 0.5 (IELTS convention)
            return round($result->average * 2) / 2;
        } else {
            return false;
        }
    }
    
    /**
     * Get history of scores for a specific section
     *
     * @param int $userId User ID
     * @param int $sectionId Section ID
     * @param int $limit Limit results (optional)
     * @return array Score history
     */
    public function getSectionScoreHistory($userId, $sectionId, $limit = null) {
        $sql = "SELECT ts.*, pt.test_date 
                FROM {$this->table} ts
                JOIN practice_tests pt ON ts.practice_test_id = pt.id
                WHERE pt.user_id = :user_id 
                AND ts.section_id = :section_id
                ORDER BY pt.test_date DESC";
        
        if($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':section_id', $sectionId);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get latest scores for all sections
     *
     * @param int $userId User ID
     * @return array Latest scores for each section
     */
    public function getLatestSectionScores($userId) {
        $this->db->query("SELECT s.id as section_id, s.name as section_name, 
                          ts.score, ts.time_spent, pt.test_date
                          FROM ielts_sections s
                          LEFT JOIN (
                              SELECT ts.*, pt.user_id, pt.test_date,
                              ROW_NUMBER() OVER (PARTITION BY ts.section_id ORDER BY pt.test_date DESC) as rn
                              FROM {$this->table} ts
                              JOIN practice_tests pt ON ts.practice_test_id = pt.id
                              WHERE pt.user_id = :user_id
                          ) ts ON s.id = ts.section_id AND ts.rn = 1
                          LEFT JOIN practice_tests pt ON ts.practice_test_id = pt.id
                          ORDER BY s.name ASC");
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Identify weak sections based on average scores
     *
     * @param int $userId User ID
     * @param int $limit Number of weak sections to return (optional)
     * @return array Weak sections with average scores
     */
    public function identifyWeakSections($userId, $limit = null) {
        $sql = "SELECT s.id, s.name, AVG(ts.score) as avg_score
                FROM ielts_sections s
                JOIN {$this->table} ts ON s.id = ts.section_id
                JOIN practice_tests pt ON ts.practice_test_id = pt.id
                WHERE pt.user_id = :user_id
                GROUP BY s.id, s.name
                ORDER BY avg_score ASC";
                
        if($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->resultSet();
    }
}