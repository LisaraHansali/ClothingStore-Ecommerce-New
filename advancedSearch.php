<?php

session_start();
require_once 'connection.php';

// Get categories, brands, models, conditions, and colors for dropdowns
$categories = Database::getMultipleRows("SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name");
$brands = Database::getMultipleRows("SELECT brand_id, brand_name FROM brands WHERE status = 'active' ORDER BY brand_name");
$models = Database::getMultipleRows("SELECT model_id, model_name FROM models WHERE status = 'active' ORDER BY model_name");
$conditions = Database::getMultipleRows("SELECT condition_id, condition_name FROM product_conditions WHERE status = 'active' ORDER BY condition_name");
$colors = Database::getMultipleRows("SELECT color_id, color_name FROM colors WHERE status = 'active' ORDER BY color_name");

// Set page variables
$pageTitle = "Advanced Search - ClothesStore";
$requireLogin = false;

// Additional CSS for this page
$additionalCSS = '
<style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #e74c3c;
        --accent-color: #f39c12;
        --success-color: #27ae60;
        --light-bg: #ecf0f1;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    .search-container {
        padding: 2rem 0;
        min-height: 80vh;
    }

    .search-header {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .search-form {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        transition: border-color 0.15s ease-in-out;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
    }

    .btn-search {
        background: var(--primary-color);
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-search:hover {
        background: #1a252f;
        transform: translateY(-2px);
        color: white;
    }

    .sort-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .results-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 2rem;
        min-height: 400px;
    }

    .product-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s;
        margin-bottom: 1rem;
        height: 100%;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .product-image {
        height: 200px;
        object-fit: cover;
        border-radius: 10px 10px 0 0;
    }

    .product-placeholder {
        height: 200px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 3rem;
        border-radius: 10px 10px 0 0;
    }

    .page-title {
        color: var(--primary-color);
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .search-icon {
        font-size: 4rem;
        color: #6c757d;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .search-form {
            padding: 1rem;
        }
        
        .product-image, .product-placeholder {
            height: 150px;
        }
    }
</style>
';

require_once 'header.php';
?>

<div class="search-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="search-header">
            <div class="row">
                <div class="offset-lg-4 col-12 col-lg-4">
                    <div class="row">
                        <div class="col-2">
                            <div class="mt-2 mb-2 logo" style="height: 80px;">
                                <i class="fas fa-search fa-3x text-primary"></i>
                            </div>
                        </div>
                        <div class="col-10 text-center">
                            <h1 class="page-title mt-3 pt-2">Advanced Search</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="offset-lg-2 col-12 col-lg-8 search-form">
            <div class="row">
                <div class="offset-lg-1 col-12 col-lg-10">
                    <!-- Main Search Bar -->
                    <div class="row">
                        <div class="col-12 col-lg-10 mt-2 mb-1">
                            <input type="text" class="form-control" placeholder="Type keyword to search..." id="searchKeyword"/>
                        </div>
                        <div class="col-12 col-lg-2 mt-2 mb-1 d-grid">
                            <button class="btn btn-search" onclick="advancedSearch(0);">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                        <div class="col-12">
                            <hr class="border border-3 border-primary">
                        </div>
                    </div>

                    <!-- Advanced Filters -->
                    <div class="row">
                        <div class="col-12">
                            <div class="row">
                                <!-- Category -->
                                <div class="col-12 col-lg-4 mb-3">
                                    <select class="form-select" id="categorySelect">
                                        <option value="0">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Brand -->
                                <div class="col-12 col-lg-4 mb-3">
                                    <select class="form-select" id="brandSelect">
                                        <option value="0">Select Brand</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['brand_id']; ?>">
                                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Model -->
                                <div class="col-12 col-lg-4 mb-3">
                                    <select class="form-select" id="modelSelect">
                                        <option value="0">Select Model</option>
                                        <?php foreach ($models as $model): ?>
                                            <option value="<?php echo $model['model_id']; ?>">
                                                <?php echo htmlspecialchars($model['model_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Condition -->
                                <div class="col-12 col-lg-6 mb-3">
                                    <select class="form-select" id="conditionSelect">
                                        <option value="0">Select Condition</option>
                                        <?php foreach ($conditions as $condition): ?>
                                            <option value="<?php echo $condition['condition_id']; ?>">
                                                <?php echo htmlspecialchars($condition['condition_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Color -->
                                <div class="col-12 col-lg-6 mb-3">
                                    <select class="form-select" id="colorSelect">
                                        <option value="0">Select Color</option>
                                        <?php foreach ($colors as $color): ?>
                                            <option value="<?php echo $color['color_id']; ?>">
                                                <?php echo htmlspecialchars($color['color_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Price Range -->
                                <div class="col-12 col-lg-6 mb-3">
                                    <input type="number" class="form-control" placeholder="Price From..." id="priceFrom" min="0" step="0.01"/>
                                </div>

                                <div class="col-12 col-lg-6 mb-3">
                                    <input type="number" class="form-control" placeholder="Price To..." id="priceTo" min="0" step="0.01"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sort Section -->
        <div class="offset-lg-2 col-12 col-lg-8 sort-section">
            <div class="row">
                <div class="offset-8 col-4 mt-2 mb-2">
                    <select id="sortSelect" class="form-select border border-top-0 border-start-0 border-end-0 border-2 border-dark">
                        <option value="0">SORT BY</option>
                        <option value="1">PRICE LOW TO HIGH</option>
                        <option value="2">PRICE HIGH TO LOW</option>
                        <option value="3">QUANTITY LOW TO HIGH</option>
                        <option value="4">QUANTITY HIGH TO LOW</option>
                        <option value="5">NEWEST FIRST</option>
                        <option value="6">OLDEST FIRST</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="offset-lg-2 col-12 col-lg-8 results-section">
            <div class="row">
                <div class="offset-lg-1 col-12 col-lg-10 text-center">
                    <div class="row" id="searchResults">
                        <!-- Default No Search State -->
                        <div class="offset-5 col-2 mt-5">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <div class="offset-3 col-6 mt-3 mb-5">
                            <h4 class="text-muted">No Items Searched Yet...</h4>
                            <p class="text-muted">Use the search form above to find products</p>
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
let currentPage = 0;

// Advanced search function
function advancedSearch(page) {
    currentPage = page;
    
    const keyword = document.getElementById("searchKeyword").value.trim();
    const category = document.getElementById("categorySelect").value;
    const brand = document.getElementById("brandSelect").value;
    const model = document.getElementById("modelSelect").value;
    const condition = document.getElementById("conditionSelect").value;
    const color = document.getElementById("colorSelect").value;
    const priceFrom = document.getElementById("priceFrom").value;
    const priceTo = document.getElementById("priceTo").value;
    const sort = document.getElementById("sortSelect").value;
    
    // Show loading state
    const resultsDiv = document.getElementById("searchResults");
    resultsDiv.innerHTML = `
        <div class="col-12 text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <h5>Searching products...</h5>
        </div>
    `;
    
    // Prepare form data
    const formData = new FormData();
    formData.append("t", keyword);
    formData.append("cat", category);
    formData.append("b", brand);
    formData.append("m", model);
    formData.append("con", condition);
    formData.append("col", color);
    formData.append("pf", priceFrom);
    formData.append("pt", priceTo);
    formData.append("s", sort);
    formData.append("page", page);
    
    // Perform AJAX request
    fetch("advancedSearchProcess.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        resultsDiv.innerHTML = data;
    })
    .catch(error => {
        console.error("Error:", error);
        resultsDiv.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h5>Error loading search results</h5>
                <p class="text-muted">Please try again later</p>
            </div>
        `;
    });
}

// Basic search function for compatibility
function basicSearch(page) {
    const keyword = document.getElementById("searchKeyword").value.trim();
    
    // Reset advanced filters
    document.getElementById("categorySelect").value = "0";
    document.getElementById("brandSelect").value = "0";
    document.getElementById("modelSelect").value = "0";
    document.getElementById("conditionSelect").value = "0";
    document.getElementById("colorSelect").value = "0";
    document.getElementById("priceFrom").value = "";
    document.getElementById("priceTo").value = "";
    document.getElementById("sortSelect").value = "0";
    
    advancedSearch(page);
}

// Auto-search on sort change
document.getElementById("sortSelect").addEventListener("change", function() {
    if (currentPage >= 0) {
        advancedSearch(currentPage);
    }
});

// Search on Enter key
document.getElementById("searchKeyword").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        advancedSearch(0);
    }
});

// Price validation
document.getElementById("priceFrom").addEventListener("change", function() {
    const priceFrom = parseFloat(this.value);
    const priceTo = parseFloat(document.getElementById("priceTo").value);
    
    if (priceFrom < 0) {
        this.value = "";
        showToast("Price cannot be negative", "warning");
    } else if (priceTo && priceFrom > priceTo) {
        document.getElementById("priceTo").value = "";
        showToast("Price from cannot be greater than price to", "warning");
    }
});

document.getElementById("priceTo").addEventListener("change", function() {
    const priceFrom = parseFloat(document.getElementById("priceFrom").value);
    const priceTo = parseFloat(this.value);
    
    if (priceTo < 0) {
        this.value = "";
        showToast("Price cannot be negative", "warning");
    } else if (priceFrom && priceTo < priceFrom) {
        this.value = "";
        showToast("Price to cannot be less than price from", "warning");
    }
});

// Clear search function
function clearSearch() {
    document.getElementById("searchKeyword").value = "";
    document.getElementById("categorySelect").value = "0";
    document.getElementById("brandSelect").value = "0";
    document.getElementById("modelSelect").value = "0";
    document.getElementById("conditionSelect").value = "0";
    document.getElementById("colorSelect").value = "0";
    document.getElementById("priceFrom").value = "";
    document.getElementById("priceTo").value = "";
    document.getElementById("sortSelect").value = "0";
    
    // Reset to no search state
    document.getElementById("searchResults").innerHTML = `
        <div class="offset-5 col-2 mt-5">
            <i class="fas fa-search search-icon"></i>
        </div>
        <div class="offset-3 col-6 mt-3 mb-5">
            <h4 class="text-muted">No Items Searched Yet...</h4>
            <p class="text-muted">Use the search form above to find products</p>
        </div>
    `;
    currentPage = -1;
}

// Show toast notification
function showToast(message, type) {
    const toastContainer = document.getElementById("toastContainer") || createToastContainer();
    
    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-white bg-${type === "success" ? "success" : type === "warning" ? "warning" : "danger"} border-0`;
    toast.setAttribute("role", "alert");
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === "success" ? "check-circle" : type === "warning" ? "exclamation-triangle" : "exclamation-circle"} me-2"></i>
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

// Create toast container if it doesn\'t exist
function createToastContainer() {
    const container = document.createElement("div");
    container.id = "toastContainer";
    container.className = "toast-container position-fixed bottom-0 end-0 p-3";
    container.style.zIndex = "1050";
    document.body.appendChild(container);
    return container;
}

// Auto-fill from URL parameters
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get("search")) {
        document.getElementById("searchKeyword").value = urlParams.get("search");
    }
    
    if (urlParams.get("category")) {
        document.getElementById("categorySelect").value = urlParams.get("category");
    }
    
    // Auto-search if parameters exist
    if (urlParams.get("search") || urlParams.get("category")) {
        advancedSearch(0);
    }
});
</script>
';

require_once 'footer.php';
?>