<?php
/**
 * Base Model Class
 * Provides base functionality for all models
 */
class Model {
    protected $db;
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        $this->db = new Database;
    }
    
    /**
     * Find all records from a table
     *
     * @param string $table Table name
     * @param string $order Order by clause (optional)
     * @return array Records
     */
    public function findAll($table, $order = '') {
        $sql = "SELECT * FROM $table";
        
        if(!empty($order)) {
            $sql .= " ORDER BY $order";
        }
        
        $this->db->query($sql);
        return $this->db->resultSet();
    }
    
    /**
     * Find record by ID
     *
     * @param string $table Table name
     * @param int $id ID to find
     * @return object Record
     */
    public function findById($table, $id) {
        $this->db->query("SELECT * FROM $table WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Find records by specific field
     *
     * @param string $table Table name
     * @param string $field Field name
     * @param mixed $value Field value
     * @return array Records
     */
    public function findByField($table, $field, $value) {
        $this->db->query("SELECT * FROM $table WHERE $field = :value");
        $this->db->bind(':value', $value);
        return $this->db->resultSet();
    }
    
    /**
     * Add a record to database
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return boolean True on success
     */
    public function add($table, $data) {
        // Create column and value strings
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $this->db->query("INSERT INTO $table ($columns) VALUES($placeholders)");
        
        // Bind values
        foreach($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        
        // Execute
        if($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }
    
    /**
     * Update a record in database
     *
     * @param string $table Table name
     * @param int $id ID to update
     * @param array $data Data to update
     * @return boolean True on success
     */
    public function update($table, $id, $data) {
        // Create placeholder string
        $placeholders = '';
        foreach($data as $key => $value) {
            $placeholders .= "$key = :$key, ";
        }
        $placeholders = rtrim($placeholders, ', ');
        
        $this->db->query("UPDATE $table SET $placeholders WHERE id = :id");
        
        // Bind values
        foreach($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        $this->db->bind(':id', $id);
        
        // Execute
        return $this->db->execute();
    }
    
    /**
     * Delete a record from database
     *
     * @param string $table Table name
     * @param int $id ID to delete
     * @return boolean True on success
     */
    public function delete($table, $id) {
        $this->db->query("DELETE FROM $table WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    /**
     * Execute custom query
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return mixed Result set or boolean
     */
    public function executeQuery($sql, $params = []) {
        $this->db->query($sql);
        
        // Bind values if any
        foreach($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        // If query contains SELECT, return resultSet
        if(stripos($sql, 'SELECT') === 0) {
            return $this->db->resultSet();
        } else {
            return $this->db->execute();
        }
    }
}