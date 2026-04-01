<?php

session_start();
header('Content-Type: application/json');
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to cancel order']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$order_id = $_POST['order_id'];
$user_id = $_SESSION['user_id'];

try {
    Database::beginTransaction();
    
    // Get order details to verify user ownership and current status
    $order_items = Database::getMultipleRows(
        "SELECT o.*, p.title FROM orders o 
         INNER JOIN products p ON o.product_id = p.product_id
         WHERE o.order_id = ? AND o.user_id = ?",
        [$order_id, $user_id],
        'si'
    );
    
    if (empty($order_items)) {
        throw new Exception('Order not found');
    }
    
    // Check if order can be cancelled
    $first_order = $order_items[0];
    if (!in_array($first_order['order_status'], ['pending', 'confirmed'])) {
        throw new Exception('Order cannot be cancelled at this stage');
    }
    
    // Update order status to cancelled
    $update_query = "UPDATE orders SET order_status = 'cancelled', updated_date = NOW() 
                     WHERE order_id = ? AND user_id = ?";
    
    $success = executeUpdate($update_query, [$order_id, $user_id]);
    
    if (!$success) {
        throw new Exception('Failed to cancel order');
    }
    
    // Restore product quantities
    foreach ($order_items as $item) {
        $restore_qty = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
        executeUpdate($restore_qty, [$item['quantity'], $item['product_id']]);
    }
    
    Database::commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);
    
} catch (Exception $e) {
    Database::rollback();
    error_log("Cancel order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

Database::closeConnection();
?>