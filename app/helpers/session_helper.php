<?php
/**
 * Session Helper
 * Manages session related functionality
 */

// Start session if not already started
if(session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Flash message helper
 * 
 * EXAMPLE - flash('register_success', 'You are now registered');
 * DISPLAY IN VIEW - echo flash('register_success');
 *
 * @param string $name
 * @param string $message
 * @param string $class
 * @return string
 */
function flash($name = '', $message = '', $class = 'alert alert-success') {
    if(!empty($name)) {
        // Set Flash
        if(!empty($message) && empty($_SESSION[$name])) {
            if(!empty($_SESSION[$name])) {
                unset($_SESSION[$name]);
            }
            
            if(!empty($_SESSION[$name . '_class'])) {
                unset($_SESSION[$name . '_class']);
            }
            
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } 
        // Display Flash
        elseif(empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

/**
 * Check if user is logged in
 *
 * @return boolean
 */
function isLoggedIn() {
    if(isset($_SESSION['user_id'])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get current logged in user
 *
 * @return object|null User object or null
 */
function getCurrentUser() {
    if(isLoggedIn()) {
        $userModel = new User();
        return $userModel->findById('users', $_SESSION['user_id']);
    } else {
        return null;
    }
}

/**
 * Redirect if not logged in
 *
 * @param string $location Location to redirect to
 * @return void
 */
function requireLogin($location = 'users/login') {
    if(!isLoggedIn()) {
        flash('login_required', 'Please log in to access this page', 'alert alert-danger');
        redirect($location);
    }
}

/**
 * Redirect to page
 *
 * @param string $page
 * @return void
 */
function redirect($page) {
    header('Location: ' . URLROOT . '/' . $page);
    exit;
}

/**
 * Generate CSRF token
 *
 * @return string Token
 */
function generateCsrfToken() {
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Check if CSRF token is valid
 *
 * @param string $token Token to check
 * @return boolean
 */
function checkCsrfToken($token) {
    if(isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    
    return false;
}

/**
 * Set session timeout
 *
 * @param int $minutes Minutes of inactivity
 * @return void
 */
function setSessionTimeout($minutes = 30) {
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['EXPIRE_TIME'] = $minutes * 60;
}

/**
 * Check session timeout
 *
 * @return boolean
 */
function checkSessionTimeout() {
    if(isset($_SESSION['LAST_ACTIVITY']) && isset($_SESSION['EXPIRE_TIME'])) {
        if((time() - $_SESSION['LAST_ACTIVITY']) > $_SESSION['EXPIRE_TIME']) {
            session_unset();
            session_destroy();
            return true;
        }
    }
    
    $_SESSION['LAST_ACTIVITY'] = time();
    return false;
}