<?php

session_start();
header('Content-Type: application/json');
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to update cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$cart_id = (int)$_POST['cart_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

try {
    // Verify cart item belongs to current user and get product info
    $cart_item = Database::getSingleRow(
        "SELECT c.cart_id, c.quantity, p.product_id, p.title, p.quantity as stock_quantity, p.user_id as seller_id
         FROM cart c
         INNER JOIN products p ON c.product_id = p.product_id
         WHERE c.cart_id = ? AND c.user_id = ? AND p.status = 'active'",
        [$cart_id, $user_id],
        'ii'
    );
    
    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }
    
    // Check if requested quantity exceeds available stock
    if ($quantity > $cart_item['stock_quantity']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Requested quantity (' . $quantity . ') exceeds available stock (' . $cart_item['stock_quantity'] . ')'
        ]);
        exit();
    }
    
    // Update cart quantity
    $update_query = "UPDATE cart SET quantity = ?, updated_date = NOW() WHERE cart_id = ? AND user_id = ?";
    $success = executeUpdate($update_query, [$quantity, $cart_id, $user_id]);
    
    if ($success) {
        // Log the update for audit purposes
        error_log("Cart quantity updated: User ID {$user_id}, Cart ID {$cart_id}, New Quantity {$quantity}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cart quantity updated successfully',
            'new_quantity' => $quantity,
            'cart_id' => $cart_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart quantity']);
    }
    
} catch (Exception $e) {
    error_log("Cart quantity update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating cart']);
}

Database::closeConnection();
?>