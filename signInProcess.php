<?php
// signInProcess.php - Secure user login processing
session_start();
require_once 'connection.php';

// Set JSON content type for AJAX responses
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$rememberMe = isset($_POST['remember_me']) ? $_POST['remember_me'] : false;

// Response array
$response = array();

try {
    // Input validation
    if (empty($email)) {
        $response['success'] = false;
        $response['message'] = 'Please enter your email address.';
        echo json_encode($response);
        exit();
    }

    if (empty($password)) {
        $response['success'] = false;
        $response['message'] = 'Please enter your password.';
        echo json_encode($response);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['success'] = false;
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit();
    }

    // Check if user exists using prepared statement
    $query = "SELECT user_id, first_name, last_name, email, password, status, user_type, mobile, profile_image 
              FROM users WHERE email = ? LIMIT 1";
    
    $user = Database::getSingleRow($query, [$email], 's');

    if (!$user) {
        $response['success'] = false;
        $response['message'] = 'Invalid email or password.';
        echo json_encode($response);
        exit();
    }

    // Verify password using password_verify (secure hash comparison)
    if (!password_verify($password, $user['password'])) {
        $response['success'] = false;
        $response['message'] = 'Invalid email or password.';
        echo json_encode($response);
        exit();
    }

    // Check account status
    if ($user['status'] === 'blocked') {
        $response['success'] = false;
        $response['message'] = 'Your account has been blocked. Please contact support.';
        echo json_encode($response);
        exit();
    }

    if ($user['status'] === 'inactive') {
        $response['success'] = false;
        $response['message'] = 'Please verify your account first. Check your email for verification link.';
        echo json_encode($response);
        exit();
    }

    // Login successful - Set session variables
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

    // Update last login timestamp
    $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
    Database::prepare($updateQuery, [$user['user_id']], 'i');
    
    $stmt = Database::prepare($updateQuery, [$user['user_id']], 'i');
    if ($stmt) {
        $stmt->execute();
    }

    // Handle remember me functionality (secure approach)
    if ($rememberMe === 'true' || $rememberMe === true) {
        // Generate a secure random token
        $rememberToken = bin2hex(random_bytes(32));
        
        // Store token in database (you might want to create a remember_tokens table)
        $tokenQuery = "UPDATE users SET remember_token = ?, remember_token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) 
                       WHERE user_id = ?";
        $stmt = Database::prepare($tokenQuery, [$rememberToken, $user['user_id']], 'si');
        
        if ($stmt && $stmt->execute()) {
            // Set secure cookie with the token
            $cookieOptions = array(
                'expires' => time() + (30 * 24 * 60 * 60), // 30 days
                'path' => '/',
                'domain' => '', // Set your domain if needed
                'secure' => isset($_SERVER['HTTPS']), // Only send over HTTPS
                'httponly' => true, // Prevent XSS attacks
                'samesite' => 'Strict' // CSRF protection
            );
            
            setcookie('remember_token', $rememberToken, $cookieOptions);
        }
    } else {
        // Clear remember me cookie and token
        setcookie('remember_token', '', time() - 3600, '/');
        $clearTokenQuery = "UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE user_id = ?";
        $stmt = Database::prepare($clearTokenQuery, [$user['user_id']], 'i');
        if ($stmt) {
            $stmt->execute();
        }
    }

    // Prepare success response
    $response['success'] = true;
    $response['message'] = 'Login successful! Welcome back, ' . $user['first_name'] . '!';
    $response['user'] = array(
        'user_id' => $user['user_id'],
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'profile_image' => $user['profile_image']
    );

    // Set redirect URL based on user type
    if ($user['user_type'] === 'admin') {
        $response['redirect'] = 'adminPanel.php';
    } else {
        $response['redirect'] = 'home.php';
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Log error for debugging (don't expose to user)
    error_log("Sign in error: " . $e->getMessage());
    
    $response['success'] = false;
    $response['message'] = 'An error occurred during login. Please try again.';
    echo json_encode($response);
}

// Close database connection
Database::closeConnection();
?>