<?php
/**
 * User Model
 * Handles all operations related to users
 */
class User extends Model {
    // Table name
    private $table = 'users';
    
    /**
     * Register new user
     *
     * @param array $data User data
     * @return int|bool User ID if successful, false otherwise
     */
    public function register($data) {
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return $this->add($this->table, $data);
    }
    
    /**
     * Check if user exists by email
     *
     * @param string $email Email to check
     * @return bool True if user exists
     */
    public function findUserByEmail($email) {
        $this->db->query("SELECT * FROM {$this->table} WHERE email = :email");
        $this->db->bind(':email', $email);
        
        $row = $this->db->single();
        
        return ($this->db->rowCount() > 0);
    }
    
    /**
     * Check if user exists by username
     *
     * @param string $username Username to check
     * @return bool True if user exists
     */
    public function findUserByUsername($username) {
        $this->db->query("SELECT * FROM {$this->table} WHERE username = :username");
        $this->db->bind(':username', $username);
        
        $row = $this->db->single();
        
        return ($this->db->rowCount() > 0);
    }
    
    /**
     * Get user by ID
     *
     * @param int $id User ID
     * @return object User data
     */
    public function getUserById($id) {
        return $this->findById($this->table, $id);
    }
    
    /**
     * Authenticate user login
     *
     * @param string $email Email or username
     * @param string $password Password to verify
     * @return object|bool User object if authenticated, false otherwise
     */
    public function login($email, $password) {
        // Check if input is email or username
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        } else {
            $field = 'username';
        }
        
        $this->db->query("SELECT * FROM {$this->table} WHERE {$field} = :{$field}");
        $this->db->bind(":{$field}", $email);
        
        $user = $this->db->single();
        
        if(!$user) {
            return false;
        }
        
        // Verify password
        if(password_verify($password, $user->password)) {
            return $user;
        } else {
            return false;
        }
    }
    
    /**
     * Update user profile
     *
     * @param int $id User ID
     * @param array $data Data to update
     * @return bool True on success
     */
    public function updateProfile($id, $data) {
        // Don't update password if empty
        if(isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        } elseif(isset($data['password'])) {
            // Hash new password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Update target score
     *
     * @param int $id User ID
     * @param float $score Target score
     * @return bool True on success
     */
    public function updateTargetScore($id, $score) {
        $data = ['target_score' => $score];
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Update test date
     *
     * @param int $id User ID
     * @param string $date Test date (YYYY-MM-DD)
     * @return bool True on success
     */
    public function updateTestDate($id, $date) {
        $data = ['test_date' => $date];
        return $this->update($this->table, $id, $data);
    }
    
    /**
     * Get days until test
     *
     * @param int $id User ID
     * @return int|null Days until test or null if no test date
     */
    public function getDaysUntilTest($id) {
        $user = $this->getUserById($id);
        
        if(!$user || !$user->test_date) {
            return null;
        }
        
        $testDate = new DateTime($user->test_date);
        $today = new DateTime();
        
        $interval = $today->diff($testDate);
        
        return $interval->days;
    }
}