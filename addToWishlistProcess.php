<?php
// addToWishlistProcess.php - Handle adding items to wishlist
session_start();
header('Content-Type: application/json');
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add items to your wishlist'
    ]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['product_id']) || empty($input['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit();
}

$product_id = (int)$input['product_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if product exists and is active
    $product_check = Database::getSingleRow(
        "SELECT product_id, title, user_id FROM products WHERE product_id = ? AND status = 'active'",
        [$product_id],
        'i'
    );

    if (!$product_check) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found or not available'
        ]);
        exit();
    }

    // Check if user is trying to add their own product
    if ($product_check['user_id'] == $user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'You cannot add your own product to wishlist'
        ]);
        exit();
    }

    // Check if product is already in wishlist
    $existing_item = Database::getSingleRow(
        "SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$user_id, $product_id],
        'ii'
    );

    if ($existing_item) {
        echo json_encode([
            'success' => false,
            'message' => 'Product is already in your wishlist'
        ]);
        exit();
    }

    // Add to wishlist
    $add_query = "INSERT INTO wishlist (user_id, product_id, added_date) VALUES (?, ?, NOW())";
    $stmt = Database::prepare($add_query, [$user_id, $product_id], 'ii');

    if ($stmt && $stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product added to wishlist successfully!',
            'action' => 'added'
        ]);
    } else {
        throw new Exception('Failed to add product to wishlist');
    }

} catch (Exception $e) {
    error_log("Add to wishlist error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding to wishlist. Please try again.'
    ]);
}

Database::closeConnection();
?>