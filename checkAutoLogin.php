<?php
// checkAutoLogin.php - Check for auto-login using remember token
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

$response = array('success' => false);

try {
    // Check if user is already logged in via session
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $response['success'] = true;
        $response['message'] = 'Already logged in';
        
        // Set redirect based on user type
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            $response['redirect'] = 'adminPanel.php';
        } else {
            $response['redirect'] = 'home.php';
        }
        
        echo json_encode($response);
        exit();
    }

    // Check for remember token in cookies
    if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
        $rememberToken = $_COOKIE['remember_token'];
        
        // Validate token from database
        $query = "SELECT user_id, first_name, last_name, email, user_type, mobile, profile_image, status 
                  FROM users 
                  WHERE remember_token = ? 
                  AND remember_token_expires > NOW() 
                  AND status = 'active' 
                  LIMIT 1";
        
        $user = Database::getSingleRow($query, [$rememberToken], 's');
        
        if ($user) {
            // Token is valid, log user in
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_last_name'] = $user['last_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_mobile'] = $user['mobile'];
            $_SESSION['user_profile_image'] = $user['profile_image'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();

            // Update last login
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
            $stmt = Database::prepare($updateQuery, [$user['user_id']], 'i');
            if ($stmt) {
                $stmt->execute();
            }

            $response['success'] = true;
            $response['message'] = 'Auto-login successful';
            
            // Set redirect based on user type
            if ($user['user_type'] === 'admin') {
                $response['redirect'] = 'adminPanel.php';
            } else {
                $response['redirect'] = 'home.php';
            }
        } else {
            // Invalid or expired token, clear cookie
            setcookie('remember_token', '', time() - 3600, '/');
            $response['message'] = 'Token expired or invalid';
        }
    } else {
        $response['message'] = 'No remember token found';
    }

} catch (Exception $e) {
    error_log("Auto-login error: " . $e->getMessage());
    $response['message'] = 'Auto-login check failed';
}

echo json_encode($response);
Database::closeConnection();
?>