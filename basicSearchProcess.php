<?php
// basicSearchProcess.php - Handle basic search functionality
require_once "connection.php";

// Get search parameters
$search_text = isset($_POST["t"]) ? trim($_POST["t"]) : "";
$category_id = isset($_POST["s"]) ? (int)$_POST["s"] : 0;
$page = isset($_POST["page"]) ? max(1, (int)$_POST["page"]) : 1;

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
if (!empty($search_text) && $category_id == 0) {
    $query .= " AND p.title LIKE ?";
    $params[] = "%" . $search_text . "%";
    $types .= "s";
} else if (empty($search_text) && $category_id != 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
} else if (!empty($search_text) && $category_id != 0) {
    $query .= " AND p.title LIKE ? AND p.category_id = ?";
    $params[] = "%" . $search_text . "%";
    $params[] = $category_id;
    $types .= "si";
}

// Get total count for pagination
$count_query = str_replace("SELECT p.*, c.category_name, b.brand_name, cl.color_name, s.size_name, pc.condition_name, u.first_name, u.last_name, (SELECT image_path FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as primary_image", "SELECT COUNT(*) as total", $query);

$count_result = Database::getSingleRow($count_query, $params, $types);
$total_products = $count_result ? $count_result['total'] : 0;

// Pagination settings
$results_per_page = 6;
$total_pages = ceil($total_products / $results_per_page);
$offset = ($page - 1) * $results_per_page;

// Add pagination to main query
$query .= " ORDER BY p.created_date DESC LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;
$types .= "ii";

// Execute the search query
$products = Database::getMultipleRows($query, $params, $types);
?>

<div class="row">
    <div class="offset-lg-1 col-12 col-lg-10 text-center">
        <div class="row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="offset-lg-1 col-12 col-lg-3 mb-4">
                        <div class="card" style="width: 18rem; height: 100%;">
                            <?php if ($product['primary_image']): ?>
                                <img src="<?php echo htmlspecialchars($product['primary_image']); ?>" 
                                     class="card-img-top img-thumbnail mt-2" 
                                     style="height: 180px; object-fit: cover;" 
                                     alt="<?php echo htmlspecialchars($product['title']); ?>" />
                            <?php else: ?>
                                <div class="card-img-top mt-2 bg-light d-flex align-items-center justify-content-center" 
                                     style="height: 180px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body ms-0 m-0 text-center d-flex flex-column">
                                <h5 class="card-title fw-bold fs-6"><?php echo htmlspecialchars($product['title']); ?></h5>
                                
                                <?php if ($product['condition_name']): ?>
                                    <span class="badge rounded-pill text-bg-info mb-2"><?php echo htmlspecialchars($product['condition_name']); ?></span>
                                <?php endif; ?>
                                
                                <span class="card-text text-primary mb-2">Rs. <?php echo number_format($product['price'], 2); ?></span>

                                <?php if ($product['quantity'] > 0): ?>
                                    <span class="card-text text-warning fw-bold">In Stock</span>
                                    <span class="card-text text-success fw-bold mb-3"><?php echo $product['quantity']; ?> Items Available</span>
                                    
                                    <div class="mt-auto">
                                        <a href='singleProductView.php?id=<?php echo $product['product_id']; ?>' 
                                           class="col-12 btn btn-success mb-2">Buy Now</a>
                                        <button class="col-12 btn btn-dark mb-2" 
                                                onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                            <i class="bi bi-cart-plus-fill text-white fs-5"></i> Add to Cart
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="card-text text-danger fw-bold">Out Of Stock</span>
                                    <span class="card-text text-danger fw-bold mb-3">0 Items Available</span>
                                    
                                    <div class="mt-auto">
                                        <a href='#' class="col-12 btn btn-success disabled mb-2">Buy Now</a>
                                        <button class="col-12 btn btn-dark mb-2 disabled">
                                            <i class="bi bi-cart-plus-fill text-white fs-5"></i> Add to Cart
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <button class="col-12 btn btn-outline-light border border-primary" 
                                        onclick="toggleWishlist(<?php echo $product['product_id']; ?>)">
                                    <i class="bi bi-heart-fill text-danger fs-5"></i> Wishlist
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No products found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="offset-2 offset-lg-3 col-8 col-lg-6 text-center mb-3">
    <nav aria-label="Page navigation">
        <ul class="pagination pagination-lg justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="#" 
                   onclick="<?php echo $page > 1 ? 'basicSearch(' . ($page - 1) . ');' : '#'; ?>" 
                   aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($x = $start_page; $x <= $end_page; $x++):
            ?>
                <li class="page-item <?php echo $x == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="#" onclick="basicSearch(<?php echo $x; ?>);"><?php echo $x; ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="#" 
                   onclick="<?php echo $page < $total_pages ? 'basicSearch(' . ($page + 1) . ');' : '#'; ?>" 
                   aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<script>
// Add to cart function
function addToCart(productId) {
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
}

// Toggle wishlist function
function toggleWishlist(productId) {
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
}

// Show toast notification
function showToast(message, type) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
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
</script>