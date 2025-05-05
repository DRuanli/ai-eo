<?php
/**
 * Base Controller
 * Loads models and views
 */
class Controller {
    /**
     * Load model
     *
     * @param string $model Model name
     * @return object Model instance
     */
    public function model($model) {
        // Require model file
        require_once '../app/models/' . $model . '.php';
        
        // Instantiate model
        return new $model();
    }
    
    /**
     * Load view
     *
     * @param string $view View name
     * @param array $data Data passed to the view
     * @return void
     */
    public function view($view, $data = []) {
        // Check for view file
        $viewFile = '../app/views/' . $view . '.php';
        
        if(file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            // View does not exist
            die('View ' . $view . ' does not exist');
        }
    }
    
    /**
     * Redirect to page
     *
     * @param string $page
     * @return void
     */
    public function redirect($page) {
        header('Location: ' . URLROOT . '/' . $page);
        exit;
    }
    
    /**
     * Flash message helper
     *
     * @param string $name
     * @param string $message
     * @param string $class
     * @return void
     */
    public function flash($name = '', $message = '', $class = 'alert alert-success') {
        if(!empty($name)) {
            if(!empty($message) && empty($_SESSION[$name])) {
                if(!empty($_SESSION[$name])) {
                    unset($_SESSION[$name]);
                }
                
                if(!empty($_SESSION[$name . '_class'])) {
                    unset($_SESSION[$name . '_class']);
                }
                
                $_SESSION[$name] = $message;
                $_SESSION[$name . '_class'] = $class;
            } elseif(empty($message) && !empty($_SESSION[$name])) {
                $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
                echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
                unset($_SESSION[$name]);
                unset($_SESSION[$name . '_class']);
            }
        }
    }
}