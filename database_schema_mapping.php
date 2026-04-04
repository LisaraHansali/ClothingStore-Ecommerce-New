<?php


// =============================================================================
// 1. UPDATED manageOrders.php queries for your database
// =============================================================================

// Count query - UPDATED
$count_query = "
    SELECT COUNT(*) as total 
    FROM invoices i 
    JOIN users u ON i.user_email = u.email 
    WHERE $where_clause
";

// Main orders query - UPDATED
$orders_query = "
    SELECT i.invoice_id, i.user_email, i.total as total_amount, 
           CASE i.status 
               WHEN 0 THEN 'pending'
               WHEN 1 THEN 'confirmed' 
               WHEN 2 THEN 'processing'
               WHEN 3 THEN 'shipped'
               WHEN 4 THEN 'delivered'
               ELSE 'cancelled'
           END as status,
           i.date as created_date, i.date as updated_date,
           u.fname as first_name, u.lname as last_name, u.email, u.mobile,
           COUNT(ii.id) as item_count
    FROM invoices i
    JOIN users u ON i.user_email = u.email
    LEFT JOIN invoice_items ii ON i.invoice_id = ii.invoice_id
    WHERE $where_clause
    GROUP BY i.invoice_id
    ORDER BY i.date DESC 
    LIMIT ? OFFSET ?
";

// Update order status - UPDATED  
$update_query = "UPDATE invoices SET status = ? WHERE invoice_id = ?";

// =============================================================================
// 2. UPDATED getOrderDetails.php queries
// =============================================================================

// Get order details - UPDATED
$order_query = "
    SELECT i.*, 
           u.fname as first_name, u.lname as last_name, u.email, u.mobile,
           CONCAT(u.fname, ' ', u.lname) as customer_name
    FROM invoices i
    JOIN users u ON i.user_email = u.email
    WHERE i.invoice_id = ?
";

// Get order items - UPDATED
$order_items_query = "
    SELECT ii.*, p.title, p.price as product_price,
           (SELECT path FROM product_images pi WHERE pi.product_id = p.id LIMIT 1) as product_image
    FROM invoice_items ii
    JOIN products p ON ii.product_id = p.id
    WHERE ii.invoice_id = ?
    ORDER BY ii.id
";

// =============================================================================
// 3. UPDATED manageUsers.php queries  
// =============================================================================

// Users query with profile image - UPDATED
$users_query = "
    SELECT u.id as user_id, u.fname as first_name, u.lname as last_name, 
           u.email, u.mobile, u.status, u.joined_date, u.last_login,
           (SELECT path FROM user_profile_images upi WHERE upi.user_email = u.email LIMIT 1) as profile_image
    FROM users u 
    WHERE u.user_type = 'customer' AND $where_clause
    ORDER BY u.joined_date DESC 
    LIMIT ? OFFSET ?
";

// Update user status - UPDATED
$update_query = "UPDATE users SET status = ? WHERE id = ?";

// =============================================================================
// 4. UPDATED salesReports.php queries
// =============================================================================

// Sales overview - UPDATED
$sales_query = "
    SELECT COUNT(*) as total_orders, COALESCE(SUM(i.total), 0) as total_revenue 
    FROM invoices i 
    WHERE i.status IN (3, 4) -- Assuming 3=shipped, 4=delivered
    " . ($where_date_condition ? "AND " . $where_date_condition : "");

// Top products - UPDATED
$products_query = "
    SELECT p.id as product_id, p.title, 
           SUM(ii.qty) as total_sold,
           SUM(ii.qty * p.price) as total_revenue,
           COUNT(DISTINCT ii.invoice_id) as order_count
    FROM invoice_items ii
    JOIN products p ON ii.product_id = p.id
    JOIN invoices i ON ii.invoice_id = i.invoice_id
    WHERE i.status IN (3, 4)
    " . ($where_date_condition ? "AND " . $where_date_condition : "") . "
    GROUP BY p.id, p.title
    ORDER BY total_sold DESC
    LIMIT 10
";

// Top customers - UPDATED  
$customers_query = "
    SELECT u.id as user_id, u.fname as first_name, u.lname as last_name, u.email,
           COUNT(i.invoice_id) as total_orders,
           COALESCE(SUM(i.total), 0) as total_spent
    FROM invoices i
    JOIN users u ON i.user_email = u.email
    WHERE i.status IN (3, 4)
    " . ($where_date_condition ? "AND " . $where_date_condition : "") . "
    GROUP BY u.id, u.fname, u.lname, u.email
    ORDER BY total_spent DESC
    LIMIT 10
";

// =============================================================================
// 5. UPDATED purchasingHistory.php queries
// =============================================================================

// Get user's invoices - UPDATED
$orders_query = "
    SELECT i.invoice_id, i.total as total_amount, 
           CASE i.status 
               WHEN 0 THEN 'pending'
               WHEN 1 THEN 'confirmed'
               WHEN 2 THEN 'processing' 
               WHEN 3 THEN 'shipped'
               WHEN 4 THEN 'delivered'
               ELSE 'cancelled'
           END as status,
           i.date as created_date, i.date as updated_date,
           COUNT(ii.id) as item_count
    FROM invoices i
    LEFT JOIN invoice_items ii ON i.invoice_id = ii.invoice_id
    WHERE i.user_email = (SELECT email FROM users WHERE id = ?)
    GROUP BY i.invoice_id
    ORDER BY i.date DESC
    LIMIT ? OFFSET ?
";

// Get invoice items - UPDATED
$items_query = "
    SELECT ii.*, p.title, p.price as product_price,
           u.fname as seller_first_name, u.lname as seller_last_name,
           (SELECT path FROM product_images pi WHERE pi.product_id = p.id LIMIT 1) as product_image
    FROM invoice_items ii
    JOIN products p ON ii.product_id = p.id
    JOIN users u ON p.user_email = u.email
    WHERE ii.invoice_id = ?
    ORDER BY ii.id
";

// =============================================================================
// 6. UPDATED sellingHistory.php queries
// =============================================================================

// This file should work mostly as-is since it's for admin viewing all invoices
// Just update table names and column references as shown above

// =============================================================================
// STATUS MAPPING
// =============================================================================



// Status conversion function you can use:
function convertStatusToText($status_id) {
    $status_map = [
        0 => 'pending',
        1 => 'confirmed', 
        2 => 'processing',
        3 => 'shipped',
        4 => 'delivered',
        5 => 'cancelled'
    ];
    return $status_map[$status_id] ?? 'unknown';
}

function convertStatusToId($status_text) {
    $status_map = [
        'pending' => 0,
        'confirmed' => 1,
        'processing' => 2, 
        'shipped' => 3,
        'delivered' => 4,
        'cancelled' => 5
    ];
    return $status_map[$status_text] ?? 0;
}




?>

