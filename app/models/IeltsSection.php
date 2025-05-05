<?php
/**
 * IELTS Section Model
 * Handles operations related to IELTS test sections (Reading, Writing, Listening, Speaking)
 */
class IeltsSection extends Model {
    // Table name
    private $table = 'ielts_sections';
    
    /**
     * Get all IELTS sections
     *
     * @return array All sections
     */
    public function getAllSections() {
        return $this->findAll($this->table, 'name ASC');
    }
    
    /**
     * Get section by ID
     *
     * @param int $id Section ID
     * @return object Section data
     */
    public function getSectionById($id) {
        return $this->findById($this->table, $id);
    }
    
    /**
     * Get section by name
     *
     * @param string $name Section name
     * @return object|bool Section data or false if not found
     */
    public function getSectionByName($name) {
        $this->db->query("SELECT * FROM {$this->table} WHERE name = :name");
        $this->db->bind(':name', $name);
        
        $section = $this->db->single();
        
        if($this->db->rowCount() > 0) {
            return $section;
        } else {
            return false;
        }
    }
    
    /**
     * Add new section
     *
     * @param string $name Section name
     * @param string $description Section description
     * @return int|bool Section ID or false if failed
     */
    public function addSection($name, $description = '') {
        $data = [
            'name' => $name,
            'description' => $description
        ];
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Update section
     *
     * @param int $id Section ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function updateSection($id, $data) {
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Delete section
     *
     * @param int $id Section ID
     * @return bool Success status
     */
    public function deleteSection($id) {
        return $this->delete($this->table, $id);
    }
    
    /**
     * Initialize default IELTS sections if none exist
     *
     * @return void
     */
    public function initDefaultSections() {
        $sections = $this->getAllSections();
        
        if(count($sections) == 0) {
            $defaultSections = [
                ['name' => 'Reading', 'description' => 'IELTS Reading section tests your ability to understand written texts.'],
                ['name' => 'Writing', 'description' => 'IELTS Writing section tests your ability to produce written responses.'],
                ['name' => 'Listening', 'description' => 'IELTS Listening section tests your ability to understand spoken English.'],
                ['name' => 'Speaking', 'description' => 'IELTS Speaking section tests your ability to communicate in spoken English.']
            ];
            
            foreach($defaultSections as $section) {
                $this->addSection($section['name'], $section['description']);
            }
        }
    }
}