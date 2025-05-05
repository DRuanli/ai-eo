<?php
/**
 * Study Session Model
 * Handles operations related to study sessions
 */
class StudySession extends Model {
    // Table name
    private $table = 'study_sessions';
    
    /**
     * Get all study sessions for a user
     *
     * @param int $userId User ID
     * @param int $limit Limit results (optional)
     * @param int $offset Offset for pagination (optional)
     * @return array Study sessions
     */
    public function getUserSessions($userId, $limit = null, $offset = null) {
        $sql = "SELECT ss.*, is.name as section_name 
                FROM {$this->table} ss
                JOIN ielts_sections is ON ss.section_id = is.id
                WHERE ss.user_id = :user_id 
                ORDER BY ss.start_time DESC";
        
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
     * Get study session by ID
     *
     * @param int $id Session ID
     * @return object Session data
     */
    public function getSessionById($id) {
        $this->db->query("SELECT ss.*, is.name as section_name 
                          FROM {$this->table} ss
                          JOIN ielts_sections is ON ss.section_id = is.id
                          WHERE ss.id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Start a new study session
     *
     * @param int $userId User ID
     * @param int $sectionId Section ID
     * @return int|bool Session ID if successful, false otherwise
     */
    public function startSession($userId, $sectionId) {
        $data = [
            'user_id' => $userId,
            'section_id' => $sectionId,
            'start_time' => date('Y-m-d H:i:s')
        ];
        
        return $this->add($this->table, $data);
    }
    
    /**
     * End a study session
     *
     * @param int $id Session ID
     * @return bool True on success
     */
    public function endSession($id) {
        $endTime = date('Y-m-d H:i:s');
        
        // Get session start time
        $session = $this->getSessionById($id);
        if(!$session) {
            return false;
        }
        
        // Calculate duration in minutes
        $startTime = new DateTime($session->start_time);
        $endTimeObj = new DateTime($endTime);
        $interval = $startTime->diff($endTimeObj);
        $duration = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        
        $data = [
            'end_time' => $endTime,
            'duration' => $duration
        ];
        
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete a study session
     *
     * @param int $id Session ID
     * @return bool True on success
     */
    public function deleteSession($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Get active session for a user if exists
     *
     * @param int $userId User ID
     * @return object|bool Session data or false if no active session
     */
    public function getActiveSession($userId) {
        $this->db->query("SELECT ss.*, is.name as section_name 
                          FROM {$this->table} ss
                          JOIN ielts_sections is ON ss.section_id = is.id
                          WHERE ss.user_id = :user_id 
                          AND ss.end_time IS NULL");
        
        $this->db->bind(':user_id', $userId);
        
        $session = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $session;
        } else {
            return false;
        }
    }
    
    /**
     * Get total study time per section
     *
     * @param int $userId User ID
     * @param string $startDate Start date (YYYY-MM-DD) (optional)
     * @param string $endDate End date (YYYY-MM-DD) (optional)
     * @return array Total time per section
     */
    public function getTotalTimePerSection($userId, $startDate = null, $endDate = null) {
        $sql = "SELECT is.id, is.name, SUM(ss.duration) as total_minutes
                FROM ielts_sections is
                LEFT JOIN {$this->table} ss ON is.id = ss.section_id
                  AND ss.user_id = :user_id
                  AND ss.duration IS NOT NULL";
        
        if($startDate !== null) {
            $sql .= " AND ss.start_time >= :start_date";
        }
        
        if($endDate !== null) {
            $sql .= " AND ss.start_time <= :end_date";
        }
        
        $sql .= " GROUP BY is.id, is.name
                  ORDER BY total_minutes DESC";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        
        if($startDate !== null) {
            $this->db->bind(':start_date', $startDate . ' 00:00:00');
        }
        
        if($endDate !== null) {
            $this->db->bind(':end_date', $endDate . ' 23:59:59');
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get study sessions by date range
     *
     * @param int $userId User ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Study sessions in the date range
     */
    public function getSessionsByDateRange($userId, $startDate, $endDate) {
        $this->db->query("SELECT ss.*, is.name as section_name 
                          FROM {$this->table} ss
                          JOIN ielts_sections is ON ss.section_id = is.id
                          WHERE ss.user_id = :user_id 
                          AND ss.start_time BETWEEN :start_date AND :end_date
                          ORDER BY ss.start_time ASC");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':start_date', $startDate . ' 00:00:00');
        $this->db->bind(':end_date', $endDate . ' 23:59:59');
        
        return $this->db->resultSet();
    }
    
    /**
     * Get total study time for a user
     *
     * @param int $userId User ID
     * @param string $period 'day', 'week', 'month', 'all' (optional, defaults to 'all')
     * @return int Total minutes studied
     */
    public function getTotalStudyTime($userId, $period = 'all') {
        $sql = "SELECT COALESCE(SUM(duration), 0) as total_minutes 
                FROM {$this->table} 
                WHERE user_id = :user_id";
        
        switch($period) {
            case 'day':
                $sql .= " AND DATE(start_time) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND YEARWEEK(start_time, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $sql .= " AND YEAR(start_time) = YEAR(CURDATE()) AND MONTH(start_time) = MONTH(CURDATE())";
                break;
        }
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        
        $result = $this->db->single();
        return $result->total_minutes ?? 0;
    }
}