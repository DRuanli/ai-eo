<?php
/**
 * Weak Area Model
 * Handles operations related to weak areas
 */
class WeakArea extends Model {
    // Table name
    private $table = 'weak_areas';
    
    /**
     * Get all weak areas for a user
     *
     * @param int $userId User ID
     * @param bool $includeSection Include section details (optional)
     * @return array Weak areas
     */
    public function getUserWeakAreas($userId, $includeSection = true) {
        if($includeSection) {
            $this->db->query("SELECT wa.*, is.name as section_name
                              FROM {$this->table} wa
                              JOIN ielts_sections is ON wa.section_id = is.id
                              WHERE wa.user_id = :user_id
                              ORDER BY wa.priority DESC, is.name ASC");
        } else {
            $this->db->query("SELECT * FROM {$this->table} 
                              WHERE user_id = :user_id
                              ORDER BY priority DESC");
        }
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get weak area by ID
     *
     * @param int $id Weak area ID
     * @param bool $includeSection Include section details (optional)
     * @return object Weak area data
     */
    public function getWeakAreaById($id, $includeSection = true) {
        if($includeSection) {
            $this->db->query("SELECT wa.*, is.name as section_name
                              FROM {$this->table} wa
                              JOIN ielts_sections is ON wa.section_id = is.id
                              WHERE wa.id = :id");
        } else {
            $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        }
        
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Get weak areas by section
     *
     * @param int $userId User ID
     * @param int $sectionId Section ID
     * @return array Weak areas for the section
     */
    public function getWeakAreasBySection($userId, $sectionId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND section_id = :section_id
                          ORDER BY priority DESC");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':section_id', $sectionId);
        
        return $this->db->resultSet();
    }
    
    /**
     * Add a weak area
     *
     * @param int $userId User ID
     * @param int $sectionId Section ID
     * @param string $subSkill Sub-skill name
     * @param int $priority Priority level (1-5, optional)
     * @return int|bool Weak area ID if successful, false otherwise
     */
    public function addWeakArea($userId, $sectionId, $subSkill, $priority = 1) {
        $data = [
            'user_id' => $userId,
            'section_id' => $sectionId,
            'sub_skill' => $subSkill,
            'priority' => $priority
        ];
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update a weak area
     *
     * @param int $id Weak area ID
     * @param array $data Update data
     * @return bool True on success
     */
    public function updateWeakArea($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete a weak area
     *
     * @param int $id Weak area ID
     * @return bool True on success
     */
    public function deleteWeakArea($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Update priority for a weak area
     *
     * @param int $id Weak area ID
     * @param int $priority New priority level (1-5)
     * @return bool True on success
     */
    public function updatePriority($id, $priority) {
        $data = ['priority' => $priority];
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Get top weak areas for a user
     *
     * @param int $userId User ID
     * @param int $limit Number of areas to return (optional)
     * @return array Top weak areas
     */
    public function getTopWeakAreas($userId, $limit = 5) {
        $this->db->query("SELECT wa.*, is.name as section_name
                          FROM {$this->table} wa
                          JOIN ielts_sections is ON wa.section_id = is.id
                          WHERE wa.user_id = :user_id
                          ORDER BY wa.priority DESC, is.name ASC
                          LIMIT :limit");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Check if weak area exists
     *
     * @param int $userId User ID
     * @param int $sectionId Section ID
     * @param string $subSkill Sub-skill name
     * @return bool True if exists
     */
    public function weakAreaExists($userId, $sectionId, $subSkill) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE user_id = :user_id 
                          AND section_id = :section_id
                          AND sub_skill = :sub_skill");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':section_id', $sectionId);
        $this->db->bind(':sub_skill', $subSkill);
        
        $this->db->single();
        return ($this->db->rowCount() > 0);
    }
    
    /**
     * Count weak areas by section
     *
     * @param int $userId User ID
     * @return array Counts per section
     */
    public function countWeakAreasBySection($userId) {
        $this->db->query("SELECT is.id, is.name, COUNT(wa.id) as weak_area_count
                          FROM ielts_sections is
                          LEFT JOIN {$this->table} wa ON is.id = wa.section_id AND wa.user_id = :user_id
                          GROUP BY is.id, is.name
                          ORDER BY weak_area_count DESC, is.name ASC");
        
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get common sub-skills by section
     *
     * @param int $sectionId Section ID
     * @return array Common sub-skills
     */
    public function getCommonSubSkills($sectionId) {
        // This returns a static list of common sub-skills per section
        // In a real application, this might come from a database
        $commonSubSkills = [
            // Reading
            1 => [
                'Skimming for main ideas',
                'Scanning for specific information',
                'Understanding vocabulary in context',
                'Identifying writer\'s views',
                'True/False/Not Given questions',
                'Yes/No/Not Given questions',
                'Matching headings',
                'Sentence completion',
                'Multiple choice questions'
            ],
            // Writing
            2 => [
                'Task achievement/response',
                'Coherence and cohesion',
                'Lexical resource/vocabulary',
                'Grammatical range and accuracy',
                'Task 1 graph description',
                'Task 1 process description',
                'Task 2 argument development',
                'Task 2 essay structure',
                'Paragraph organization'
            ],
            // Listening
            3 => [
                'Identifying main ideas',
                'Identifying specific details',
                'Understanding speaker opinions',
                'Following a conversation',
                'Form completion',
                'Multiple choice questions',
                'Matching exercises',
                'Note completion',
                'Sentence completion'
            ],
            // Speaking
            4 => [
                'Fluency and coherence',
                'Lexical resource/vocabulary',
                'Grammatical range and accuracy',
                'Pronunciation',
                'Part 1 responses',
                'Part 2 individual long turn',
                'Part 3 discussion',
                'Expressing opinions',
                'Developing ideas'
            ]
        ];
        
        if(isset($commonSubSkills[$sectionId])) {
            return $commonSubSkills[$sectionId];
        } else {
            return [];
        }
    }
}