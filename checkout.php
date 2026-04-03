<head>
    <link rel="icon" href="resource/basket.png" />  
    </head>

<?php

session_start();
require_once 'connection.php';

// Set page variables
$pageTitle = "Checkout - ClothesStore";
$requireLogin = true;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to proceed with checkout.";
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user = Database::getSingleRow(
    "SELECT * FROM users WHERE user_id = ?",
    [$user_id],
    'i'
);

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: index.php");
    exit();
}

// Initialize checkout data
$checkout_items = [];
$checkout_type = '';
$subtotal = 0;
$total_delivery = 0;
$grand_total = 0;
$order_id = '';

// Check for pending orders in session or from cart
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    $checkout_type = isset($_GET['type']) ? $_GET['type'] : 'single';
    
    if ($checkout_type === 'cart' && isset($_SESSION['pending_cart_order'])) {
        $pending_order = $_SESSION['pending_cart_order'];
        $checkout_items = $pending_order['items'];
        $subtotal = $pending_order['subtotal'];
        $total_delivery = $pending_order['delivery_cost'];
        $grand_total = $pending_order['total'];
        $checkout_type = 'cart';
    } elseif (isset($_SESSION['pending_order'])) {
        $pending_order = $_SESSION['pending_order'];
        
        // Get product details for single item
        $product = Database::getSingleRow(
            "SELECT p.*, c.category_name, u.first_name, u.last_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.category_id
             LEFT JOIN users u ON p.user_id = u.user_id
             WHERE p.product_id = ?",
            [$pending_order['product_id']],
            'i'
        );
        
        if ($product) {
           $checkout_items = [[
    'product_id' => $product['product_id'],
    'title' => $product['title'],
    'price' => $pending_order['price'],
    'quantity' => $pending_order['quantity'],
    'delivery_cost_colombo' => $pending_order['delivery_cost'],
    'category_name' => $product['category_name'],
    'first_name' => $product['first_name'],
    'last_name' => $product['last_name']
]];
            
            $subtotal = $pending_order['price'] * $pending_order['quantity'];
            $total_delivery = $pending_order['delivery_cost'];
            $grand_total = $pending_order['total'];
        }
        $checkout_type = 'single';
    }
} else {
    // Load from cart if no order_id
    $cart_items = Database::getMultipleRows(
        "SELECT c.*, p.title, p.price, p.delivery_cost_colombo, p.delivery_cost_other,
                cat.category_name, u.first_name, u.last_name
         FROM cart c
         INNER JOIN products p ON c.product_id = p.product_id
         INNER JOIN users u ON p.user_id = u.user_id
         LEFT JOIN categories cat ON p.category_id = cat.category_id
         WHERE c.user_id = ? AND p.status = 'active'",
        [$user_id],
        'i'
    );
    
    if (!empty($cart_items)) {
        $checkout_items = $cart_items;
        foreach ($cart_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $total_delivery += $item['delivery_cost_colombo'];
        }
        $grand_total = $subtotal + $total_delivery;
        $checkout_type = 'cart';
    }
}

// If no items found, redirect to cart
if (empty($checkout_items)) {
    $_SESSION['error_message'] = "No items found for checkout.";
    header("Location: cart.php");
    exit();
}

// Get user's primary address
$user_address = Database::getSingleRow(
    "SELECT ua.*, c.city_name, d.district_name, p.province_name
     FROM user_addresses ua
     LEFT JOIN cities c ON ua.city_id = c.city_id  
     LEFT JOIN districts d ON c.district_id = d.district_id
     LEFT JOIN provinces p ON d.province_id = p.province_id
     WHERE ua.user_id = ? AND ua.is_primary = 1",
    [$user_id],
    'i'
);

// Get all user addresses for selection
$all_addresses = Database::getMultipleRows(
    "SELECT ua.*, c.city_name, d.district_name, p.province_name
     FROM user_addresses ua
     LEFT JOIN cities c ON ua.city_id = c.city_id
     LEFT JOIN districts d ON c.district_id = d.district_id  
     LEFT JOIN provinces p ON d.province_id = p.province_id
     WHERE ua.user_id = ?
     ORDER BY ua.is_primary DESC, ua.created_date DESC",
    [$user_id],
    'i'
);

