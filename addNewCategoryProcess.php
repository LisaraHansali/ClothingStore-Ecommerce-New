<?php
session_start();
require_once 'connection.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

try {
    // Check if admin is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        throw new Exception("Access denied. Admin privileges required.");
    }

    // Check if form was submitted via POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    // Validate required fields
    if (!isset($_POST["email"]) || !isset($_POST["name"])) {
        throw new Exception("Missing required fields.");
    }

    $category_name = trim($_POST["name"]);
    $admin_email = trim($_POST["email"]);

    // Basic validation
    if (empty($category_name)) {
        throw new Exception("Category name cannot be empty.");
    }

    if (empty($admin_email)) {
        throw new Exception("Admin email cannot be empty.");
    }

    // Validate email format
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    // Check if the logged-in admin's email matches
    $current_admin = Database::getSingleRow(
        "SELECT email FROM users WHERE user_id = ? AND user_type = 'admin'",
        [$_SESSION['user_id']],
        'i'
    );

    if (!$current_admin || $current_admin['email'] !== $admin_email) {
        throw new Exception("Invalid admin credentials.");
    }

    // Check if category already exists
    $existing_category = Database::getSingleRow(
        "SELECT category_id FROM categories WHERE category_name = ? AND status = 'active'",
        [$category_name],
        's'
    );

    if ($existing_category) {
        throw new Exception("This category already exists.");
    }

    // Generate verification code
    $verification_code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);

    // Store verification code in session for later verification
    $_SESSION['category_verification'] = [
        'code' => $verification_code,
        'category_name' => $category_name,
        'admin_email' => $admin_email,
        'timestamp' => time()
    ];

    // In a real application, you would send email here
    // For now, we'll simulate email sending and return success with the code
    // This is for development purposes only - remove in production
    
    /*
    // Email sending code (uncomment and configure for production)
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php';

    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';
        $mail->Password   = 'your-app-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@clothesstore.com', 'ClothesStore Admin');
        $mail->addAddress($admin_email);

        $mail->isHTML(true);
        $mail->Subject = 'Category Addition Verification Code';
        $mail->Body    = "
            <h2>Category Addition Verification</h2>
            <p>Your verification code for adding the category '<strong>$category_name</strong>' is:</p>
            <h3 style='color: #e74c3c; font-size: 24px; text-align: center; background: #f8f9fa; padding: 20px; border-radius: 5px;'>$verification_code</h3>
            <p>This code will expire in 10 minutes.</p>
            <p>If you did not request this, please ignore this email.</p>
        ";

        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent to your email address.',
            'show_verification' => true
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Failed to send verification email: " . $mail->ErrorInfo);
    }
    */

    // Development response (remove in production)
    echo json_encode([
        'success' => true,
        'message' => 'Verification code generated successfully.',
        'show_verification' => true,
        'dev_code' => $verification_code // Remove this in production
    ]);

} catch (Exception $e) {
    error_log("Add category error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

Database::closeConnection();
?>