<?php
// addProductProcess.php - Handle product addition
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: addProduct.php");
    exit();
}

// Set JSON header for AJAX responses
header('Content-Type: application/json');

try {
    // Validate required fields
    $required_fields = ['title', 'category_id', 'condition_id', 'price', 'quantity', 'agree_terms'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }
    
    // Sanitize and validate input data
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)$_POST['category_id'];
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $color_id = !empty($_POST['color_id']) ? (int)$_POST['color_id'] : null;
    $size_id = !empty($_POST['size_id']) ? (int)$_POST['size_id'] : null;
    $condition_id = (int)$_POST['condition_id'];
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $delivery_cost_colombo = (float)($_POST['delivery_cost_colombo'] ?? 0);
    $delivery_cost_other = (float)($_POST['delivery_cost_other'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if (strlen($title) < 10) {
        throw new Exception("Product title must be at least 10 characters long");
    }
    
    if ($price <= 0) {
        throw new Exception("Price must be greater than 0");
    }
    
    if ($quantity <= 0) {
        throw new Exception("Quantity must be greater than 0");
    }
    
    // Verify category exists
    $category_check = Database::getSingleRow(
        "SELECT category_id FROM categories WHERE category_id = ? AND status = 'active'",
        [$category_id],
        'i'
    );
    
    if (!$category_check) {
        throw new Exception("Invalid category selected");
    }
    
    // Verify condition exists
    $condition_check = Database::getSingleRow(
        "SELECT condition_id FROM product_conditions WHERE condition_id = ? AND status = 'active'",
        [$condition_id],
        'i'
    );
    
    if (!$condition_check) {
        throw new Exception("Invalid condition selected");
    }
    
    // Start transaction
    Database::beginTransaction();
    
    // Insert product
    $insert_query = "INSERT INTO products (
        title, description, price, quantity, category_id, brand_id, 
        color_id, size_id, condition_id, delivery_cost_colombo, 
        delivery_cost_other, user_id, status, created_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
    
    $params = [
        $title, $description, $price, $quantity, $category_id, $brand_id,
        $color_id, $size_id, $condition_id, $delivery_cost_colombo,
        $delivery_cost_other, $user_id
    ];
    
    $types = 'ssdiiiiiiddi';
    
    $stmt = Database::prepare($insert_query, $params, $types);
    
    if (!$stmt || !$stmt->execute()) {
        throw new Exception("Failed to insert product");
    }
    
    $product_id = Database::getLastInsertId();
    
    // Handle image uploads
    $upload_errors = [];
    $uploaded_images = [];
    
    if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
        $upload_dir = 'uploads/products/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        $max_images = 5;
        
        $file_count = min(count($_FILES['product_images']['name']), $max_images);
        
        for ($i = 0; $i < $file_count; $i++) {
            $file_name = $_FILES['product_images']['name'][$i];
            $file_tmp = $_FILES['product_images']['tmp_name'][$i];
            $file_size = $_FILES['product_images']['size'][$i];
            $file_error = $_FILES['product_images']['error'][$i];
            $file_type = $_FILES['product_images']['type'][$i];
            
            // Skip empty files
            if ($file_error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            // Check for upload errors
            if ($file_error !== UPLOAD_ERR_OK) {
                $upload_errors[] = "Error uploading file: $file_name";
                continue;
            }
            
            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                $upload_errors[] = "Invalid file type for: $file_name";
                continue;
            }
            
            // Validate file size
            if ($file_size > $max_file_size) {
                $upload_errors[] = "File too large: $file_name (max 5MB)";
                continue;
            }
            
            // Validate actual image
            $image_info = getimagesize($file_tmp);
            if ($image_info === false) {
                $upload_errors[] = "Invalid image file: $file_name";
                continue;
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = $product_id . '_' . time() . '_' . $i . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Insert image record
                $image_query = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)";
                $is_primary = ($i === 0) ? 1 : 0; // First image is primary
                
                $image_stmt = Database::prepare($image_query, [$product_id, $upload_path, $is_primary], 'isi');
                
                if ($image_stmt && $image_stmt->execute()) {
                    $uploaded_images[] = $upload_path;
                } else {
                    $upload_errors[] = "Failed to save image record for: $file_name";
                    // Delete the uploaded file if database insert failed
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $upload_errors[] = "Failed to upload file: $file_name";
            }
        }
    }
    
    // Commit transaction
    Database::commit();
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Product added successfully!',
        'product_id' => $product_id,
        'uploaded_images' => count($uploaded_images),
        'redirect' => 'myProducts.php'
    ];
    
    // Add warnings if there were upload errors
    if (!empty($upload_errors)) {
        $response['warnings'] = $upload_errors;
        $response['message'] .= ' However, some images could not be uploaded.';
    }
    
    // Log successful product addition
    error_log("Product added successfully: ID $product_id by User ID $user_id");
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    Database::rollback();
    
    // Log error
    error_log("Product addition error: " . $e->getMessage());
    
    // Return error response
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    echo json_encode($response);
}

// Close database connection
Database::closeConnection();