// Additional CSS for this page
$additionalCSS = '
<style>
    .checkout-container {
        padding: 2rem 0;
        min-height: 80vh;
    }

    .checkout-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .section-title {
        color: var(--primary-color);
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--accent-color);
    }

    .checkout-item {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        background: #f8f9fa;
    }

    .product-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
    }

    .address-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 1rem;
    }

    .address-card:hover {
        border-color: var(--accent-color);
        background: rgba(243, 156, 18, 0.05);
    }

    .address-card.selected {
        border-color: var(--accent-color);
        background: rgba(243, 156, 18, 0.1);
    }

    .address-card input[type="radio"] {
        margin-right: 0.75rem;
    }

    .order-summary {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        position: sticky;
        top: 100px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }

    .summary-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 0;
    }

    .payment-methods {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
    }

    .payment-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }

    .payment-card:hover {
        border-color: var(--accent-color);
        background: rgba(243, 156, 18, 0.05);
    }

    .payment-card.selected {
        border-color: var(--accent-color);
        background: rgba(243, 156, 18, 0.1);
    }

    .payment-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
    }

    .btn-place-order {
        background: var(--secondary-color);
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1.1rem;
        width: 100%;
        transition: all 0.3s;
    }

    .btn-place-order:hover {
        background: #c0392b;
        transform: translateY(-2px);
        color: white;
    }

    .btn-place-order:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
    }

    .security-info {
        background: #e8f5e8;
        border-left: 4px solid #28a745;
        padding: 1rem;
        border-radius: 5px;
        margin: 1rem 0;
    }

    .form-control:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
    }

    .form-select:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
    }

    @media (max-width: 768px) {
        .checkout-card {
            padding: 1rem;
        }
        
        .product-info {
            flex-direction: column;
            text-align: center;
        }
        
        .payment-methods {
            grid-template-columns: 1fr;
        }
        
        .order-summary {
            position: static;
            margin-top: 2rem;
        }
    }
</style>
';

require_once 'header.php';
?>

