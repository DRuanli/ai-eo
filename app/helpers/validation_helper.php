<?php
/**
 * Validation Helper
 * Contains functions for data validation
 */

/**
 * Validate email address
 *
 * @param string $email Email to validate
 * @return boolean
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 * 
 * Checks if password meets minimum requirements:
 * - At least 8 characters
 * - Contains at least one uppercase letter
 * - Contains at least one lowercase letter
 * - Contains at least one number
 *
 * @param string $password Password to validate
 * @return boolean
 */
function isStrongPassword($password) {
    // Check password length
    if(strlen($password) < 8) {
        return false;
    }
    
    // Check for uppercase letter
    if(!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Check for lowercase letter
    if(!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Check for number
    if(!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Validate date format
 *
 * @param string $date Date to validate
 * @param string $format Format to check against (default: Y-m-d)
 * @return boolean
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate numeric value
 *
 * @param mixed $value Value to check
 * @param float $min Minimum value (optional)
 * @param float $max Maximum value (optional)
 * @return boolean
 */
function isValidNumber($value, $min = null, $max = null) {
    // Check if it's numeric
    if(!is_numeric($value)) {
        return false;
    }
    
    // Check min value if provided
    if($min !== null && $value < $min) {
        return false;
    }
    
    // Check max value if provided
    if($max !== null && $value > $max) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize input data
 *
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if(is_array($data)) {
        foreach($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Validate IELTS band score
 *
 * @param float $score Score to validate
 * @return boolean
 */
function isValidIELTSScore($score) {
    // IELTS scores range from 0 to 9 in 0.5 increments
    $validScores = [0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 7.5, 8, 8.5, 9];
    return in_array((float)$score, $validScores);
}

/**
 * Validate username
 * 
 * Checks if username meets the following criteria:
 * - 3-20 characters long
 * - Only contains alphanumeric characters, underscores, and hyphens
 *
 * @param string $username Username to validate
 * @return boolean
 */
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username);
}

/**
 * Check if string exceeds maximum length
 *
 * @param string $string String to check
 * @param int $maxLength Maximum allowed length
 * @return boolean
 */
function exceedsMaxLength($string, $maxLength) {
    return strlen($string) > $maxLength;
}

/**
 * Check if file is valid image
 *
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return boolean|string True if valid, error message if not
 */
function isValidImage($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 2097152) {
    // Check if file was uploaded successfully
    if($file['error'] !== UPLOAD_ERR_OK) {
        return 'File upload error: ' . $file['error'];
    }
    
    // Check file type
    if(!in_array($file['type'], $allowedTypes)) {
        return 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
    }
    
    // Check file size
    if($file['size'] > $maxSize) {
        return 'File is too large. Maximum size: ' . ($maxSize / 1048576) . 'MB';
    }
    
    return true;
}

/**
 * Generate random string
 *
 * @param int $length Length of the string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}