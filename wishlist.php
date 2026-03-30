<head>
    <link rel="icon" href="resource/basket.png" />  
    </head>

<?php
// wishlist.php - User's wishlist page
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to access your wishlist.";
    header("Location: index.php");
    exit();
}

// Get current user information
$user = Database::getSingleRow("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']], 'i');

if (!$user) {
    $_SESSION['error_message'] = "User account not found.";
    header("Location: index.php");
    exit();
}

// Pagination settings
$results_per_page = 12;
$current_page = isset($_GET["page"]) ? max(1, (int)$_GET["page"]) : 1;
$offset = ($current_page - 1) * $results_per_page;

// Search functionality
$search_term = "";
$search_params = [$_SESSION['user_id']];
$search_types = "i";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $search_query = " AND (p.title LIKE ? OR p.description LIKE ?)";
    $search_params[] = "%$search_term%";
    $search_params[] = "%$search_term%";
    $search_types .= "ss";
} else {
    $search_query = "";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM wishlist w
                INNER JOIN products p ON w.product_id = p.product_id
                WHERE w.user_id = ? AND p.status = 'active'" . $search_query;

$total_count = Database::getSingleRow($count_query, $search_params, $search_types);
$total_products = $total_count ? $total_count['total'] : 0;
$total_pages = ceil($total_products / $results_per_page);

// Get wishlist products with pagination
$wishlist_query = "SELECT w.wishlist_id, w.user_id, w.product_id, w.added_date,
                          p.*, 
                          c.category_name, 
                          b.brand_name, 
                          cl.color_name, 
                          s.size_name, 
                          pc.condition_name,
                          u.first_name, 
                          u.last_name,
                          (SELECT image_path FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as primary_image
                   FROM wishlist w
                   INNER JOIN products p ON w.product_id = p.product_id
                   LEFT JOIN categories c ON p.category_id = c.category_id
                   LEFT JOIN brands b ON p.brand_id = b.brand_id
                   LEFT JOIN colors cl ON p.color_id = cl.color_id
                   LEFT JOIN sizes s ON p.size_id = s.size_id
                   LEFT JOIN product_conditions pc ON p.condition_id = pc.condition_id
                   LEFT JOIN users u ON p.user_id = u.user_id
                   WHERE w.user_id = ? AND p.status = 'active'" . $search_query . "
                   ORDER BY w.added_date DESC
                   LIMIT ? OFFSET ?";

$final_params = array_merge($search_params, [$results_per_page, $offset]);
$final_types = $search_types . "ii";

$wishlist_products = Database::getMultipleRows($wishlist_query, $final_params, $final_types);

// Set page variables BEFORE including header
$pageTitle = "My Wishlist - ClothesStore";
$requireLogin = true;

// Additional CSS for this page
$additionalCSS = '
<style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #e74c3c;
        --accent-color: #f39c12;
        --success-color: #27ae60;
        --warning-color: #f1c40f;
        --info-color: #3498db;
        --light-bg: #ecf0f1;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    .wishlist-container {
        padding: 2rem 0;
        min-height: 80vh;
    }

    .page-header {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-title {
        color: var(--primary-color);
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .wishlist-controls {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .wishlist-grid {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    /* EQUAL HEIGHT PRODUCT CARDS */
    .wishlist-card {
        background: white;
        border: none;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s;
        margin-bottom: 0;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .wishlist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .wishlist-image {
        height: 220px;
        object-fit: cover;
        width: 100%;
        flex-shrink: 0;
    }

    .wishlist-image-placeholder {
        height: 220px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 3rem;
        flex-shrink: 0;
        border-bottom: 1px solid #e9ecef;
    }

    .wishlist-card-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        min-height: 0;
    }

    .wishlist-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 1rem;
        height: 2.8em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        line-height: 1.4;
    }

    .wishlist-price {
        font-size: 1.25rem;
        font-weight: bold;
        color: var(--success-color);
        margin-bottom: 1rem;
        flex-shrink: 0;
    }

    .wishlist-meta {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 1rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .wishlist-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid #f8f9fa;
        flex-shrink: 0;
    }

    .btn-action {
        flex: 1;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
        text-align: center;
        min-width: 80px;
    }

    .btn-add-cart {
        background: var(--primary-color);
        color: white;
    }

    .btn-add-cart:hover {
        background: #1a252f;
        color: white;
    }

    .btn-view {
        background: var(--info-color);
        color: white;
    }

    .btn-view:hover {
        background: #2980b9;
        color: white;
    }

    .btn-remove {
        background: var(--secondary-color);
        color: white;
    }

    .btn-remove:hover {
        background: #c0392b;
        color: white;
    }

    .remove-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(231, 76, 60, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        cursor: pointer;
        z-index: 10;
    }

    .remove-btn:hover {
        background: rgba(192, 57, 43, 1);
        transform: scale(1.1);
    }

    .stock-status {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        z-index: 10;
    }

    .stock-in {
        background: rgba(39, 174, 96, 0.9);
        color: white;
    }

    .stock-out {
        background: rgba(231, 76, 60, 0.9);
        color: white;
    }

    .empty-wishlist {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
    }

    .empty-wishlist i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .btn-browse {
        background: linear-gradient(45deg, var(--accent-color), #e67e22);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
    }

    .btn-browse:hover {
        background: linear-gradient(45deg, #e67e22, var(--accent-color));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
        color: white;
    }

    .btn-clear-all {
        background: var(--secondary-color);
        border: none;
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-clear-all:hover {
        background: #c0392b;
        transform: translateY(-1px);
        color: white;
    }

    .pagination-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .wishlist-stats {
        background: linear-gradient(45deg, var(--primary-color), #34495e);
        color: white;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .added-date {
        font-size: 0.75rem;
        color: #6c757d;
        text-align: right;
        margin-top: 0.5rem;
    }

    /* RESPONSIVE IMPROVEMENTS */
    @media (max-width: 768px) {
        .wishlist-actions {
            flex-direction: column;
        }
        
        .btn-action {
            margin-bottom: 0.5rem;
            flex: none;
        }

        .wishlist-image, .wishlist-image-placeholder {
            height: 180px;
        }

        .wishlist-title {
            font-size: 1rem;
        }

        .remove-btn {
            width: 30px;
            height: 30px;
        }
    }

    @media (max-width: 576px) {
        .wishlist-image, .wishlist-image-placeholder {
            height: 160px;
        }
    }
</style>
';

// Now include header AFTER all variables are set and redirects are done
require_once 'header.php';
?>

<head>
    <link rel="icon" href="resource/basket.png" />
</head>

<div class="wishlist-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-heart me-2"></i>My Wishlist
            </h1>
            <p class="text-muted mb-0">Save items you love for later</p>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Wishlist Controls -->
        <div class="wishlist-controls">
            <div class="row align-items-center mb-3">
                <div class="col-md-6">
                    <div class="wishlist-stats">
                        <h6 class="mb-0">
                            <i class="fas fa-heart me-2"></i>
                            <?php echo number_format($total_products); ?> items in your wishlist
                        </h6>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php if ($total_products > 0): ?>
                        <button class="btn btn-clear-all" onclick="clearAllWishlist()">
                            <i class="fas fa-trash me-2"></i>Clear All
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Search Form -->
            <form method="GET" action="">
                <div class="row align-items-end">
                    <div class="col-lg-10 col-md-8 mb-3">
                        <label for="search" class="form-label">Search Wishlist</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search your wishlist items..."
                               value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                    </div>
                </div>
                <?php if (!empty($search_term)): ?>
                    <div class="mt-2">
                        <a href="wishlist.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear Search
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Wishlist Grid -->
        <div class="wishlist-grid">
            <?php if (!empty($wishlist_products)): ?>
                <div class="row align-items-center mb-3">
                    <div class="col">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Your Wishlist Items
                        </h5>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $results_per_page, $total_products); ?> 
                            of <?php echo $total_products; ?> items
                        </small>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($wishlist_products as $product): ?>
                        <div class="col">
                            <div class="card wishlist-card h-100">
                                <!-- Stock Status Badge -->
                                <div class="stock-status <?php echo $product['quantity'] > 0 ? 'stock-in' : 'stock-out'; ?>">
                                    <?php echo $product['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                </div>

                                <!-- Remove Button -->
                                <button class="remove-btn" onclick="removeFromWishlist(<?php echo $product['product_id']; ?>)" 
                                        title="Remove from wishlist">
                                    <i class="fas fa-times"></i>
                                </button>

                                <!-- Product Image -->
                                <?php if ($product['primary_image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['primary_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                         class="wishlist-image">
                                <?php else: ?>
                                    <div class="wishlist-image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="wishlist-card-body">
                                    <h6 class="wishlist-title"><?php echo htmlspecialchars($product['title']); ?></h6>
                                    
                                    <div class="wishlist-price">Rs. <?php echo number_format($product['price'], 2); ?></div>
                                    
                                    <div class="wishlist-meta">
                                        <div class="mb-2">
                                            <?php if ($product['category_name']): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['category_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                            
                                            <?php if ($product['condition_name']): ?>
                                                <small class="text-muted ms-2">
                                                    <i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars($product['condition_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <?php if ($product['brand_name']): ?>
                                                <small class="text-muted">Brand: <?php echo htmlspecialchars($product['brand_name']); ?></small>
                                            <?php endif; ?>
                                            
                                            <?php if ($product['size_name']): ?>
                                                <small class="text-muted <?php echo $product['brand_name'] ? 'ms-2' : ''; ?>">
                                                    Size: <?php echo htmlspecialchars($product['size_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <small class="text-muted">
                                            Seller: <?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?>
                                        </small>
                                    </div>

                                    <div class="wishlist-actions">
                                        <?php if ($product['quantity'] > 0): ?>
                                            <button class="btn btn-action btn-add-cart" 
                                                    onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                                    title="Add to Cart">
                                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-action btn-add-cart disabled" disabled title="Out of Stock">
                                                <i class="fas fa-times me-1"></i>Out of Stock
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="singleProductView.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-action btn-view" title="View Product" target="_blank">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </div>

                                    <div class="added-date">
                                        Added on <?php echo date('M j, Y', strtotime($product['added_date'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-wishlist">
                    <i class="fas fa-heart-broken"></i>
                    <h4>Your wishlist is empty</h4>
                    <p class="mb-4">
                        <?php if (!empty($search_term)): ?>
                            No items match your search criteria in your wishlist.
                        <?php else: ?>
                            Start adding items to your wishlist by clicking the heart icon on products you love!
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!empty($search_term)): ?>
                        <a href="wishlist.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-times me-1"></i>Clear Search
                        </a>
                    <?php endif; ?>
                    
                    <a href="home.php" class="btn btn-browse">
                        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Wishlist pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>

                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                    <?php echo $total_pages; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clear All Confirmation Modal -->
<div class="modal fade" id="clearAllModal" tabindex="-1" aria-labelledby="clearAllModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="clearAllModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Clear Entire Wishlist
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove all items from your wishlist?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-warning me-2"></i>
                    <strong>Warning:</strong> This action will remove all <?php echo number_format($total_products); ?> items from your wishlist and cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmClearAll()">
                    <i class="fas fa-trash me-2"></i>Clear All Items
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Additional JavaScript for this page
$additionalJS = '
<script>
    // Remove single item from wishlist
    function removeFromWishlist(productId) {
        if (!confirm("Remove this item from your wishlist?")) return;

        fetch("removeWishlistProcess.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || "Item removed from wishlist!", "success");
                updateWishlistCount();
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || "Error removing item from wishlist", "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("Error removing item from wishlist", "error");
        });
    }

    // Clear all wishlist items
    function clearAllWishlist() {
        const modal = new bootstrap.Modal(document.getElementById("clearAllModal"));
        modal.show();
    }

    function confirmClearAll() {
        const clearBtn = document.querySelector("#clearAllModal .btn-warning");
        const originalText = clearBtn.innerHTML;
        clearBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i>Clearing...";
        clearBtn.disabled = true;

        fetch("clearWishlistProcess.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
        })
        .then(response => response.json())
        .then(data => {
            clearBtn.innerHTML = originalText;
            clearBtn.disabled = false;

            if (data.success) {
                showToast(data.message || "Wishlist cleared successfully!", "success");
                bootstrap.Modal.getInstance(document.getElementById("clearAllModal")).hide();
                updateWishlistCount();
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message || "Error clearing wishlist", "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            clearBtn.innerHTML = originalText;
            clearBtn.disabled = false;
            showToast("Error clearing wishlist", "error");
        });
    }

    // Add to cart from wishlist
    function addToCart(productId) {
        fetch("addToCartProcess.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || "Product added to cart!", "success");
                updateCartCount();
            } else {
                showToast(data.message || "Error adding to cart", "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("Error adding to cart", "error");
        });
    }

    // Show toast notification
    function showToast(message, type) {
        const toastContainer = document.getElementById("toastContainer") || createToastContainer();
        
        const toast = document.createElement("div");
        toast.className = `toast align-items-center text-white bg-${type === "success" ? "success" : type === "error" ? "danger" : type === "warning" ? "warning" : "info"} border-0`;
        toast.setAttribute("role", "alert");
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === "success" ? "check-circle" : type === "error" ? "exclamation-circle" : type === "warning" ? "exclamation-triangle" : "info-circle"} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener("hidden.bs.toast", () => {
            toast.remove();
        });
    }

    // Create toast container if it does not exist
    function createToastContainer() {
        const container = document.createElement("div");
        container.id = "toastContainer";
        container.className = "toast-container position-fixed bottom-0 end-0 p-3";
        container.style.zIndex = "1050";
        document.body.appendChild(container);
        return container;
    }

    // Update cart count
    function updateCartCount() {
        fetch("getCartCount.php")
            .then(response => response.json())
            .then(data => {
                const cartCountElement = document.getElementById("cartCount");
                if (cartCountElement && data.success) {
                    cartCountElement.textContent = data.count || 0;
                }
            })
            .catch(error => console.error("Error updating cart count:", error));
    }

    // Update wishlist count
    function updateWishlistCount() {
        fetch("getWishlistCount.php")
            .then(response => response.json())
            .then(data => {
                const wishlistCountElement = document.getElementById("wishlistCount");
                if (wishlistCountElement && data.success) {
                    wishlistCountElement.textContent = data.count || 0;
                }
            })
            .catch(error => console.error("Error updating wishlist count:", error));
    }

    // Initialize page
    function initializePage() {
        // Add hover effects to cards
        document.querySelectorAll(".wishlist-card").forEach(card => {
            card.addEventListener("mouseenter", function() {
                this.style.transform = "translateY(-5px)";
            });
            
            card.addEventListener("mouseleave", function() {
                this.style.transform = "translateY(0)";
            });
        });

        // Auto-refresh wishlist count every 30 seconds
        setInterval(updateWishlistCount, 30000);
    }

    // Initialize page when DOM is loaded
    document.addEventListener("DOMContentLoaded", initializePage);
</script>
';

require_once 'footer.php';