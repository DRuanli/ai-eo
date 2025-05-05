<?php
/**
 * Study Plan Item Model
 * Handles operations related to study plan items
 */
class StudyPlanItem extends Model {
    // Table name
    private $table = 'study_plan_items';
    
    /**
     * Get all items for a study plan
     *
     * @param int $planId Plan ID
     * @param bool $includeDetails Include additional details (optional)
     * @return array Plan items
     */
    public function getPlanItems($planId, $includeDetails = true) {
        if($includeDetails) {
            $this->db->query("SELECT spi.*, is.name as section_name, sr.title as resource_title, sr.file_path
                              FROM {$this->table} spi
                              LEFT JOIN ielts_sections is ON spi.section_id = is.id
                              LEFT JOIN study_resources sr ON spi.resource_id = sr.id
                              WHERE spi.study_plan_id = :plan_id
                              ORDER BY spi.scheduled_date ASC, spi.duration DESC");
        } else {
            $this->db->query("SELECT * FROM {$this->table} 
                              WHERE study_plan_id = :plan_id
                              ORDER BY scheduled_date ASC, duration DESC");
        }
        
        $this->db->bind(':plan_id', $planId);
        return $this->db->resultSet();
    }
    
    /**
     * Get item by ID
     *
     * @param int $id Item ID
     * @param bool $includeDetails Include additional details (optional)
     * @return object Item data
     */
    public function getItemById($id, $includeDetails = true) {
        if($includeDetails) {
            $this->db->query("SELECT spi.*, is.name as section_name, sr.title as resource_title, sr.file_path
                              FROM {$this->table} spi
                              LEFT JOIN ielts_sections is ON spi.section_id = is.id
                              LEFT JOIN study_resources sr ON spi.resource_id = sr.id
                              WHERE spi.id = :id");
        } else {
            $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        }
        
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Add a new plan item
     *
     * @param int $planId Plan ID
     * @param int|null $sectionId Section ID (null for general items)
     * @param string $title Item title
     * @param string $description Item description
     * @param string $scheduledDate Scheduled date (YYYY-MM-DD)
     * @param int $duration Duration in minutes
     * @param bool $completed Completed status
     * @param int|null $resourceId Resource ID (optional)
     * @return int|bool Item ID if successful, false otherwise
     */
    public function addPlanItem($planId, $sectionId, $title, $description, $scheduledDate, $duration, $completed = false, $resourceId = null) {
        $data = [
            'study_plan_id' => $planId,
            'title' => $title,
            'description' => $description,
            'scheduled_date' => $scheduledDate,
            'duration' => $duration,
            'completed' => $completed ? 1 : 0
        ];
        
        if($sectionId !== null) {
            $data['section_id'] = $sectionId;
        }
        
        if($resourceId !== null) {
            $data['resource_id'] = $resourceId;
        }
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update a plan item
     *
     * @param int $id Item ID
     * @param array $data Update data
     * @return bool True on success
     */
    public function updateItem($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete a plan item
     *
     * @param int $id Item ID
     * @return bool True on success
     */
    public function deleteItem($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Mark item as completed or not completed
     *
     * @param int $id Item ID
     * @param bool $completed Completed status
     * @return bool True on success
     */
    public function markAsCompleted($id, $completed = true) {
        $data = ['completed' => $completed ? 1 : 0];
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Reschedule a plan item
     *
     * @param int $id Item ID
     * @param string $newDate New scheduled date (YYYY-MM-DD)
     * @return bool True on success
     */
    public function rescheduleItem($id, $newDate) {
        $data = ['scheduled_date' => $newDate];
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Get items by date range
     *
     * @param int $planId Plan ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param bool $includeDetails Include additional details (optional)
     * @return array Items in the date range
     */
    public function getItemsByDateRange($planId, $startDate, $endDate, $includeDetails = true) {
        if($includeDetails) {
            $this->db->query("SELECT spi.*, is.name as section_name, sr.title as resource_title, sr.file_path
                              FROM {$this->table} spi
                              LEFT JOIN ielts_sections is ON spi.section_id = is.id
                              LEFT JOIN study_resources sr ON spi.resource_id = sr.id
                              WHERE spi.study_plan_id = :plan_id
                              AND spi.scheduled_date BETWEEN :start_date AND :end_date
                              ORDER BY spi.scheduled_date ASC, spi.duration DESC");
        } else {
            $this->db->query("SELECT * FROM {$this->table} 
                              WHERE study_plan_id = :plan_id
                              AND scheduled_date BETWEEN :start_date AND :end_date
                              ORDER BY scheduled_date ASC, duration DESC");
        }
        
        $this->db->bind(':plan_id', $planId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get today's items for a user
     *
     * @param int $userId User ID
     * @param bool $includeDetails Include additional details (optional)
     * @return array Today's items
     */
    public function getTodayItems($userId, $includeDetails = true) {
        $today = date('Y-m-d');
        
        if($includeDetails) {
            $this->db->query("SELECT spi.*, is.name as section_name, sr.title as resource_title, sr.file_path, sp.name as plan_name
                              FROM {$this->table} spi
                              JOIN study_plans sp ON spi.study_plan_id = sp.id
                              LEFT JOIN ielts_sections is ON spi.section_id = is.id
                              LEFT JOIN study_resources sr ON spi.resource_id = sr.id
                              WHERE sp.user_id = :user_id
                              AND sp.status = 'active'
                              AND spi.scheduled_date = :today
                              ORDER BY spi.duration DESC");
        } else {
            $this->db->query("SELECT spi.* FROM {$this->table} spi
                              JOIN study_plans sp ON spi.study_plan_id = sp.id
                              WHERE sp.user_id = :user_id
                              AND sp.status = 'active'
                              AND spi.scheduled_date = :today
                              ORDER BY spi.duration DESC");
        }
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':today', $today);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get upcoming items for a user
     *
     * @param int $userId User ID
     * @param int $days Number of days to look ahead
     * @param bool $includeDetails Include additional details (optional)
     * @return array Upcoming items
     */
    public function getUpcomingItems($userId, $days = 7, $includeDetails = true) {
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        if($includeDetails) {
            $this->db->query("SELECT spi.*, is.name as section_name, sr.title as resource_title, sr.file_path, sp.name as plan_name
                              FROM {$this->table} spi
                              JOIN study_plans sp ON spi.study_plan_id = sp.id
                              LEFT JOIN ielts_sections is ON spi.section_id = is.id
                              LEFT JOIN study_resources sr ON spi.resource_id = sr.id
                              WHERE sp.user_id = :user_id
                              AND sp.status = 'active'
                              AND spi.scheduled_date BETWEEN :today AND :end_date
                              ORDER BY spi.scheduled_date ASC, spi.duration DESC");
        } else {
            $this->db->query("SELECT spi.* FROM {$this->table} spi
                              JOIN study_plans sp ON spi.study_plan_id = sp.id
                              WHERE sp.user_id = :user_id
                              AND sp.status = 'active'
                              AND spi.scheduled_date BETWEEN :today AND :end_date
                              ORDER BY spi.scheduled_date ASC, spi.duration DESC");
        }
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':today', $today);
        $this->db->bind(':end_date', $endDate);
        
        return $this->db->resultSet();
    }
    
    /**
     * Count items by section for a plan
     *
     * @param int $planId Plan ID
     * @return array Count per section
     */
    public function countItemsBySection($planId) {
        $this->db->query("SELECT is.id, is.name, 
                          COUNT(spi.id) as total_count,
                          SUM(spi.completed) as completed_count
                          FROM ielts_sections is
                          LEFT JOIN {$this->table} spi ON is.id = spi.section_id AND spi.study_plan_id = :plan_id
                          GROUP BY is.id, is.name
                          ORDER BY is.name ASC");
        
        $this->db->bind(':plan_id', $planId);
        return $this->db->resultSet();
    }
    
    /**
     * Get overdue items for a user
     *
     * @param int $userId User ID
     * @param bool $includeDetails Include additional details (optional)
     * @return array Overdue items
     */
    public function getOverdueItems($userId, $includeDetails = true) {
        $today = date('Y-m-d');
        
        if($includeDetails) {
            $this->db->query("SELECT spi.*, is.name as section_name, sr.title as resource_title, sr.file_path, sp.name as plan_name
                              FROM {$this->table} spi
                              JOIN study_plans sp ON spi.study_plan_id = sp.id
                              LEFT JOIN ielts_sections is ON spi.section_id = is.id
                              LEFT JOIN study_resources sr ON spi.resource_id = sr.id
                              WHERE sp.user_id = :user_id
                              AND sp.status = 'active'
                              AND spi.scheduled_date < :today
                              AND spi.completed = 0
                              ORDER BY spi.scheduled_date ASC");
        } else {
            $this->db->query("SELECT spi.* FROM {$this->table} spi
                              JOIN study_plans sp ON spi.study_plan_id = sp.id
                              WHERE sp.user_id = :user_id
                              AND sp.status = 'active'
                              AND spi.scheduled_date < :today
                              AND spi.completed = 0
                              ORDER BY spi.scheduled_date ASC");
        }
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':today', $today);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get total study time for a plan
     *
     * @param int $planId Plan ID
     * @return int Total minutes
     */
    public function getTotalStudyTime($planId) {
        $this->db->query("SELECT SUM(duration) as total_minutes FROM {$this->table} 
                          WHERE study_plan_id = :plan_id");
        
        $this->db->bind(':plan_id', $planId);
        $result = $this->db->single();
        
        return $result->total_minutes ?? 0;
    }
    
    /**
     * Get time distribution by section for a plan
     *
     * @param int $planId Plan ID
     * @return array Time distribution per section
     */
    public function getTimeDistributionBySection($planId) {
        $this->db->query("SELECT is.id, is.name, 
                          SUM(spi.duration) as total_minutes
                          FROM ielts_sections is
                          LEFT JOIN {$this->table} spi ON is.id = spi.section_id AND spi.study_plan_id = :plan_id
                          GROUP BY is.id, is.name
                          ORDER BY total_minutes DESC");
        
        $this->db->bind(':plan_id', $planId);
        return $this->db->resultSet();
    }
}