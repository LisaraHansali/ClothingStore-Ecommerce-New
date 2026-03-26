<head>
    <link rel="icon" href="resource/basket.png" />  
    </head>

<?php

$pageTitle = "Add Category - ClothesStore Admin";
require_once 'connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error_message'] = "Access denied. Admin privileges required.";
    header("Location: adminSignin.php");
    exit();
}

// Get current admin information
$admin = Database::getSingleRow("SELECT * FROM users WHERE user_id = ? AND user_type = 'admin'", [$_SESSION['user_id']], 'i');

if (!$admin) {
    $_SESSION['error_message'] = "Admin account not found.";
    header("Location: adminSignin.php");
    exit();
}

// Get existing categories
try {
    $categories = Database::getMultipleRows("
        SELECT category_id, category_name, status, created_date,
               (SELECT COUNT(*) FROM products WHERE category_id = categories.category_id AND status = 'active') as product_count
        FROM categories 
        ORDER BY category_name
    ");
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="resource/basket.png" />
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #f39c12;
            --success-color: #27ae60;
            --light-bg: #ecf0f1;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: white !important;
            transition: color 0.3s;
        }

        .navbar-nav .nav-link:hover {
            color: var(--accent-color) !important;
        }

        .admin-container {
            padding: 2rem 0;
            min-height: calc(100vh - 80px);
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
        }

        .btn {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-custom {
            background: var(--accent-color);
            border: none;
            color: white;
        }

        .btn-custom:hover {
            background: #e67e22;
            transform: translateY(-2px);
            color: white;
        }

        .category-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-info h6 {
            margin-bottom: 0.25rem;
            color: var(--primary-color);
        }

        .category-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #fff3cd;
            color: #856404;
        }

        .verification-section {
            display: none;
            border-top: 1px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .verification-code {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            text-align: center;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 1.5rem;
            }
            
            .category-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="adminPanel.php">
                <i class="bi bi-shield-check me-2"></i>eShop Admin
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="adminPanel.php">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="container">
            <!-- Admin Header -->
            <div class="admin-header">
                <h1><i class="bi bi-tags me-2"></i>Category Management</h1>
                <p class="mb-0">Add and manage product categories for your eShop</p>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <div class="row">
                <!-- Add Category Form -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-plus-circle me-2"></i>Add New Category
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="addCategoryForm">
                                <div class="mb-3">
                                    <label for="adminEmail" class="form-label">Admin Email</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="adminEmail" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($admin['email']); ?>" 
                                           readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="categoryName" class="form-label">Category Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="categoryName" 
                                           name="name" 
                                           placeholder="Enter category name" 
                                           required>
                                    <div class="form-text">Category names should be unique and descriptive</div>
                                </div>
                                
                                <div class="verification-section" id="verificationSection">
                                    <h6>Email Verification Required</h6>
                                    <p class="text-muted">A verification code has been sent to your email address.</p>
                                    
                                    <div class="verification-code" id="devCode" style="display: none;">
                                        <!-- Development code will be displayed here -->
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="verificationCode" class="form-label">Verification Code</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="verificationCode" 
                                               name="verification_code" 
                                               placeholder="Enter 6-digit code"
                                               maxlength="6">
                                    </div>
                                    
                                    <button type="button" class="btn btn-success w-100" onclick="verifyAndAddCategory()">
                                        <i class="bi bi-check-circle me-2"></i>Verify & Add Category
                                    </button>
                                    
                                    <button type="button" class="btn btn-link w-100 mt-2" onclick="resetForm()">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Form
                                    </button>
                                </div>
                                
                                <div id="initialFormSection">
                                    <button type="submit" class="btn btn-custom w-100">
                                        <i class="bi bi-send me-2"></i>Send Verification Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Existing Categories -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-list me-2"></i>Existing Categories
                            </h5>
                            <span class="badge bg-light text-dark"><?php echo count($categories); ?> Categories</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($categories)): ?>
                                <div style="max-height: 500px; overflow-y: auto;">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="category-item">
                                            <div class="category-info">
                                                <h6><?php echo htmlspecialchars($category['category_name']); ?></h6>
                                                <div class="category-meta">
                                                    <?php echo $category['product_count']; ?> products • 
                                                    Added <?php echo date('M j, Y', strtotime($category['created_date'])); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="status-badge status-<?php echo $category['status']; ?>">
                                                    <?php echo ucfirst($category['status']); ?>
                                                </span>
                                                <?php if ($category['status'] === 'active'): ?>
                                                    <button class="btn btn-sm btn-outline-warning ms-2" 
                                                            onclick="toggleCategoryStatus(<?php echo $category['category_id']; ?>, 'inactive')"
                                                            title="Deactivate Category">
                                                        <i class="bi bi-pause"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-success ms-2" 
                                                            onclick="toggleCategoryStatus(<?php echo $category['category_id']; ?>, 'active')"
                                                            title="Activate Category">
                                                        <i class="bi bi-play"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-tags" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-2">No categories found</p>
                                    <p class="text-muted">Add your first category to get started!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentStep = 'initial';
        
        // Show alert messages
        function showAlert(message, type = 'danger') {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            
            const typeMap = {
                'danger': 'alert-danger',
                'success': 'alert-success',
                'warning': 'alert-warning',
                'info': 'alert-info'
            };
            
            alertDiv.className = `alert ${typeMap[type] || 'alert-danger'} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alertDiv);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    const alert = new bootstrap.Alert(alertDiv);
                    alert.close();
                }
            }, 5000);
        }

        // Clear all alerts
        function clearAlerts() {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = '';
        }

        // Reset form to initial state
        function resetForm() {
            currentStep = 'initial';
            document.getElementById('verificationSection').style.display = 'none';
            document.getElementById('initialFormSection').style.display = 'block';
            document.getElementById('verificationCode').value = '';
            document.getElementById('devCode').style.display = 'none';
            clearAlerts();
        }

        // Add category form submission
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('adminEmail').value.trim();
            const name = document.getElementById('categoryName').value.trim();
            
            if (!name) {
                showAlert('Please enter a category name.');
                return;
            }
            
            if (name.length < 2) {
                showAlert('Category name must be at least 2 characters long.');
                return;
            }
            
            if (name.length > 50) {
                showAlert('Category name must not exceed 50 characters.');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;
            
            clearAlerts();
            
            // Send request to generate verification code
            const formData = new FormData();
            formData.append('email', email);
            formData.append('name', name);
            
            fetch('addNewCategoryProcess.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    // Show verification section
                    currentStep = 'verification';
                    document.getElementById('initialFormSection').style.display = 'none';
                    document.getElementById('verificationSection').style.display = 'block';
                    
                    // Show development code if available (remove in production)
                    if (data.dev_code) {
                        document.getElementById('devCode').innerHTML = `Development Code: <strong>${data.dev_code}</strong>`;
                        document.getElementById('devCode').style.display = 'block';
                    }
                    
                    // Focus on verification input
                    document.getElementById('verificationCode').focus();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });

        // Verify and add category
        function verifyAndAddCategory() {
            const email = document.getElementById('adminEmail').value.trim();
            const name = document.getElementById('categoryName').value.trim();
            const verificationCode = document.getElementById('verificationCode').value.trim();
            
            if (!verificationCode) {
                showAlert('Please enter the verification code.');
                return;
            }
            
            if (verificationCode.length !== 6) {
                showAlert('Verification code must be 6 characters long.');
                return;
            }
            
            const button = document.querySelector('#verificationSection button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-spinner fa-spin me-2"></i>Verifying...';
            button.disabled = true;
            
            clearAlerts();
            
            // Send verification request
            const formData = new FormData();
            formData.append('email', email);
            formData.append('name', name);
            formData.append('verification_code', verificationCode);
            formData.append('verify', 'true');
            
            fetch('verifyCategoryProcess.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    // Reset form and reload categories
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.innerHTML = originalText;
                button.disabled = false;
                showAlert('An error occurred during verification. Please try again.', 'danger');
            });
        }

        // Toggle category status
        function toggleCategoryStatus(categoryId, newStatus) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            
            if (!confirm(`Are you sure you want to ${action} this category?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('category_id', categoryId);
            formData.append('status', newStatus);
            
            fetch('updateCategoryStatus.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        }

        // Auto-format verification code input
        document.getElementById('verificationCode').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            e.target.value = value;
        });

        // Category name validation
        document.getElementById('categoryName').addEventListener('input', function(e) {
            const value = e.target.value;
            const feedback = document.getElementById('categoryFeedback');
            
            // Remove any existing feedback
            if (feedback) {
                feedback.remove();
            }
            
            if (value.length > 0) {
                const feedbackDiv = document.createElement('div');
                feedbackDiv.id = 'categoryFeedback';
                feedbackDiv.className = 'form-text';
                
                if (value.length < 2) {
                    feedbackDiv.className += ' text-warning';
                    feedbackDiv.textContent = 'Category name should be at least 2 characters';
                } else if (value.length > 50) {
                    feedbackDiv.className += ' text-danger';
                    feedbackDiv.textContent = 'Category name is too long (max 50 characters)';
                } else {
                    feedbackDiv.className += ' text-success';
                    feedbackDiv.textContent = 'Valid category name';
                }
                
                this.parentNode.appendChild(feedbackDiv);
            }
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on category name input
            document.getElementById('categoryName').focus();
        });
    </script>
</body>
</html>