<?php
// footer.php - Common footer file
?>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5><i class="fas fa-tshirt me-2"></i>ClothesStore</h5>
                    <p class="text-light">Your ultimate destination for fashion in Sri Lanka. Find the best deals on clothing from trusted sellers across the country.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-light fs-4"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-warning">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="home.php" class="text-light text-decoration-none">Home</a></li>
                        <li><a href="advancedSearch.php" class="text-light text-decoration-none">Shop</a></li>
                        <li><a href="addProduct.php" class="text-light text-decoration-none">Sell</a></li>
                        <li><a href="aboutUs.php" class="text-light text-decoration-none">About Us</a></li>
                        <li><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-warning">Account</h6>
                    <ul class="list-unstyled">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="userProfile.php" class="text-light text-decoration-none">My Profile</a></li>
                            <li><a href="orders.php" class="text-light text-decoration-none">My Orders</a></li>
                            <li><a href="wishlist.php" class="text-light text-decoration-none">Wishlist</a></li>
                            <li><a href="cart.php" class="text-light text-decoration-none">Cart</a></li>
                        <?php else: ?>
                            <li><a href="index.php" class="text-light text-decoration-none">Sign In</a></li>
                            <li><a href="index.php#signup" class="text-light text-decoration-none">Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-warning">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="help.php" class="text-light text-decoration-none">Help Center</a></li>
                        <li><a href="terms.php" class="text-light text-decoration-none">Terms of Service</a></li>
                        <li><a href="privacy.php" class="text-light text-decoration-none">Privacy Policy</a></li>
                        <li><a href="shipping.php" class="text-light text-decoration-none">Shipping Info</a></li>
                        <li><a href="returns.php" class="text-light text-decoration-none">Returns</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-warning">Categories</h6>
                    <ul class="list-unstyled">
                        <?php
                        $footerCategories = Database::getMultipleRows("
                            SELECT category_id, category_name 
                            FROM categories 
                            WHERE status = 'active' 
                            ORDER BY category_name 
                            LIMIT 5
                        ");
                        if ($footerCategories) {
                            foreach ($footerCategories as $category):
                        ?>
                            <li>
                                <a href="advancedSearch.php?category=<?php echo $category['category_id']; ?>" 
                                   class="text-light text-decoration-none">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </a>
                            </li>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4 border-secondary">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light">&copy; <?php echo date('Y'); ?> ClothesStore. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-light">
                        Made with <i class="fas fa-heart text-danger"></i> in Sri Lanka
                    </p>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <p class="text-muted mb-2">We Accept</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <i class="fab fa-cc-visa text-light fs-2"></i>
                        <i class="fab fa-cc-mastercard text-light fs-2"></i>
                        <i class="fab fa-cc-paypal text-light fs-2"></i>
                        <i class="fas fa-university text-light fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button type="button" class="btn btn-warning position-fixed bottom-0 start-0 m-3" 
            id="backToTopBtn" style="display: none; z-index: 1000;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        // Back to top button functionality
        const backToTopBtn = document.getElementById('backToTopBtn');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });
        
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Handle navbar collapse on mobile
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    const navbarToggler = document.querySelector('.navbar-toggler');
                    if (navbarToggler) {
                        navbarToggler.click();
                    }
                }
            });
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Auto-hide alerts
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            }, 5000);
        });

        // Initialize page-specific functions on DOM load
        document.addEventListener('DOMContentLoaded', function() {
            // Update navbar counts if functions exist
            if (typeof updateWishlistCount === 'function') {
                updateWishlistCount();
            }
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            
            // Load page-specific initialization
            if (typeof initializePage === 'function') {
                initializePage();
            }
        });
    </script>

    <?php
    // Display session messages
    if (isset($_SESSION['success_message'])) {
        echo '<script>showToast("' . addslashes($_SESSION['success_message']) . '", "success");</script>';
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        echo '<script>showToast("' . addslashes($_SESSION['error_message']) . '", "error");</script>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['warning_message'])) {
        echo '<script>showToast("' . addslashes($_SESSION['warning_message']) . '", "warning");</script>';
        unset($_SESSION['warning_message']);
    }

    if (isset($_SESSION['info_message'])) {
        echo '<script>showToast("' . addslashes($_SESSION['info_message']) . '", "info");</script>';
        unset($_SESSION['info_message']);
    }
    ?>

    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>

</body>
</html>