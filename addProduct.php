<head>
    <link rel="icon" href="resource/basket.png" />  
    </head>

<?php

session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$pageTitle = "Add Product - ClothesStore";

// Get categories, brands, colors, sizes, conditions using the Database class
$categories = Database::getMultipleRows("SELECT * FROM categories WHERE status = 'active' ORDER BY category_name");
$brands = Database::getMultipleRows("SELECT * FROM brands WHERE status = 'active' ORDER BY brand_name");
$colors = Database::getMultipleRows("SELECT * FROM colors WHERE status = 'active' ORDER BY color_name");
$sizes = Database::getMultipleRows("SELECT * FROM sizes WHERE status = 'active' ORDER BY size_order");
$conditions = Database::getMultipleRows("SELECT * FROM product_conditions WHERE status = 'active' ORDER BY condition_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #f39c12;
            --success-color: #27ae60;
            --info-color: #3498db;
            --warning-color: #f1c40f;
            --light-gray: #ecf0f1;
            --dark-gray: #95a5a6;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .add-product-container {
            padding: 2rem 0;
            min-height: 70vh;
        }
        
        .product-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }
        
        .form-control, .form-select, .form-check-input {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
        }
        
        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .image-upload-area:hover {
            border-color: var(--accent-color);
            background: #fff3cd;
        }
        
        .image-upload-area.dragover {
            border-color: var(--secondary-color);
            background: #f8d7da;
        }
        
        .preview-container {
            display: none;
            margin-top: 1rem;
        }
        
        .image-preview {
            display: inline-block;
            position: relative;
            margin: 0.5rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .image-preview img {
            width: 120px;
            height: 120px;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .price-input-group {
            position: relative;
        }
        
        .price-input-group .form-control {
            padding-left: 2.5rem;
        }
        
        .price-currency {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-weight: 600;
            z-index: 10;
        }
        
        .required-field::after {
            content: " *";
            color: var(--secondary-color);
        }
        
        .help-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .btn-custom {
            background: linear-gradient(45deg, var(--primary-color), var(--info-color));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-custom:hover {
            background: linear-gradient(45deg, var(--info-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: white;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
        }

        .nav-link:hover {
            color: white !important;
        }

        .main-badge {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: var(--success-color);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
        }
    </style>

    <link rel="icon" href="resource/basket.png" />
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>ClothesStore
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="myProducts.php"><i class="fas fa-box me-1"></i>My Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="add-product-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-plus-circle me-2"></i>Add New Product</h2>
                        <p class="text-muted">List your item and reach thousands of potential buyers</p>
                    </div>
                    
                    <form action="addProductProcess.php" method="POST" enctype="multipart/form-data" class="product-form" id="productForm">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Basic Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="title" class="form-label required-field">Product Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required maxlength="255"
                                           placeholder="Enter a descriptive title for your product">
                                    <div class="help-text">Be specific and include brand, size, color, and condition</div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"
                                              placeholder="Describe your product in detail - condition, measurements, materials, etc."></textarea>
                                    <div class="help-text">Include any defects, measurements, or special features</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category & Details -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-tags"></i>
                                Category & Details
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label required-field">Category</label>
                                    <select class="form-select" id="category" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="brand" class="form-label">Brand</label>
                                    <select class="form-select" id="brand" name="brand_id">
                                        <option value="">Select Brand</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo htmlspecialchars($brand['brand_id']); ?>">
                                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="size" class="form-label">Size</label>
                                    <select class="form-select" id="size" name="size_id">
                                        <option value="">Select Size</option>
                                        <?php foreach ($sizes as $size): ?>
                                            <option value="<?php echo htmlspecialchars($size['size_id']); ?>">
                                                <?php echo htmlspecialchars($size['size_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <select class="form-select" id="color" name="color_id">
                                        <option value="">Select Color</option>
                                        <?php foreach ($colors as $color): ?>
                                            <option value="<?php echo htmlspecialchars($color['color_id']); ?>">
                                                <?php echo htmlspecialchars($color['color_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="condition" class="form-label required-field">Condition</label>
                                    <select class="form-select" id="condition" name="condition_id" required>
                                        <option value="">Select Condition</option>
                                        <?php foreach ($conditions as $condition): ?>
                                            <option value="<?php echo htmlspecialchars($condition['condition_id']); ?>">
                                                <?php echo htmlspecialchars($condition['condition_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pricing & Quantity -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-dollar-sign"></i>
                                Pricing & Quantity
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label required-field">Price</label>
                                    <div class="price-input-group">
                                        <span class="price-currency">Rs.</span>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               step="0.01" min="0" required placeholder="0.00">
                                    </div>
                                    <div class="help-text">Set a competitive price for your item</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label required-field">Quantity Available</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" 
                                           min="1" required placeholder="1">
                                    <div class="help-text">How many items are you selling?</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delivery Costs -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-shipping-fast"></i>
                                Delivery Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="delivery_colombo" class="form-label">Delivery Cost (Colombo)</label>
                                    <div class="price-input-group">
                                        <span class="price-currency">Rs.</span>
                                        <input type="number" class="form-control" id="delivery_colombo" 
                                               name="delivery_cost_colombo" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    <div class="help-text">Delivery cost within Colombo district</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="delivery_other" class="form-label">Delivery Cost (Other Areas)</label>
                                    <div class="price-input-group">
                                        <span class="price-currency">Rs.</span>
                                        <input type="number" class="form-control" id="delivery_other" 
                                               name="delivery_cost_other" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    <div class="help-text">Delivery cost for other districts</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Images -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-images"></i>
                                Product Images
                            </h5>
                            
                            <div class="image-upload-area" onclick="document.getElementById('product-images').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h6>Click to upload images or drag and drop</h6>
                                <p class="text-muted mb-0">You can upload up to 5 images. First image will be the main image.</p>
                                <input type="file" id="product-images" name="product_images[]" multiple 
                                       accept="image/*" style="display: none;">
                            </div>
                            
                            <div class="preview-container" id="image-previews"></div>
                            <div class="help-text mt-2">
                                <strong>Tips:</strong> Use good lighting, show different angles, include close-ups of any defects
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="text-center">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" name="agree_terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree that the information provided is accurate and I have the right to sell this item
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <button type="submit" class="btn btn-custom btn-lg px-5">
                                    <i class="fas fa-plus-circle me-2"></i>Add Product
                                </button>
                                <a href="myProducts.php" class="btn btn-outline-secondary btn-lg px-4">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container" style="z-index: 11;"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        

        
    </script>
</body>
</html>