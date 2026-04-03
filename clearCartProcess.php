<?php
// clearCartProcess.php - Clear entire cart for current user
session_start();
header('Content-Type: application/json');
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to clear cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get count of items before clearing for logging
    $count_result = Database::getSingleRow(
        "SELECT COUNT(*) as count FROM cart WHERE user_id = ?",
        [$user_id],
        'i'
    );
    
    $items_count = $count_result ? $count_result['count'] : 0;
    
    if ($items_count == 0) {
        echo json_encode(['success' => true, 'message' => 'Cart is already empty']);
        exit();
    }
    
    // Clear all cart items for the user
    $delete_query = "DELETE FROM cart WHERE user_id = ?";
    $success = executeUpdate($delete_query, [$user_id]);
    
    if ($success) {
        // Log the action for audit purposes
        error_log("Cart cleared: User ID {$user_id}, {$items_count} items removed");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cart cleared successfully',
            'items_removed' => $items_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
    }
    
} catch (Exception $e) {
    error_log("Clear cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while clearing cart']);
}

Database::closeConnection();
?>