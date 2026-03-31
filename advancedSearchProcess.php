<?php
// advancedSearchProcess.php - Handle advanced search functionality
require_once "connection.php";

// Get search parameters
$search_text = isset($_POST["t"]) ? trim($_POST["t"]) : "";
$category_id = isset($_POST["cat"]) ? (int)$_POST["cat"] : 0;
$brand_id = isset($_POST["b"]) ? (int)$_POST["b"] : 0;
$model_id = isset($_POST["m"]) ? (int)$_POST["m"] : 0;
$condition_id = isset($_POST["con"]) ? (int)$_POST["con"] : 0;
$color_id = isset($_POST["col"]) ? (int)$_POST["col"] : 0;
$price_from = isset($_POST["pf"]) && $_POST["pf"] !== "" ? (float)$_POST["pf"] : null;
$price_to = isset($_POST["pt"]) && $_POST["pt"] !== "" ? (float)$_POST["pt"] : null;
$sort_option = isset($_POST["s"]) ? (int)$_POST["s"] : 0;
$page = isset($_POST["page"]) ? max(0, (int)$_POST["page"]) : 0;

// Build the base query
$query = "SELECT p.*, 
                 c.category_name, 
                 b.brand_name, 
                 cl.color_name, 
                 s.size_name, 
                 pc.condition_name,
                 u.first_name, 
                 u.last_name,
                 (SELECT image_path FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as primary_image
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN brands b ON p.brand_id = b.brand_id
          LEFT JOIN colors cl ON p.color_id = cl.color_id
          LEFT JOIN sizes s ON p.size_id = s.size_id
          LEFT JOIN product_conditions pc ON p.condition_id = pc.condition_id
          LEFT JOIN users u ON p.user_id = u.user_id
          WHERE p.status = 'active'";

$params = [];
$types = "";

// Add search conditions
if (!empty($search_text)) {
    $query .= " AND p.title LIKE ?";
    $params[] = "%" . $search_text . "%";
    $types .= "s";
}

if ($category_id != 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($brand_id != 0) {
    $query .= " AND p.brand_id = ?";
    $params[] = $brand_id;
    $types .= "i";
}

if ($model_id != 0) {
    $query .= " AND p.model_id = ?";
    $params[] = $model_id;
    $types .= "i";
}

if ($condition_id != 0) {
    $query .= " AND p.condition_id = ?";
    $params[] = $condition_id;
    $types .= "i";
}

if ($color_id != 0) {
    $query .= " AND p.color_id = ?";
    $params[] = $color_id;
    $types .= "i";
}

// Price range filters
if ($price_from !== null && $price_to !== null) {
    $query .= " AND p.price BETWEEN ? AND ?";
    $params[] = $price_from;
    $params[] = $price_to;
    $types .= "dd";
} else if ($price_from !== null) {
    $query .= " AND p.price >= ?";
    $params[] = $price_from;
    $types .= "d";
} else if ($price_to !== null) {
    $query .= " AND p.price <= ?";
    $params[] = $price_to;
    $types .= "d";
}

// Add sorting
switch ($sort_option) {
    case 1: // Price low to high
        $query .= " ORDER BY p.price ASC";
        break;
    case 2: // Price high to low
        $query .= " ORDER BY p.price DESC";
        break;
    case 3: // Quantity low to high
        $query .= " ORDER BY p.quantity ASC";
        break;
    case 4: // Quantity high to low
        $query .= " ORDER BY p.quantity DESC";
        break;
    case 5: // Newest first
        $query .= " ORDER BY p.created_date DESC";
        break;
    case 6: // Oldest first
        $query .= " ORDER BY p.created_date ASC";
        break;
    default: // Default sorting
        $query .= " ORDER BY p.created_date DESC";
        break;
}

// Build count query separately to avoid issues
$count_query = "SELECT COUNT(*) as total 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN colors cl ON p.color_id = cl.color_id
                LEFT JOIN sizes s ON p.size_id = s.size_id
                LEFT JOIN product_conditions pc ON p.condition_id = pc.condition_id
                LEFT JOIN users u ON p.user_id = u.user_id
                WHERE p.status = 'active'";

// Add the same conditions to count query
if (!empty($search_text)) {
    $count_query .= " AND p.title LIKE ?";
}

if ($category_id != 0) {
    $count_query .= " AND p.category_id = ?";
}

if ($brand_id != 0) {
    $count_query .= " AND p.brand_id = ?";
}

if ($model_id != 0) {
    $count_query .= " AND p.model_id = ?";
}

if ($condition_id != 0) {
    $count_query .= " AND p.condition_id = ?";
}

if ($color_id != 0) {
    $count_query .= " AND p.color_id = ?";
}

// Price range filters for count query
if ($price_from !== null && $price_to !== null) {
    $count_query .= " AND p.price BETWEEN ? AND ?";
} else if ($price_from !== null) {
    $count_query .= " AND p.price >= ?";
} else if ($price_to !== null) {
    $count_query .= " AND p.price <= ?";
}

try {
    $count_result = Database::getSingleRow($count_query, $params, $types);
    $total_products = ($count_result && isset($count_result['total'])) ? (int)$count_result['total'] : 0;
} catch (Exception $e) {
    error_log("Count query error: " . $e->getMessage());
    $total_products = 0;
}

// Pagination settings
$results_per_page = 6;
$total_pages = ceil($total_products / $results_per_page);
$offset = $page * $results_per_page;

// Add pagination to main query
$query .= " LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;
$types .= "ii";

// Execute the search query
$products = Database::getMultipleRows($query, $params, $types);
?>

<?php if (!empty($products)): ?>
    <div class="row">
        <div class="col-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Search Results</h5>
                <span class="badge bg-primary"><?php echo number_format($total_products); ?> products found</span>
            </div>
            <hr>
        </div>
    </div>
    
    <div class="row">
        <?php foreach ($products as $product): ?>
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card product-card h-100">
                    <?php if ($product['primary_image']): ?>
                        <img src="<?php echo htmlspecialchars($product['primary_image']); ?>" 
                             class="card-img-top product-image" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>" />
                    <?php else: ?>
                        <div class="product-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold"><?php echo htmlspecialchars($product['title']); ?></h6>
                        
                        <div class="mb-2">
                            <?php if ($product['condition_name']): ?>
                                <span class="badge bg-info me-1"><?php echo htmlspecialchars($product['condition_name']); ?></span>
                            <?php endif; ?>
                            <?php if ($product['category_name']): ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">
                                <?php if ($product['brand_name']): ?>
                                    Brand: <?php echo htmlspecialchars($product['brand_name']); ?>
                                <?php endif; ?>
                                <?php if ($product['size_name']): ?>
                                    <?php echo $product['brand_name'] ? ' • ' : ''; ?>Size: <?php echo htmlspecialchars($product['size_name']); ?>
                                <?php endif; ?>
                                <?php if ($product['color_name']): ?>
                                    <?php echo ($product['brand_name'] || $product['size_name']) ? ' • ' : ''; ?>Color: <?php echo htmlspecialchars($product['color_name']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <p class="card-text text-primary fw-bold fs-5 mb-2">Rs. <?php echo number_format($product['price'], 2); ?></p>

                        <div class="mb-3">
                            <?php if ($product['quantity'] > 0): ?>
                                <span class="text-success fw-bold">In Stock</span>
                                <small class="text-muted d-block"><?php echo $product['quantity']; ?> items available</small>
                            <?php else: ?>
                                <span class="text-danger fw-bold">Out of Stock</span>
                                <small class="text-muted d-block">0 items available</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-auto">
                            <?php if ($product['quantity'] > 0): ?>
                                <div class="d-grid gap-2">
                                    <a href='singleProductView.php?id=<?php echo $product['product_id']; ?>' 
                                       class="btn btn-success">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <div class="row g-1">
                                        <div class="col-8">
                                            <button class="btn btn-primary w-100" 
                                                    onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button class="btn btn-outline-danger w-100" 
                                                    onclick="toggleWishlist(<?php echo $product['product_id']; ?>)"
                                                    title="Add to Wishlist">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="d-grid gap-2">
                                    <a href='singleProductView.php?id=<?php echo $product['product_id']; ?>' 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <button class="btn btn-secondary disabled">
                                        <i class="fas fa-cart-plus me-1"></i>Out of Stock
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="row">
            <div class="col-12">
                <nav aria-label="Search results pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 0 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="#" 
                               onclick="<?php echo $page > 0 ? 'advancedSearch(' . ($page - 1) . ');' : '#'; ?>" 
                               aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php
                        $start_page = max(0, $page - 2);
                        $end_page = min($total_pages - 1, $page + 2);
                        
                        // Show first page if we're not starting from 0
                        if ($start_page > 0):
                        ?>
                            <li class="page-item">
                                <a class="page-link" href="#" onclick="advancedSearch(0);">1</a>
                            </li>
                            <?php if ($start_page > 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($x = $start_page; $x <= $end_page; $x++): ?>
                            <li class="page-item <?php echo $x == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="#" onclick="advancedSearch(<?php echo $x; ?>);">
                                    <?php echo $x + 1; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php
                        // Show last page if we're not ending at the last page
                        if ($end_page < $total_pages - 1):
                        ?>
                            <?php if ($end_page < $total_pages - 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="#" onclick="advancedSearch(<?php echo $total_pages - 1; ?>);">
                                    <?php echo $total_pages; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?php echo $page >= $total_pages - 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="#" 
                               onclick="<?php echo $page < $total_pages - 1 ? 'advancedSearch(' . ($page + 1) . ');' : '#'; ?>" 
                               aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="row">
        <div class="col-12 text-center py-5">
            <i class="fas fa-search-minus fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No products found</h4>
            <p class="text-muted">Try adjusting your search criteria or removing some filters</p>
            
            <div class="mt-4">
                <button class="btn btn-outline-primary" onclick="clearSearch()">
                    <i class="fas fa-times me-2"></i>Clear All Filters
                </button>
                <a href="home.php" class="btn btn-primary ms-2">
                    <i class="fas fa-home me-2"></i>Browse All Products
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Add to cart function
function addToCart(productId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        fetch('addToCartProcess.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Product added to cart!', 'success');
                updateCartCount();
            } else {
                showToast(data.message || 'Error adding to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error adding to cart', 'error');
        });
    <?php else: ?>
        showToast('Please log in to add items to cart', 'warning');
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 2000);
    <?php endif; ?>
}

// Toggle wishlist function
function toggleWishlist(productId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        fetch('toggleWishlistProcess.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                updateWishlistCount();
            } else {
                showToast(data.message || 'Error updating wishlist', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error updating wishlist', 'error');
        });
    <?php else: ?>
        showToast('Please log in to add items to wishlist', 'warning');
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 2000);
    <?php endif; ?>
}

// Show toast notification
function showToast(message, type) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Create toast container if it doesn't exist
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1050';
    document.body.appendChild(container);
    return container;
}

// Update cart count
function updateCartCount() {
    fetch('getCartCount.php')
        .then(response => response.json())
        .then(data => {
            const cartCountElement = document.getElementById('cartCount');
            if (cartCountElement && data.success) {
                cartCountElement.textContent = data.count || 0;
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Update wishlist count
function updateWishlistCount() {
    fetch('getWishlistCount.php')
        .then(response => response.json())
        .then(data => {
            const wishlistCountElement = document.getElementById('wishlistCount');
            if (wishlistCountElement && data.success) {
                wishlistCountElement.textContent = data.count || 0;
            }
        })
        .catch(error => console.error('Error updating wishlist count:', error));
}
</script>