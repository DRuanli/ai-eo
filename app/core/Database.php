<?php
/**
 * PDO Database Class
 * Connect to database
 * Create prepared statements
 * Bind values
 * Return rows and results
 */
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;
    private $stmt;
    private $error;
    
    /**
     * Constructor - establish database connection
     */
    public function __construct() {
        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Create PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Database Connection Error: ' . $this->error;
            die();
        }
    }
    
    /**
     * Prepare statement with query
     *
     * @param string $sql SQL query
     * @return void
     */
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }
    
    /**
     * Bind values
     *
     * @param string $param Parameter placeholder
     * @param mixed $value Actual value
     * @param mixed $type Parameter type
     * @return void
     */
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
    }
    
    /**
     * Execute the prepared statement
     *
     * @return boolean
     */
    public function execute() {
        return $this->stmt->execute();
    }
    
    /**
     * Get result set as array of objects
     *
     * @return array
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    /**
     * Get single record as object
     *
     * @return object
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    /**
     * Get row count
     *
     * @return int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Get last inserted ID
     *
     * @return string Last inserted ID
     */
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
    
    /**
     * Start transaction
     *
     * @return boolean
     */
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    /**
     * Commit transaction
     *
     * @return boolean
     */
    public function commit() {
        return $this->dbh->commit();
    }
    
    /**
     * Rollback transaction
     *
     * @return boolean
     */
    public function rollBack() {
        return $this->dbh->rollBack();
    }
    
    /**
     * Debug - dumps parameters
     *
     * @return array Parameters
     */
    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }
}