<div class="checkout-container">
    <div class="container">
        <!-- Page Header -->
        <div class="checkout-card">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2><i class="fas fa-credit-card me-2"></i>Checkout</h2>
                    <p class="text-muted mb-0">Complete your order securely</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo $checkout_type === 'cart' ? 'cart.php' : 'advancedSearch.php'; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Forms -->
            <div class="col-lg-8">
                
                <!-- Order Items -->
                <div class="checkout-card">
                    <h4 class="section-title">
                        <i class="fas fa-box me-2"></i>Order Items (<?php echo count($checkout_items); ?> items)
                    </h4>
                    
                    <?php foreach ($checkout_items as $item): ?>
                        <div class="checkout-item">
                            <div class="product-info">
                                <div class="product-image">
                                    <i class="fas fa-tshirt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                    <small class="text-muted">
                                        <?php if (isset($item['category_name'])): ?>
                                            Category: <?php echo htmlspecialchars($item['category_name']); ?>
                                        <?php endif; ?>
                                        <?php if (isset($item['first_name'])): ?>
                                            • Seller: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                        <?php endif; ?>
                                    </small>
                                    <div class="row mt-2">
                                        <div class="col-4">
                                            <strong>Price: Rs. <?php echo number_format($item['price'], 2); ?></strong>
                                        </div>
                                        <div class="col-4">
                                            <strong>Qty: <?php echo $item['quantity']; ?></strong>
                                        </div>
                                        <div class="col-4">
                                            <strong>Total: Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Delivery Address -->
                <div class="checkout-card">
                    <h4 class="section-title">
                        <i class="fas fa-map-marker-alt me-2"></i>Delivery Address
                    </h4>
                    
                    <?php if (!empty($all_addresses)): ?>
                        <div id="saved-addresses">
                            <h6>Select from saved addresses:</h6>
                            <?php foreach ($all_addresses as $address): ?>
                                <div class="address-card <?php echo $address['is_primary'] ? 'selected' : ''; ?>" 
                                     onclick="selectAddress(<?php echo $address['address_id']; ?>)">
                                    <input type="radio" name="selected_address" value="<?php echo $address['address_id']; ?>" 
                                           <?php echo $address['is_primary'] ? 'checked' : ''; ?>>
                                    <strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong>
                                    <br>
                                    <?php echo htmlspecialchars($address['address_line_1']); ?>
                                    <?php if ($address['address_line_2']): ?>
                                        , <?php echo htmlspecialchars($address['address_line_2']); ?>
                                    <?php endif; ?>
                                    <br>
                                    <?php echo htmlspecialchars($address['city_name'] ?? 'Unknown City'); ?>, 
                                    <?php echo htmlspecialchars($address['district_name'] ?? 'Unknown District'); ?>
                                    <?php if ($address['postal_code']): ?>
                                        - <?php echo htmlspecialchars($address['postal_code']); ?>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">Mobile: <?php echo htmlspecialchars($address['mobile']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleNewAddressForm()">
                                <i class="fas fa-plus me-2"></i>Add New Address
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- New Address Form -->
                    <div id="new-address-form" style="display: <?php echo empty($all_addresses) ? 'block' : 'none'; ?>;">
                        <h6>Enter delivery address:</h6>
                        <form id="addressForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="addr_first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="addr_last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mobile *</label>
                                        <input type="tel" class="form-control" id="addr_mobile" 
                                               value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" id="addr_email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address Line 1 *</label>
                                <input type="text" class="form-control" id="addr_line_1" 
                                       placeholder="House number, street name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" id="addr_line_2" 
                                       placeholder="Apartment, suite, unit, building, floor, etc.">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">City *</label>
                                        <select class="form-select" id="addr_city" required>
                                            <option value="">Select City</option>
                                            <?php
                                            $cities = Database::getMultipleRows("SELECT * FROM cities ORDER BY city_name");
                                            foreach ($cities as $city):
                                            ?>
                                                <option value="<?php echo $city['city_id']; ?>">
                                                    <?php echo htmlspecialchars($city['city_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">District</label>
                                        <input type="text" class="form-control" id="addr_district" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="addr_postal" 
                                               placeholder="e.g. 10100">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="save_address" 
                                       <?php echo empty($all_addresses) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="save_address">
                                    Save this address for future orders
                                </label>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-card">
                    <h4 class="section-title">
                        <i class="fas fa-credit-card me-2"></i>Payment Method
                    </h4>
                    
                    <div class="payment-methods">
                        <div class="payment-card selected" onclick="selectPayment('payhere')">
                            <input type="radio" name="payment_method" value="payhere" checked style="display: none;">
                            <div class="payment-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h6>PayHere</h6>
                            <small>Credit/Debit Cards, Bank Transfer</small>
                        </div>
                        
                        <div class="payment-card" onclick="selectPayment('cod')">
                            <input type="radio" name="payment_method" value="cod" style="display: none;">
                            <div class="payment-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h6>Cash on Delivery</h6>
                            <small>Pay when you receive</small>
                        </div>
                    </div>
                    
                    <div class="security-info">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Secure Payment:</strong> Your payment information is protected by SSL encryption.
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Summary -->
            <div class="col-lg-4">
                <div class="checkout-card">
                    <div class="order-summary">
                        <h5 class="section-title">Order Summary</h5>
                        
                        <div class="summary-row">
                            <span>Subtotal:</span>
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
                        
                        <button type="button" class="btn btn-place-order mt-3" onclick="placeOrder()">
                            <i class="fas fa-lock me-2"></i>Place Order
                        </button>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                By placing this order, you agree to our 
                                <a href="#" class="text-decoration-none">Terms & Conditions</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Additional JavaScript for this page
$additionalJS = '
<script>
    let selectedAddress = ' . ($user_address ? $user_address['address_id'] : 'null') . ';
    let selectedPayment = "payhere";
    let checkoutData = ' . json_encode([
        'items' => $checkout_items,
        'type' => $checkout_type,
        'subtotal' => $subtotal,
        'delivery_cost' => $total_delivery,
        'total' => $grand_total,
        'order_id' => $order_id
    ]) . ';

    function selectAddress(addressId) {
        selectedAddress = addressId;
        
        // Update UI
        document.querySelectorAll(".address-card").forEach(card => {
            card.classList.remove("selected");
        });
        
        event.currentTarget.classList.add("selected");
        
        const radio = event.currentTarget.querySelector("input[type=\"radio\"]");
        if (radio) {
            radio.checked = true;
        }
        
        // Hide new address form if an existing address is selected
        document.getElementById("new-address-form").style.display = "none";
    }

    function selectPayment(method) {
        selectedPayment = method;
        
        // Update UI
        document.querySelectorAll(".payment-card").forEach(card => {
            card.classList.remove("selected");
        });
        
        event.currentTarget.classList.add("selected");
        
        const radio = event.currentTarget.querySelector("input[type=\"radio\"]");
        if (radio) {
            radio.checked = true;
        }
    }

    function toggleNewAddressForm() {
        const form = document.getElementById("new-address-form");
        const isVisible = form.style.display !== "none";
        
        form.style.display = isVisible ? "none" : "block";
        
        if (!isVisible) {
            // Clear selected address
            selectedAddress = null;
            document.querySelectorAll(".address-card").forEach(card => {
                card.classList.remove("selected");
                const radio = card.querySelector("input[type=\"radio\"]");
                if (radio) radio.checked = false;
            });
        }
    }

    function validateAddress() {
        if (selectedAddress) {
            return true; // Using existing address
        }
        
        // Validate new address form
        const firstName = document.getElementById("addr_first_name").value.trim();
        const lastName = document.getElementById("addr_last_name").value.trim();
        const mobile = document.getElementById("addr_mobile").value.trim();
        const addressLine1 = document.getElementById("addr_line_1").value.trim();
        const city = document.getElementById("addr_city").value;
        
        if (!firstName || !lastName || !mobile || !addressLine1 || !city) {
            showToast("Please fill in all required address fields", "error");
            return false;
        }
        
        return true;
    }

    function getAddressData() {
        if (selectedAddress) {
            return { address_id: selectedAddress };
        }
        
        return {
            first_name: document.getElementById("addr_first_name").value.trim(),
            last_name: document.getElementById("addr_last_name").value.trim(),
            mobile: document.getElementById("addr_mobile").value.trim(),
            email: document.getElementById("addr_email").value.trim(),
            address_line_1: document.getElementById("addr_line_1").value.trim(),
            address_line_2: document.getElementById("addr_line_2").value.trim(),
            city_id: document.getElementById("addr_city").value,
            postal_code: document.getElementById("addr_postal").value.trim(),
            save_address: document.getElementById("save_address").checked
        };
    }

    function placeOrder() {
        // Validate address
        if (!validateAddress()) {
            return;
        }
        
        // Prepare order data
        const orderData = {
            checkout_type: checkoutData.type,
            order_id: checkoutData.order_id,
            items: checkoutData.items,
            address: getAddressData(),
            payment_method: selectedPayment,
            subtotal: checkoutData.subtotal,
            delivery_cost: checkoutData.delivery_cost,
            total: checkoutData.total
        };
        
        // Update button state
        const submitBtn = document.querySelector(".btn-place-order");
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i>Processing...";
        submitBtn.disabled = true;
        
        console.log("Order data being sent:", orderData);
        
        fetch("processOrderCheckout.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (selectedPayment === "payhere" && data.payment_data) {
                    // Redirect to payment gateway
                    window.location.href = data.redirect_url;
                } else if (selectedPayment === "cod") {
                    // Show success message and redirect
                    showToast("Order placed successfully!", "success");
                    setTimeout(() => {
                        window.location.href = "orders.php";
                    }, 2000);
                }
            } else {
                showToast(data.message || "Error placing order", "error");
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showToast("Error placing order. Please try again.", "error");
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    // Load district when city is selected
    document.getElementById("addr_city").addEventListener("change", function() {
        const cityId = this.value;
        if (cityId) {
            fetch("getCityDetails.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "city_id=" + cityId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("addr_district").value = data.district_name || "";
                }
            })
            .catch(error => {
                console.error("Error loading district:", error);
            });
        } else {
            document.getElementById("addr_district").value = "";
        }
    });

    // Initialize page
    document.addEventListener("DOMContentLoaded", function() {
        // Set initial states
        if (selectedAddress) {
            const selectedCard = document.querySelector(`input[name="selected_address"][value="${selectedAddress}"]`);
            if (selectedCard) {
                selectedCard.closest(".address-card").classList.add("selected");
                selectedCard.checked = true;
            }
        }
    });
</script>
';

require_once 'footer.php';
?>