<head>
    <link rel="icon" href="resource/basket.png" />  
    </head>

<?php

session_start();
require_once 'connection.php';

// Set page variables BEFORE including header
$pageTitle = "About Us - ClothesStore";
$requireLogin = false; // Allow both logged in and non-logged in users

// Additional CSS for this page
$additionalCSS = '
<style>
    /* Hero Section */
    .about-hero {
        background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(52, 73, 94, 0.9)), 
                    url("data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1000 400\'><rect fill=\'%23f8f9fa\' width=\'1000\' height=\'400\'/><path fill=\'%23dee2e6\' d=\'M0 200h1000v200H0z\'/></svg>");
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 0;
        text-align: center;
    }

    .about-hero h1 {
        font-size: 3.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .about-hero p {
        font-size: 1.3rem;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        max-width: 600px;
        margin: 0 auto;
    }

    /* Section Styles */
    .section-title {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--primary-color);
        text-align: center;
        margin-bottom: 3rem;
        position: relative;
    }

    .section-title::after {
        content: "";
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: var(--accent-color);
    }

    /* Mission Section */
    .mission-section {
        padding: 80px 0;
        background: white;
    }

    .mission-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-align: center;
        height: 100%;
        transition: transform 0.3s;
    }

    .mission-card:hover {
        transform: translateY(-5px);
    }

    .mission-icon {
        font-size: 3rem;
        color: var(--accent-color);
        margin-bottom: 1.5rem;
    }

    .mission-card h4 {
        color: var(--primary-color);
        font-weight: bold;
        margin-bottom: 1rem;
    }

    /* Values Section */
    .values-section {
        padding: 80px 0;
        background: var(--light-bg);
    }

    .value-item {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }

    .value-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .value-icon {
        font-size: 2.5rem;
        color: var(--secondary-color);
        margin-bottom: 1rem;
    }

    .value-title {
        color: var(--primary-color);
        font-weight: bold;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    /* Team Section */
    .team-section {
        padding: 80px 0;
        background: white;
    }

    .team-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        height: 100%;
    }

    .team-card:hover {
        transform: translateY(-5px);
    }

    .team-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 3rem;
        color: white;
    }

    .team-name {
        color: var(--primary-color);
        font-weight: bold;
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .team-role {
        color: var(--accent-color);
        font-weight: 600;
        margin-bottom: 1rem;
    }

    /* Stats Section */
    .stats-section {
        padding: 80px 0;
        background: var(--primary-color);
        color: white;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
    }

    .stat-number {
        font-size: 3rem;
        font-weight: bold;
        color: var(--accent-color);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 1.1rem;
    }

    /* CTA Section */
    .cta-section {
        padding: 80px 0;
        background: linear-gradient(135deg, var(--secondary-color), #c0392b);
        color: white;
        text-align: center;
    }

    .cta-title {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .cta-text {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-cta {
        background: white;
        color: var(--secondary-color);
        padding: 12px 30px;
        border: none;
        border-radius: 25px;
        font-weight: bold;
        font-size: 1.1rem;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        margin: 0 10px;
    }

    .btn-cta:hover {
        background: var(--light-bg);
        color: var(--primary-color);
        transform: translateY(-2px);
        text-decoration: none;
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding: 2rem 0;
    }

    .timeline::before {
        content: "";
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 3px;
        height: 100%;
        background: var(--accent-color);
    }

    .timeline-item {
        position: relative;
        margin: 2rem 0;
        padding: 0 2rem;
    }

    .timeline-content {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        position: relative;
        width: 45%;
    }

    .timeline-item:nth-child(odd) .timeline-content {
        margin-left: auto;
    }

    .timeline-item:nth-child(even) .timeline-content {
        margin-right: auto;
    }

    .timeline-date {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        background: var(--accent-color);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: bold;
        white-space: nowrap;
        z-index: 2;
    }

    .timeline-icon {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 50px;
        background: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        z-index: 2;
        margin-top: 1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .about-hero h1 {
            font-size: 2.5rem;
        }
        
        .section-title {
            font-size: 2rem;
        }
        
        .timeline::before {
            left: 20px;
        }
        
        .timeline-content {
            width: calc(100% - 60px);
            margin-left: 60px !important;
        }
        
        .timeline-date {
            left: 20px;
        }
        
        .timeline-icon {
            left: 20px;
        }
    }

    @media (max-width: 576px) {
        .about-hero {
            padding: 60px 0;
        }
        
        .mission-section,
        .values-section,
        .team-section,
        .stats-section,
        .cta-section {
            padding: 60px 0;
        }
    }
</style>
';

// Get some statistics from the database
$stats_products = Database::getSingleRow("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$totalProducts = $stats_products ? $stats_products['count'] : 0;

$stats_users = Database::getSingleRow("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$totalUsers = $stats_users ? $stats_users['count'] : 0;

$stats_categories = Database::getSingleRow("SELECT COUNT(*) as count FROM categories WHERE status = 'active'");
$totalCategories = $stats_categories ? $stats_categories['count'] : 0;

$stats_sales = Database::getSingleRow("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'");
$totalSales = $stats_sales ? $stats_sales['count'] : 0;

// Include header
require_once 'header.php';
?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <h1>About ClothesStore</h1>
        <p class="lead">Connecting fashion lovers across Sri Lanka through a trusted marketplace where style meets convenience</p>
    </div>
</section>

<!-- Mission Section -->
<section class="mission-section">
    <div class="container">
        <h2 class="section-title">Our Mission</h2>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4>Connect Communities</h4>
                    <p>We bring together fashion enthusiasts, sellers, and buyers across Sri Lanka, creating a vibrant marketplace that celebrates local style and entrepreneurship.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-recycle"></i>
                    </div>
                    <h4>Promote Sustainability</h4>
                    <p>By facilitating the resale and reuse of quality clothing, we contribute to a more sustainable fashion ecosystem while making style accessible to everyone.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Empower Individuals</h4>
                    <p>We provide a platform for individuals to monetize their fashion choices, discover unique pieces, and express their personal style affordably.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="values-section">
    <div class="container">
        <h2 class="section-title">Our Values</h2>
        <div class="row">
            <div class="col-lg-6">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="value-title">Trust & Security</h4>
                    <p>We prioritize the safety and security of our community through verified profiles, secure payment systems, and transparent transaction processes.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4 class="value-title">Community First</h4>
                    <p>Our community is at the heart of everything we do. We listen, adapt, and grow based on the needs and feedback of our users.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4 class="value-title">Quality Standards</h4>
                    <p>We maintain high standards for product quality and user experience, ensuring every interaction on our platform adds value.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h4 class="value-title">Innovation</h4>
                    <p>We continuously evolve our platform with new features and improvements to enhance the buying and selling experience.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo $totalProducts; ?>">0</div>
                    <div class="stat-label">Active Products</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo $totalUsers; ?>">0</div>
                    <div class="stat-label">Happy Users</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo $totalCategories; ?>">0</div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo $totalSales; ?>">0</div>
                    <div class="stat-label">Successful Sales</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Story Section -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title">Our Story</h2>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-date">2023</div>
                <div class="timeline-icon"><i class="fas fa-lightbulb"></i></div>
                <div class="timeline-content">
                    <h5>The Idea</h5>
                    <p>ClothesStore was born from the vision of creating a trusted platform where Sri Lankans could buy and sell quality clothing, making fashion more accessible and sustainable.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-date">2024</div>
                <div class="timeline-icon"><i class="fas fa-rocket"></i></div>
                <div class="timeline-content">
                    <h5>Platform Launch</h5>
                    <p>After months of development and testing, we launched ClothesStore with core features including user profiles, product listings, and secure transactions.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-date">2025</div>
                <div class="timeline-icon"><i class="fas fa-chart-line"></i></div>
                <div class="timeline-content">
                    <h5>Growing Community</h5>
                    <p>Our platform has grown to serve thousands of users across Sri Lanka, with continuous improvements based on community feedback and market needs.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="container">
        <h2 class="section-title">Meet Our Team</h2>
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="team-card">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="team-name">Saman Perera</div>
                    <div class="team-role">Founder & CEO</div>
                    <p class="text-muted">Passionate about connecting communities through technology and sustainable fashion.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="team-card">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="team-name">Nisha Fernando</div>
                    <div class="team-role">Head of Operations</div>
                    <p class="text-muted">Ensuring smooth platform operations and exceptional user experiences.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="team-card">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="team-name">Rajitha Silva</div>
                    <div class="team-role">Tech Lead</div>
                    <p class="text-muted">Building and maintaining the technology that powers our marketplace.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="team-card">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="team-name">Amara Wickramasinghe</div>
                    <div class="team-role">Community Manager</div>
                    <p class="text-muted">Fostering community growth and maintaining quality standards.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Join Our Growing Community</h2>
        <p class="cta-text">Whether you're looking to discover unique fashion pieces or sell items from your wardrobe, ClothesStore is your trusted partner in fashion.</p>
        <div class="text-center">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="index.php" class="btn-cta">Get Started Today</a>
            <?php else: ?>
                <a href="advancedSearch.php" class="btn-cta">Browse Products</a>
                <a href="addProduct.php" class="btn-cta">Start Selling</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Additional JavaScript for this page
$additionalJS = '
<script>
    // Animate statistics counter
    function animateStats() {
        const stats = document.querySelectorAll(".stat-number");
        
        stats.forEach(stat => {
            const target = parseInt(stat.getAttribute("data-count"));
            const increment = target / 100;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                stat.textContent = Math.floor(current).toLocaleString();
            }, 20);
        });
    }

    // Intersection Observer for stats animation
    function initStatsObserver() {
        const statsSection = document.querySelector(".stats-section");
        
        if (!statsSection) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateStats();
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.5
        });
        
        observer.observe(statsSection);
    }

    // Smooth scrolling for internal links
    function initSmoothScrolling() {
        document.querySelectorAll("a[href^=\"#\"]").forEach(anchor => {
            anchor.addEventListener("click", function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute("href"));
                if (target) {
                    target.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                }
            });
        });
    }

    // Add animation classes on scroll
    function initScrollAnimations() {
        const elements = document.querySelectorAll(".mission-card, .value-item, .team-card, .timeline-item");
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1";
                    entry.target.style.transform = "translateY(0)";
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        });
        
        elements.forEach(element => {
            element.style.opacity = "0";
            element.style.transform = "translateY(30px)";
            element.style.transition = "opacity 0.6s ease, transform 0.6s ease";
            observer.observe(element);
        });
    }

    // Initialize page functionality
    function initializePage() {
        initStatsObserver();
        initSmoothScrolling();
        initScrollAnimations();
        
        // Add loading effect for CTA buttons
        document.querySelectorAll(".btn-cta").forEach(btn => {
            btn.addEventListener("click", function(e) {
                if (this.href && this.href !== "#") {
                    const originalText = this.innerHTML;
                    this.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i>Loading...";
                    
                    // Reset after 3 seconds in case of navigation failure
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 3000);
                }
            });
        });
    }

    // Initialize when DOM is loaded
    document.addEventListener("DOMContentLoaded", initializePage);

    // Re-initialize animations on window resize
    window.addEventListener("resize", () => {
        // Debounce resize events
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(() => {
            initScrollAnimations();
        }, 250);
    });
</script>
';

require_once 'footer.php';
?>