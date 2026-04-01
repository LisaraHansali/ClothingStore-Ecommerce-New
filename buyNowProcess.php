<?php
// buyNowProcess.php - Process buy now request
session_start();
header('Content-Type: application/json');
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to purchase items']);
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
    // Get product details
    $product = Database::getSingleRow(
        "SELECT p.*, u.first_name, u.last_name, u.email as seller_email, u.mobile as seller_mobile
         FROM products p
         INNER JOIN users u ON p.user_id = u.user_id
         WHERE p.product_id = ? AND p.status = 'active'",
        [$product_id],
        'i'
    );
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
        exit();
    }
    
    // Check if user is trying to buy their own product
    if ($product['user_id'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot purchase your own product']);
        exit();
    }
    
    // Check stock availability
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $product['quantity'] . ' items left']);
        exit();
    }
    
    // Get user details for order
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
    
    // Calculate pricing
    $item_price = $product['price'];
    $subtotal = $item_price * $quantity;
    
    // Determine delivery cost (assuming Colombo if no address)
    $delivery_cost = $product['delivery_cost_other']; // Default to other areas
    if ($address && $address['district_name'] === 'Colombo') {
        $delivery_cost = $product['delivery_cost_colombo'];
    }
    
    $total = $subtotal + $delivery_cost;
    
    // Generate unique order ID
    $order_id = 'ORD' . time() . rand(1000, 9999);
    
    // Prepare order data for payment gateway (simplified PayHere integration)
    $merchant_id = "1224051"; // Replace with actual merchant ID
    $merchant_secret = "MzYxNzE4ODc1OTM5MzYxMTM2MzExNTA2MjQ1M"; // Replace with actual secret
    $currency = "LKR";
    
    // Create hash for PayHere
    $hash = strtoupper(
        md5(
            $merchant_id . 
            $order_id . 
            number_format($total, 2, '.', '') . 
            $currency .  
            strtoupper(md5($merchant_secret)) 
        ) 
    );
    
    // Prepare response data
    $order_data = [
        'order_id' => $order_id,
        'merchant_id' => $merchant_id,
        'merchant_secret' => $merchant_secret,
        'currency' => $currency,
        'hash' => $hash,
        'amount' => $total,
        'item_name' => $product['title'],
        'item_number' => $product_id,
        'quantity' => $quantity,
        'delivery_cost' => $delivery_cost,
        'subtotal' => $subtotal,
        
        // Customer details
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'phone' => $user['mobile'],
        'address' => $address ? $address['address_line_1'] . ', ' . $address['address_line_2'] : '',
        'city' => $address ? $address['city_name'] : 'Colombo',
        'country' => 'Sri Lanka',
        
        // Product details
        'product_title' => $product['title'],
        'product_price' => $item_price,
        'seller_name' => $product['first_name'] . ' ' . $product['last_name'],
        'seller_email' => $product['seller_email']
    ];
    
    // Store pending order in session for processing after payment
    $_SESSION['pending_order'] = [
        'order_id' => $order_id,
        'user_id' => $user_id,
        'product_id' => $product_id,
        'quantity' => $quantity,
        'price' => $item_price,
        'delivery_cost' => $delivery_cost,
        'total' => $total,
        'seller_id' => $product['user_id'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Order prepared successfully',
        'order_data' => $order_data,
        'redirect_url' => 'payment.php?order_id=' . $order_id
    ]);
    
} catch (Exception $e) {
    error_log("Buy now process error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your order']);
}

Database::closeConnection();
?>