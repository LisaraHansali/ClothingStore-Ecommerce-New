<?php
// clearWishlistProcess.php - Handle clearing entire wishlist
session_start();
header('Content-Type: application/json');
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to manage your wishlist'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get count of items to be removed for logging
    $count_query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
    $count_result = Database::getSingleRow($count_query, [$user_id], 'i');
    $items_count = $count_result ? $count_result['count'] : 0;

    if ($items_count == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Your wishlist is already empty'
        ]);
        exit();
    }

    // Clear entire wishlist
    $clear_query = "DELETE FROM wishlist WHERE user_id = ?";
    $stmt = Database::prepare($clear_query, [$user_id], 'i');

    if ($stmt && $stmt->execute()) {
        $affected_rows = Database::getAffectedRows();
        
        if ($affected_rows > 0) {
            // Log the activity (optional)
            $activity_log = "INSERT INTO user_activities (user_id, activity_type, activity_description, created_date) 
                             VALUES (?, 'wishlist_clear', ?, NOW())";
            Database::prepare($activity_log, [
                $user_id, 
                "Cleared entire wishlist ({$affected_rows} items removed)"
            ], 'is');

            echo json_encode([
                'success' => true,
                'message' => "Successfully removed {$affected_rows} items from your wishlist!",
                'items_removed' => $affected_rows
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No items found to remove from wishlist'
            ]);
        }
    } else {
        throw new Exception('Failed to clear wishlist');
    }

} catch (Exception $e) {
    error_log("Clear wishlist error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while clearing your wishlist. Please try again.'
    ]);
}

Database::closeConnection();
?>