<head>
    <link rel="icon" href="resource/basket.png" />  
    </head>

<?php

session_start();
require_once 'connection.php';

// Set page variables
$pageTitle = "Shopping Cart - ClothesStore";
$requireLogin = true;

// Additional CSS for this page
$additionalCSS = '
<style>
    .cart-container {
        padding: 2rem 0;
        min-height: 80vh;
    }

    .cart-header {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .cart-content {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .cart-item {
        border-bottom: 1px solid #e9ecef;
        padding: 1.5rem 0;
        margin-bottom: 1rem;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .product-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }

    .product-placeholder {
        width: 120px;
        height: 120px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 2rem;
    }

    .quantity-selector {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        background: white;
    }

    .quantity-btn {
        background: none;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        color: var(--primary-color);
        transition: background-color 0.3s;
    }

    .quantity-btn:hover {
        background: #f8f9fa;
    }

    .quantity-btn:disabled {
        color: #6c757d;
        cursor: not-allowed;
    }

    .quantity-input {
        border: none;
        text-align: center;
        width: 60px;
        padding: 8px;
        font-weight: 600;
        background: transparent;
    }

    .cart-summary {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 2rem;
        position: sticky;
        top: 100px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    .summary-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.2rem;
        margin-bottom: 0;
    }

    .empty-cart {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-cart-icon {
        font-size: 5rem;
        color: #6c757d;
        margin-bottom: 2rem;
    }

    .btn-remove {
        background: none;
        border: 1px solid var(--secondary-color);
        color: var(--secondary-color);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.875rem;
        transition: all 0.3s;
    }

    .btn-remove:hover {
        background: var(--secondary-color);
        color: white;
    }

    .product-title {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .product-meta {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }

    .product-price {
        font-weight: bold;
        color: var(--secondary-color);
        font-size: 1.1rem;
    }

    .delivery-cost {
        font-size: 0.875rem;
        color: var(--success-color);
    }

    .checkout-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    @media (max-width: 768px) {
        .product-image, .product-placeholder {
            width: 80px;
            height: 80px;
        }
        
        .cart-content {
            padding: 1rem;
        }
        
        .cart-summary {
            position: static;
            margin-top: 2rem;
        }
        
        .quantity-selector {
            margin-top: 1rem;
        }
    }
</style>
';

require_once 'header.php';

// Get user's cart items with FIXED query
$user_id = $_SESSION['user_id'];

// Fixed cart query - removed the problematic created_date ordering and simplified JOINs
$cart_query = "SELECT c.cart_id, c.user_id, c.product_id, c.quantity,
                      p.title, p.price, p.quantity as stock_quantity, 
                      p.delivery_cost_colombo, p.delivery_cost_other,
                      u.first_name, u.last_name,
                      (SELECT image_path FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as primary_image,
                      cat.category_name, 
                      pc.condition_name
               FROM cart c
               INNER JOIN products p ON c.product_id = p.product_id
               INNER JOIN users u ON p.user_id = u.user_id
               LEFT JOIN categories cat ON p.category_id = cat.category_id
               LEFT JOIN product_conditions pc ON p.condition_id = pc.condition_id
               WHERE c.user_id = ? AND p.status = 'active'
               ORDER BY c.cart_id DESC";

$cart_items = Database::getMultipleRows($cart_query, [$user_id], 'i');

// Calculate totals
$subtotal = 0;
$total_delivery = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    $total_items += $item['quantity'];
    
    // For delivery calculation, assume Colombo delivery for now
    // In a real app, you'd get user's address
    $total_delivery += $item['delivery_cost_colombo'];
}

$grand_total = $subtotal + $total_delivery;
?>

<div class="cart-container">
    <div class="container">
        <!-- Cart Header -->
        <div class="cart-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h2>
                    <p class="text-muted mb-0"><?php echo count($cart_items); ?> items in your cart</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="advancedSearch.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($cart_items)): ?>
            <!-- Empty Cart -->
            <div class="cart-content">
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Looks like you haven't added any items to your cart yet.</p>
                    <a href="advancedSearch.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="cart-content">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" id="cart-item-<?php echo $item['cart_id']; ?>">
                                <div class="row align-items-center">
                                    <!-- Product Image -->
                                    <div class="col-md-2 col-3">
                                        <?php if ($item['primary_image']): ?>
                                            <img src="<?php echo htmlspecialchars($item['primary_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Product Details -->
                                    <div class="col-md-4 col-9">
                                        <h6 class="product-title"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <div class="product-meta">
                                            <?php if ($item['category_name']): ?>
                                                Category: <?php echo htmlspecialchars($item['category_name']); ?>
                                            <?php endif; ?>
                                            <?php if ($item['condition_name']): ?>
                                                <?php echo $item['category_name'] ? ' • ' : ''; ?>
                                                Condition: <?php echo htmlspecialchars($item['condition_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-meta">
                                            Seller: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                        </div>
                                        <div class="delivery-cost">
                                            <small><i class="fas fa-truck me-1"></i>Delivery: Rs. <?php echo number_format($item['delivery_cost_colombo'], 2); ?></small>
                                        </div>
                                    </div>

                                    <!-- Quantity Selector -->
                                    <div class="col-md-2 col-6 text-center">
                                        <div class="quantity-selector">
                                            <button type="button" class="quantity-btn" 
                                                    onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>, <?php echo $item['stock_quantity']; ?>)"
                                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                   id="qty-<?php echo $item['cart_id']; ?>"
                                                   onchange="updateQuantity(<?php echo $item['cart_id']; ?>, this.value, <?php echo $item['stock_quantity']; ?>)">
                                            <button type="button" class="quantity-btn" 
                                                    onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>, <?php echo $item['stock_quantity']; ?>)"
                                                    <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <?php if ($item['quantity'] >= $item['stock_quantity']): ?>
                                            <div class="text-warning mt-1">
                                                <small>Max available</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Price -->
                                    <div class="col-md-2 col-6 text-center">
                                        <div class="product-price">Rs. <?php echo number_format($item['price'], 2); ?></div>
                                        <div class="text-muted">
                                            <small>each</small>
                                        </div>
                                    </div>

                                    <!-- Total & Actions -->
                                    <div class="col-md-2 col-12 text-center">
                                        <div class="product-price mb-2">
                                            Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                        <button type="button" class="btn btn-remove" 
                                                onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                            <i class="fas fa-trash me-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h5 class="mb-3">Order Summary</h5>
                        
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $total_items; ?> items):</span>
                            <span>Rs. <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Delivery Charges:</span>
                            <span>Rs. <?php echo number_format($total_delivery, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Total:</span>
                            <span>Rs. <?php echo number_format($grand_total, 2); ?></span>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-primary btn-lg" onclick="proceedToCheckout()">
                                <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                            </button>
                            <button class="btn btn-outline-secondary" onclick="clearCart()">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </button>
                        </div>
                        
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6><i class="fas fa-shield-alt me-2"></i>Secure Checkout</h6>
                            <small class="text-muted">Your payment information is protected by SSL encryption</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Additional JavaScript for this page
$additionalJS = '
<script>
    // Update quantity function
    function updateQuantity(cartId, newQuantity, maxQuantity) {
        newQuantity = parseInt(newQuantity);
        
        if (newQuantity < 1) {
            newQuantity = 1;
        } else if (newQuantity > maxQuantity) {
            newQuantity = maxQuantity;
            showToast("Maximum available quantity reached", "warning");
        }
        
        // Update input field
        document.getElementById("qty-" + cartId).value = newQuantity;
        
        fetch("cartQtyUpdateProcess.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "cart_id=" + cartId + "&quantity=" + newQuantity
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || "Quantity updated successfully", "success");
                // Reload page to update totals
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(data.message || "Error updating quantity", "error");
                // Revert to original value
                location.reload();
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("Error updating quantity", "error");
            location.reload();
        });
    }
    
    // Remove from cart function
    function removeFromCart(cartId) {
        if (!confirm("Are you sure you want to remove this item from your cart?")) {
            return;
        }
        
        fetch("deleteFromCartProcess.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "cart_id=" + cartId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || "Item removed from cart", "success");
                updateCartCount();
                
                // Remove item with animation
                const cartItem = document.getElementById("cart-item-" + cartId);
                if (cartItem) {
                    cartItem.style.transition = "opacity 0.3s, transform 0.3s";
                    cartItem.style.opacity = "0";
                    cartItem.style.transform = "translateX(-100%)";
                    
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            } else {
                showToast(data.message || "Error removing item", "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("Error removing item", "error");
        });
    }
    
    // Clear entire cart
    function clearCart() {
        if (!confirm("Are you sure you want to clear your entire cart?")) {
            return;
        }
        
        fetch("clearCartProcess.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast("Cart cleared successfully", "success");
                updateCartCount();
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast(data.message || "Error clearing cart", "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("Error clearing cart", "error");
        });
    }
    
    // Proceed to checkout
    function proceedToCheckout() {
        window.location.href = "checkout.php";
    }
</script>
';

require_once 'footer.php';
?>