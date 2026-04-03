<?php
// checkoutCartProcess.php - Process entire cart for checkout
session_start();
header('Content-Type: application/json');
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to checkout']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get all cart items with product details
    $cart_query = "SELECT c.*, p.title, p.price, p.quantity as stock_quantity, p.user_id as seller_id,
                          p.delivery_cost_colombo, p.delivery_cost_other,
                          u.first_name as seller_first_name, u.last_name as seller_last_name
                   FROM cart c
                   INNER JOIN products p ON c.product_id = p.product_id
                   INNER JOIN users u ON p.user_id = u.user_id
                   WHERE c.user_id = ? AND p.status = 'active'
                   ORDER BY c.created_date ASC";
    
    $cart_items = Database::getMultipleRows($cart_query, [$user_id], 'i');
    
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit();
    }
    
    // Validate all items and calculate totals
    $subtotal = 0;
    $total_delivery = 0;
    $total_items = 0;
    $validation_errors = [];
    
    foreach ($cart_items as $item) {
        // Check if user is trying to buy their own product
        if ($item['seller_id'] == $user_id) {
            $validation_errors[] = "Cannot purchase your own product: " . $item['title'];
            continue;
        }
        
        // Check stock availability
        if ($item['quantity'] > $item['stock_quantity']) {
            $validation_errors[] = "Insufficient stock for " . $item['title'] . ". Available: " . $item['stock_quantity'];
            continue;
        }
        
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $total_items += $item['quantity'];
        
        // Calculate delivery (assuming Colombo for now - should be based on user address)
        $total_delivery += $item['delivery_cost_colombo'];
    }
    
    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cart validation failed',
            'errors' => $validation_errors
        ]);
        exit();
    }
    
    $grand_total = $subtotal + $total_delivery;
    
    // Get user details
    $user = Database::getSingleRow(
        "SELECT * FROM users WHERE user_id = ?",
        [$user_id],
        'i'
    );
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Get user address if available
    $address = Database::getSingleRow(
        "SELECT ua.*, c.city_name, d.district_name 
         FROM user_addresses ua
         LEFT JOIN cities c ON ua.city_id = c.city_id
         LEFT JOIN districts d ON c.district_id = d.district_id
         WHERE ua.user_id = ? AND ua.is_primary = 1",
        [$user_id],
        'i'
    );
    
    // Generate unique order ID
    $order_id = 'CART' . time() . rand(1000, 9999);
    
    // Prepare payment gateway data (simplified PayHere integration)
    $merchant_id = "1224051"; // Replace with actual merchant ID
    $merchant_secret = "MzYxNzE4ODc1OTM5MzYxMTM2MzExNTA2MjQ1M"; // Replace with actual secret
    $currency = "LKR";
    
    // Create hash for PayHere
    $hash = strtoupper(
        md5(
            $merchant_id . 
            $order_id . 
            number_format($grand_total, 2, '.', '') . 
            $currency .  
            strtoupper(md5($merchant_secret)) 
        ) 
    );
    
    // Store pending cart order in session
    $_SESSION['pending_cart_order'] = [
        'order_id' => $order_id,
        'user_id' => $user_id,
        'items' => $cart_items,
        'subtotal' => $subtotal,
        'delivery_cost' => $total_delivery,
        'total' => $grand_total,
        'total_items' => $total_items,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Prepare order data for payment
    $order_data = [
        'order_id' => $order_id,
        'merchant_id' => $merchant_id,
        'merchant_secret' => $merchant_secret,
        'currency' => $currency,
        'hash' => $hash,
        'amount' => $grand_total,
        'item_name' => 'Cart Items (' . count($cart_items) . ' products)',
        'quantity' => $total_items,
        'delivery_cost' => $total_delivery,
        'subtotal' => $subtotal,
        
        // Customer details
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'phone' => $user['mobile'],
        'address' => $address ? $address['address_line_1'] . ', ' . $address['address_line_2'] : '',
        'city' => $address ? $address['city_name'] : 'Colombo',
        'country' => 'Sri Lanka'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart checkout prepared successfully',
        'order_data' => $order_data,
        'cart_summary' => [
            'total_items' => count($cart_items),
            'total_quantity' => $total_items,
            'subtotal' => $subtotal,
            'delivery_cost' => $total_delivery,
            'grand_total' => $grand_total
        ],
        'redirect_url' => 'payment.php?order_id=' . $order_id . '&type=cart'
    ]);
    
} catch (Exception $e) {
    error_log("Checkout cart process error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing checkout']);
}

Database::closeConnection();
?>