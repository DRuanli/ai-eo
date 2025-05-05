<?php
/**
 * Study Resource Model
 * Handles operations related to study resources
 */
class StudyResource extends Model {
    // Table name
    private $table = 'study_resources';
    
    /**
     * Get all study resources
     *
     * @param string $orderBy Field to order by (optional)
     * @param string $order Order direction (ASC/DESC, optional)
     * @return array All resources
     */
    public function getAllResources($orderBy = 'title', $order = 'ASC') {
        $this->db->query("SELECT sr.*, is.name as section_name 
                          FROM {$this->table} sr
                          JOIN ielts_sections is ON sr.section_id = is.id
                          ORDER BY sr.{$orderBy} {$order}");
        
        return $this->db->resultSet();
    }
    
    /**
     * Get resources by section
     *
     * @param int $sectionId Section ID
     * @return array Resources for the section
     */
    public function getResourcesBySection($sectionId) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE section_id = :section_id
                          ORDER BY title ASC");
        
        $this->db->bind(':section_id', $sectionId);
        return $this->db->resultSet();
    }
    
    /**
     * Get resources by type
     *
     * @param string $type Resource type
     * @return array Resources of the type
     */
    public function getResourcesByType($type) {
        $this->db->query("SELECT sr.*, is.name as section_name 
                          FROM {$this->table} sr
                          JOIN ielts_sections is ON sr.section_id = is.id
                          WHERE sr.resource_type = :type
                          ORDER BY sr.title ASC");
        
        $this->db->bind(':type', $type);
        return $this->db->resultSet();
    }
    
    /**
     * Get resource by ID
     *
     * @param int $id Resource ID
     * @return object Resource data
     */
    public function getResourceById($id) {
        $this->db->query("SELECT sr.*, is.name as section_name 
                          FROM {$this->table} sr
                          JOIN ielts_sections is ON sr.section_id = is.id
                          WHERE sr.id = :id");
        
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Add a new resource
     *
     * @param string $title Resource title
     * @param int $sectionId Section ID
     * @param string $type Resource type
     * @param string $description Resource description (optional)
     * @param string $filePath File path (optional)
     * @return int|bool Resource ID if successful, false otherwise
     */
    public function addResource($title, $sectionId, $type, $description = '', $filePath = '') {
        $data = [
            'title' => $title,
            'section_id' => $sectionId,
            'resource_type' => $type,
            'description' => $description,
            'file_path' => $filePath
        ];
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update a resource
     *
     * @param int $id Resource ID
     * @param array $data Update data
     * @return bool True on success
     */
    public function updateResource($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete a resource
     *
     * @param int $id Resource ID
     * @return bool True on success
     */
    public function deleteResource($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Search resources
     *
     * @param string $keyword Search keyword
     * @param int $sectionId Section ID (optional)
     * @param string $type Resource type (optional)
     * @return array Matching resources
     */
    public function searchResources($keyword, $sectionId = null, $type = null) {
        $sql = "SELECT sr.*, is.name as section_name 
                FROM {$this->table} sr
                JOIN ielts_sections is ON sr.section_id = is.id
                WHERE (sr.title LIKE :keyword OR sr.description LIKE :keyword)";
        
        if($sectionId !== null) {
            $sql .= " AND sr.section_id = :section_id";
        }
        
        if($type !== null) {
            $sql .= " AND sr.resource_type = :type";
        }
        
        $sql .= " ORDER BY sr.title ASC";
        
        $this->db->query($sql);
        $this->db->bind(':keyword', '%' . $keyword . '%');
        
        if($sectionId !== null) {
            $this->db->bind(':section_id', $sectionId);
        }
        
        if($type !== null) {
            $this->db->bind(':type', $type);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Count resources by section
     *
     * @return array Count of resources for each section
     */
    public function countResourcesBySection() {
        $this->db->query("SELECT is.id, is.name, COUNT(sr.id) as resource_count
                          FROM ielts_sections is
                          LEFT JOIN {$this->table} sr ON is.id = sr.section_id
                          GROUP BY is.id, is.name
                          ORDER BY is.name ASC");
        
        return $this->db->resultSet();
    }
    
    /**
     * Count resources by type
     *
     * @return array Count of resources for each type
     */
    public function countResourcesByType() {
        $this->db->query("SELECT resource_type, COUNT(*) as resource_count
                          FROM {$this->table}
                          GROUP BY resource_type
                          ORDER BY resource_count DESC");
        
        return $this->db->resultSet();
    }
    
    /**
     * Get resource types
     *
     * @return array Distinct resource types
     */
    public function getResourceTypes() {
        $this->db->query("SELECT DISTINCT resource_type FROM {$this->table} ORDER BY resource_type ASC");
        return $this->db->resultSet();
    }
}