<?php
// signupProcess.php - Secure user registration processing
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

// Get POST data and sanitize
$firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim(strtolower($_POST['email'])) : '';
$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$agreeTerms = isset($_POST['agree_terms']) && $_POST['agree_terms'];

// Response array
$response = array();

try {
    // Comprehensive input validation
    if (empty($firstName)) {
        $response['success'] = false;
        $response['message'] = 'First name is required.';
        echo json_encode($response);
        exit();
    }

    if (empty($lastName)) {
        $response['success'] = false;
        $response['message'] = 'Last name is required.';
        echo json_encode($response);
        exit();
    }

    if (empty($email)) {
        $response['success'] = false;
        $response['message'] = 'Email address is required.';
        echo json_encode($response);
        exit();
    }

    if (empty($mobile)) {
        $response['success'] = false;
        $response['message'] = 'Mobile number is required.';
        echo json_encode($response);
        exit();
    }

    if (empty($gender)) {
        $response['success'] = false;
        $response['message'] = 'Please select your gender.';
        echo json_encode($response);
        exit();
    }

    if (empty($password)) {
        $response['success'] = false;
        $response['message'] = 'Password is required.';
        echo json_encode($response);
        exit();
    }

    if (empty($confirmPassword)) {
        $response['success'] = false;
        $response['message'] = 'Please confirm your password.';
        echo json_encode($response);
        exit();
    }

    if (!$agreeTerms) {
        $response['success'] = false;
        $response['message'] = 'Please agree to the terms and conditions.';
        echo json_encode($response);
        exit();
    }

    // Validate name format (letters, spaces, hyphens only)
    if (!preg_match('/^[a-zA-Z\s\-]+$/', $firstName)) {
        $response['success'] = false;
        $response['message'] = 'First name can only contain letters, spaces, and hyphens.';
        echo json_encode($response);
        exit();
    }

    if (!preg_match('/^[a-zA-Z\s\-]+$/', $lastName)) {
        $response['success'] = false;
        $response['message'] = 'Last name can only contain letters, spaces, and hyphens.';
        echo json_encode($response);
        exit();
    }

    // Validate name length
    if (strlen($firstName) < 2 || strlen($firstName) > 50) {
        $response['success'] = false;
        $response['message'] = 'First name must be between 2 and 50 characters.';
        echo json_encode($response);
        exit();
    }

    if (strlen($lastName) < 2 || strlen($lastName) > 50) {
        $response['success'] = false;
        $response['message'] = 'Last name must be between 2 and 50 characters.';
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

    // Validate email length
    if (strlen($email) > 100) {
        $response['success'] = false;
        $response['message'] = 'Email address is too long.';
        echo json_encode($response);
        exit();
    }

    // Validate mobile number (Sri Lankan format: 10 digits starting with 0)
    $cleanMobile = preg_replace('/\D/', '', $mobile); // Remove non-digits
    if (!preg_match('/^0[0-9]{9}$/', $cleanMobile)) {
        $response['success'] = false;
        $response['message'] = 'Please enter a valid 10-digit mobile number starting with 0.';
        echo json_encode($response);
        exit();
    }

    // Validate gender
    if (!in_array($gender, ['1', '2', '3'])) {
        $response['success'] = false;
        $response['message'] = 'Please select a valid gender option.';
        echo json_encode($response);
        exit();
    }

    // Password validation
    if (strlen($password) < 6) {
        $response['success'] = false;
        $response['message'] = 'Password must be at least 6 characters long.';
        echo json_encode($response);
        exit();
    }

    if (strlen($password) > 255) {
        $response['success'] = false;
        $response['message'] = 'Password is too long.';
        echo json_encode($response);
        exit();
    }

    // Check password strength
    if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)/', $password)) {
        $response['success'] = false;
        $response['message'] = 'Password must contain at least one letter and one number.';
        echo json_encode($response);
        exit();
    }

    // Confirm password match
    if ($password !== $confirmPassword) {
        $response['success'] = false;
        $response['message'] = 'Passwords do not match.';
        echo json_encode($response);
        exit();
    }

    // Check if email already exists
    $emailQuery = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
    $existingUser = Database::getSingleRow($emailQuery, [$email], 's');
    
    if ($existingUser) {
        $response['success'] = false;
        $response['message'] = 'An account with this email address already exists.';
        echo json_encode($response);
        exit();
    }

    // Check if mobile number already exists
    $mobileQuery = "SELECT user_id FROM users WHERE mobile = ? LIMIT 1";
    $existingMobile = Database::getSingleRow($mobileQuery, [$cleanMobile], 's');
    
    if ($existingMobile) {
        $response['success'] = false;
        $response['message'] = 'An account with this mobile number already exists.';
        echo json_encode($response);
        exit();
    }

    // Hash password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare user data for insertion (6 parameters only)
    $userData = [
        $firstName,
        $lastName,
        $email,
        $cleanMobile,
        $gender,
        $hashedPassword
    ];

    $userTypes = 'ssssss';

    // Begin transaction for data integrity
    Database::beginTransaction();

    try {
        // Insert user into database (immediately active)
        $insertQuery = "INSERT INTO users (
            first_name, 
            last_name, 
            email, 
            mobile, 
            gender_id, 
            password, 
            status, 
            user_type, 
            joined_date
        ) VALUES (?, ?, ?, ?, ?, ?, 'active', 'customer', NOW())";

        $stmt = Database::prepare($insertQuery, $userData, $userTypes);
        
        if (!$stmt || !$stmt->execute()) {
            throw new Exception("Failed to create user account");
        }

        // Get the newly created user ID
        $userId = Database::getLastInsertId();

        if (!$userId) {
            throw new Exception("Failed to retrieve user ID");
        }

        // Commit transaction
        Database::commit();

        // Store user data in session for immediate login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_first_name'] = $firstName;
        $_SESSION['user_last_name'] = $lastName;
        $_SESSION['user_type'] = 'customer';
        $_SESSION['user_mobile'] = $cleanMobile;
        $_SESSION['user_profile_image'] = null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        $response['success'] = true;
        $response['message'] = 'Account created successfully! Welcome to eShop!';
        $response['user_id'] = $userId;
        $response['redirect'] = 'home.php'; // Redirect to home page
        $response['user'] = array(
            'user_id' => $userId,
            'name' => $firstName . ' ' . $lastName,
            'email' => $email,
            'user_type' => 'customer'
        );

        echo json_encode($response);

    } catch (Exception $e) {
        // Rollback transaction on error
        Database::rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Log error for debugging
    error_log("Registration error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    $response['success'] = false;
    $response['message'] = 'Registration failed: ' . $e->getMessage(); // Show actual error for debugging
    echo json_encode($response);
}

// Close database connection
Database::closeConnection();
?>