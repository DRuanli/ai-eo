<?php
/**
 * Practice Test Model
 * Handles all operations related to practice tests
 */
class PracticeTest extends Model {
    // Table name
    private $table = 'practice_tests';
    
    /**
     * Get all practice tests for a user
     *
     * @param int $userId User ID
     * @param int $limit Limit results (optional)
     * @param int $offset Offset for pagination (optional)
     * @return array Practice tests
     */
    public function getUserTests($userId, $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY test_date DESC";
        
        if($limit !== null) {
            $sql .= " LIMIT {$limit}";
            
            if($offset !== null) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get practice test by ID
     *
     * @param int $id Test ID
     * @return object Test data
     */
    public function getTestById($id) {
        return $this->findById($this->table, $id);
    }
    
    /**
     * Add new practice test
     *
     * @param int $userId User ID
     * @param string $name Test name
     * @param string $notes Test notes (optional)
     * @param string $testDate Test date (optional, defaults to current date)
     * @return int|bool Test ID if successful, false otherwise
     */
    public function addTest($userId, $name, $notes = '', $testDate = null) {
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'notes' => $notes
        ];
        
        if($testDate !== null) {
            $data['test_date'] = $testDate;
        }
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update practice test
     *
     * @param int $id Test ID
     * @param array $data Test data
     * @return bool True on success
     */
    public function updateTest($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete practice test
     *
     * @param int $id Test ID
     * @return bool True on success
     */
    public function deleteTest($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Count tests for a user
     *
     * @param int $userId User ID
     * @return int Number of tests
     */
    public function countUserTests($userId) {
        $this->db->query("SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        
        $result = $this->db->single();
        return $result->count;
    }
    
    /**
     * Get latest test for a user
     *
     * @param int $userId User ID
     * @return object|bool Test data or false if none exists
     */
    public function getLatestTest($userId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          ORDER BY test_date DESC 
                          LIMIT 1");
        $this->db->bind(':user_id', $userId);
        
        $test = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $test;
        } else {
            return false;
        }
    }
    
    /**
     * Get tests between dates
     *
     * @param int $userId User ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Tests in the date range
     */
    public function getTestsBetweenDates($userId, $startDate, $endDate) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND test_date BETWEEN :start_date AND :end_date 
                          ORDER BY test_date ASC");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        
        return $this->db->resultSet();
    }
}