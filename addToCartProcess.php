<?php

session_start();
header('Content-Type: application/json');
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['product_id']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$product_id = (int)$input['product_id'];
$quantity = (int)$input['quantity'];
$user_id = $_SESSION['user_id'];

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

try {
    // Check if product exists and is active
    $product = Database::getSingleRow(
        "SELECT product_id, title, price, quantity, user_id FROM products WHERE product_id = ? AND status = 'active'",
        [$product_id],
        'i'
    );
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
        exit();
    }
    
    // Check if user is trying to add their own product
    if ($product['user_id'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot add your own product to cart']);
        exit();
    }
    
    // Check if enough stock is available
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit();
    }
    
    // Check if item already exists in cart
    $existing_cart = Database::getSingleRow(
        "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
        [$user_id, $product_id],
        'ii'
    );
    
    if ($existing_cart) {
        // Update existing cart item
        $new_quantity = $existing_cart['quantity'] + $quantity;
        
        if ($new_quantity > $product['quantity']) {
            echo json_encode(['success' => false, 'message' => 'Total quantity exceeds available stock']);
            exit();
        }
        
        // Use Database::iud instead of executeUpdate for compatibility
        $update_success = Database::iud("UPDATE cart SET quantity = " . $new_quantity . " WHERE cart_id = " . $existing_cart['cart_id']);
        
        if ($update_success !== false) {
            echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
        }
    } else {
        // Add new cart item - use current timestamp format that matches your cart table
        $insert_success = Database::iud("INSERT INTO cart (user_id, product_id, quantity) VALUES (" . $user_id . ", " . $product_id . ", " . $quantity . ")");
        
        if ($insert_success !== false) {
            echo json_encode(['success' => true, 'message' => 'Product added to cart successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
        }
    }
    
} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding to cart']);
}

Database::closeConnection();
?>