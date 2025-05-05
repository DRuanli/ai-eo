<?php
/**
 * User Resource Model
 * Handles operations related to user-resource relationships
 */
class UserResource extends Model {
    // Table name
    private $table = 'user_resources';
    
    /**
     * Get all resources for a user
     *
     * @param int $userId User ID
     * @param bool $includeDetails Include resource details (optional)
     * @return array User resources
     */
    public function getUserResources($userId, $includeDetails = true) {
        if($includeDetails) {
            $this->db->query("SELECT ur.*, sr.title, sr.resource_type, sr.file_path, is.name as section_name
                              FROM {$this->table} ur
                              JOIN study_resources sr ON ur.resource_id = sr.id
                              JOIN ielts_sections is ON sr.section_id = is.id
                              WHERE ur.user_id = :user_id
                              ORDER BY ur.last_accessed DESC");
        } else {
            $this->db->query("SELECT * FROM {$this->table} 
                              WHERE user_id = :user_id
                              ORDER BY last_accessed DESC");
        }
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get user resource by ID
     *
     * @param int $id User resource ID
     * @param bool $includeDetails Include resource details (optional)
     * @return object User resource data
     */
    public function getUserResourceById($id, $includeDetails = true) {
        if($includeDetails) {
            $this->db->query("SELECT ur.*, sr.title, sr.resource_type, sr.file_path, is.name as section_name
                              FROM {$this->table} ur
                              JOIN study_resources sr ON ur.resource_id = sr.id
                              JOIN ielts_sections is ON sr.section_id = is.id
                              WHERE ur.id = :id");
        } else {
            $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        }
        
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Check if a user has access to a resource
     *
     * @param int $userId User ID
     * @param int $resourceId Resource ID
     * @return object|bool User resource data or false if not found
     */
    public function getUserResource($userId, $resourceId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND resource_id = :resource_id");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':resource_id', $resourceId);
        
        $userResource = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $userResource;
        } else {
            return false;
        }
    }
    
    /**
     * Add resource to user's collection
     *
     * @param int $userId User ID
     * @param int $resourceId Resource ID
     * @return int|bool User resource ID if successful, false otherwise
     */
    public function addUserResource($userId, $resourceId) {
        // Check if already exists
        if($this->getUserResource($userId, $resourceId)) {
            return false;
        }
        
        $data = [
            'user_id' => $userId,
            'resource_id' => $resourceId,
            'completed' => false
        ];
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update user resource
     *
     * @param int $id User resource ID
     * @param array $data Update data
     * @return bool True on success
     */
    public function updateUserResource($id, $data) {
        // Always update last_accessed when modifying
        $data['last_accessed'] = date('Y-m-d H:i:s');
        
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Mark resource as completed or not completed
     *
     * @param int $id User resource ID
     * @param bool $completed Completed status
     * @return bool True on success
     */
    public function markAsCompleted($id, $completed = true) {
        $data = [
            'completed' => $completed,
            'last_accessed' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Rate a resource
     *
     * @param int $id User resource ID
     * @param int $rating Rating (1-5)
     * @return bool True on success
     */
    public function rateResource($id, $rating) {
        $data = [
            'rating' => $rating,
            'last_accessed' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Update last accessed time
     *
     * @param int $id User resource ID
     * @return bool True on success
     */
    public function updateLastAccessed($id) {
        $data = [
            'last_accessed' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Remove resource from user's collection
     *
     * @param int $id User resource ID
     * @return bool True on success
     */
    public function removeUserResource($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Get user's completed resources
     *
     * @param int $userId User ID
     * @param bool $includeDetails Include resource details (optional)
     * @return array Completed resources
     */
    public function getCompletedResources($userId, $includeDetails = true) {
        if($includeDetails) {
            $this->db->query("SELECT ur.*, sr.title, sr.resource_type, sr.file_path, is.name as section_name
                              FROM {$this->table} ur
                              JOIN study_resources sr ON ur.resource_id = sr.id
                              JOIN ielts_sections is ON sr.section_id = is.id
                              WHERE ur.user_id = :user_id
                              AND ur.completed = 1
                              ORDER BY ur.last_accessed DESC");
        } else {
            $this->db->query("SELECT * FROM {$this->table} 
                              WHERE user_id = :user_id
                              AND completed = 1
                              ORDER BY last_accessed DESC");
        }
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get user's resources by section
     *
     * @param int $userId User ID
     * @param int $sectionId Section ID
     * @return array User resources for the section
     */
    public function getUserResourcesBySection($userId, $sectionId) {
        $this->db->query("SELECT ur.*, sr.title, sr.resource_type, sr.file_path
                          FROM {$this->table} ur
                          JOIN study_resources sr ON ur.resource_id = sr.id
                          WHERE ur.user_id = :user_id
                          AND sr.section_id = :section_id
                          ORDER BY ur.last_accessed DESC");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':section_id', $sectionId);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get user's resources by type
     *
     * @param int $userId User ID
     * @param string $type Resource type
     * @return array User resources of the type
     */
    public function getUserResourcesByType($userId, $type) {
        $this->db->query("SELECT ur.*, sr.title, sr.resource_type, sr.file_path, is.name as section_name
                          FROM {$this->table} ur
                          JOIN study_resources sr ON ur.resource_id = sr.id
                          JOIN ielts_sections is ON sr.section_id = is.id
                          WHERE ur.user_id = :user_id
                          AND sr.resource_type = :type
                          ORDER BY ur.last_accessed DESC");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':type', $type);
        
        return $this->db->resultSet();
    }
    
    /**
     * Count completed resources by section
     *
     * @param int $userId User ID
     * @return array Counts per section
     */
    public function countCompletedBySection($userId) {
        $this->db->query("SELECT is.id, is.name, 
                          COUNT(ur.id) as total_count,
                          SUM(ur.completed) as completed_count
                          FROM ielts_sections is
                          LEFT JOIN study_resources sr ON is.id = sr.section_id
                          LEFT JOIN {$this->table} ur ON sr.id = ur.resource_id AND ur.user_id = :user_id
                          GROUP BY is.id, is.name
                          ORDER BY is.name ASC");
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
}