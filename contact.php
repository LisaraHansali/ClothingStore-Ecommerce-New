<head>
    <link rel="icon" href="resource/basket.png" />  
    </head>

<?php

session_start();
require_once 'connection.php';

// Set page variables BEFORE including header
$pageTitle = "Contact Us - ClothesStore";
$requireLogin = false; // Allow both logged in and non-logged in users

// Handle form submission
$formSuccess = false;
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $formError = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } elseif (strlen($message) < 10) {
        $formError = 'Message must be at least 10 characters long.';
    } else {
        // Insert contact message into database using correct Database methods
        try {
            $insertQuery = "
                INSERT INTO contact_messages (name, email, subject, message, created_at, status) 
                VALUES (?, ?, ?, ?, NOW(), 'new')
            ";
            
            $stmt = Database::prepare($insertQuery, [$name, $email, $subject, $message], 'ssss');
            
            if ($stmt && $stmt->execute()) {
                $formSuccess = true;
                // Clear form data on success
                $name = $email = $subject = $message = '';
            } else {
                $formError = 'Sorry, there was an error sending your message. Please try again.';
            }
            
        } catch (Exception $e) {
            $formError = 'Sorry, there was an error sending your message. Please try again.';
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}

// Get user information if logged in for auto-fill
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_query = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
    $user = Database::getSingleRow($user_query, [$_SESSION['user_id']], 'i');
}

// Additional CSS for this page
$additionalCSS = '
<style>
    /* Hero Section */
    .contact-hero {
        background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(52, 73, 94, 0.9)), 
                    url("data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1000 400\'><rect fill=\'%23f8f9fa\' width=\'1000\' height=\'400\'/><path fill=\'%23dee2e6\' d=\'M0 200h1000v200H0z\'/></svg>");
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 0;
        text-align: center;
    }

    .contact-hero h1 {
        font-size: 3.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .contact-hero p {
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

    /* Contact Form */
    .contact-section {
        padding: 80px 0;
        background: white;
    }

    .contact-form {
        background: white;
        border-radius: 15px;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
    }

    .form-label {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
    }

    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .form-select:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }

    .btn-submit {
        background: var(--accent-color);
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s;
        width: 100%;
    }

    .btn-submit:hover {
        background: #e67e22;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .btn-submit:disabled {
        background: #6c757d;
        transform: none;
        box-shadow: none;
        cursor: not-allowed;
    }

    /* Contact Info */
    .contact-info {
        padding: 80px 0;
        background: var(--light-bg);
    }

    .info-card {
        background: white;
        border-radius: 15px;
        padding: 2.5rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        height: 100%;
        border: 1px solid #e9ecef;
    }

    .info-card:hover {
        transform: translateY(-5px);
    }

    .info-icon {
        font-size: 3rem;
        color: var(--accent-color);
        margin-bottom: 1.5rem;
    }

    .info-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .info-text {
        color: #6c757d;
        font-size: 1rem;
        line-height: 1.6;
    }

    /* FAQ Section */
    .faq-section {
        padding: 80px 0;
        background: white;
    }

    .accordion-item {
        border: none;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1rem;
    }

    .accordion-button {
        background: white;
        color: var(--primary-color);
        font-weight: 600;
        padding: 1.5rem 0;
        border: none;
        box-shadow: none;
    }

    .accordion-button:not(.collapsed) {
        background: white;
        color: var(--accent-color);
        box-shadow: none;
    }

    .accordion-button:focus {
        box-shadow: 0 0 0 0.25rem rgba(243, 156, 18, 0.25);
        border-color: var(--accent-color);
    }

    .accordion-body {
        padding: 1rem 0 2rem 0;
        color: #6c757d;
        line-height: 1.6;
    }

    /* Success/Error Messages */
    .alert-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 2rem;
    }

    .alert-danger {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 2rem;
    }

    /* Map Section */
    .map-section {
        padding: 80px 0;
        background: var(--light-bg);
    }

    .map-container {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-align: center;
        border: 1px solid #e9ecef;
    }

    .map-placeholder {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 4rem 2rem;
        color: #6c757d;
        font-size: 1.2rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .contact-hero h1 {
            font-size: 2.5rem;
        }
        
        .section-title {
            font-size: 2rem;
        }
        
        .contact-form {
            padding: 2rem 1.5rem;
        }
        
        .info-card {
            padding: 2rem 1.5rem;
        }
        
        .accordion-button {
            padding: 1rem 0;
        }
    }

    @media (max-width: 576px) {
        .contact-hero {
            padding: 60px 0;
        }
        
        .contact-section,
        .contact-info,
        .faq-section,
        .map-section {
            padding: 60px 0;
        }
        
        .contact-form {
            padding: 1.5rem 1rem;
        }
    }

    /* Loading spinner for form submission */
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    /* Character counter */
    .char-counter {
        font-size: 0.875rem;
        color: #6c757d;
        text-align: right;
        margin-top: 0.25rem;
    }

    .char-counter.warning {
        color: var(--secondary-color);
    }
</style>
';

// Include header
require_once 'header.php';
?>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <h1>Contact Us</h1>
        <p class="lead">We're here to help! Get in touch with our friendly support team</p>
    </div>
</section>

<!-- Contact Form Section -->
<section class="contact-section">
    <div class="container">
        <h2 class="section-title">Send Us a Message</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="contact-form">
                    <?php if ($formSuccess): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Thank you for contacting us! We've received your message and will get back to you within 24-48 hours.
                        </div>
                    <?php endif; ?>

                    <?php if ($formError): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($formError); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="contact.php" id="contactForm" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Full Name *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                       required
                                       maxlength="100"
                                       placeholder="Enter your full name">
                                <div class="invalid-feedback">
                                    Please provide your full name.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address *
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email"
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       required
                                       maxlength="100"
                                       placeholder="Enter your email address">
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">
                                <i class="fas fa-tag me-1"></i>Subject *
                            </label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="General Inquiry" <?php echo (isset($subject) && $subject === 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="Account Support" <?php echo (isset($subject) && $subject === 'Account Support') ? 'selected' : ''; ?>>Account Support</option>
                                <option value="Payment Issues" <?php echo (isset($subject) && $subject === 'Payment Issues') ? 'selected' : ''; ?>>Payment Issues</option>
                                <option value="Product Support" <?php echo (isset($subject) && $subject === 'Product Support') ? 'selected' : ''; ?>>Product Support</option>
                                <option value="Technical Issues" <?php echo (isset($subject) && $subject === 'Technical Issues') ? 'selected' : ''; ?>>Technical Issues</option>
                                <option value="Seller Support" <?php echo (isset($subject) && $subject === 'Seller Support') ? 'selected' : ''; ?>>Seller Support</option>
                                <option value="Report a Problem" <?php echo (isset($subject) && $subject === 'Report a Problem') ? 'selected' : ''; ?>>Report a Problem</option>
                                <option value="Partnership" <?php echo (isset($subject) && $subject === 'Partnership') ? 'selected' : ''; ?>>Partnership Opportunities</option>
                                <option value="Other" <?php echo (isset($subject) && $subject === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a subject.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label">
                                <i class="fas fa-comment me-1"></i>Message *
                            </label>
                            <textarea class="form-control" 
                                      id="message" 
                                      name="message" 
                                      rows="6" 
                                      required
                                      minlength="10"
                                      maxlength="1000"
                                      placeholder="Please describe your inquiry in detail..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            <div class="char-counter" id="charCounter">0 / 1000 characters</div>
                            <div class="invalid-feedback">
                                Message must be between 10 and 1000 characters.
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-submit" id="submitBtn">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Information -->
<section class="contact-info">
    <div class="container">
        <h2 class="section-title">Get In Touch</h2>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4 class="info-title">Email Us</h4>
                    <p class="info-text">
                        support@clothesstore.lk<br>
                        info@clothesstore.lk<br>
                        <small class="text-muted">We respond within 24-48 hours</small>
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h4 class="info-title">Call Us</h4>
                    <p class="info-text">
                        +94 11 234 5678<br>
                        +94 77 123 4567<br>
                        <small class="text-muted">Mon-Fri 9:00 AM - 6:00 PM</small>
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4 class="info-title">Visit Us</h4>
                    <p class="info-text">
                        123 Main Street<br>
                        Colombo 03, Sri Lanka<br>
                        <small class="text-muted">Mon-Fri 9:00 AM - 5:00 PM</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How do I create an account on ClothesStore?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Creating an account is simple! Click the "Sign Up" link on our homepage, fill in your details, and verify your email address. Once verified, you can start buying and selling on our platform immediately.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I list my products for sale?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                After logging in, click "Sell" in the navigation menu. Upload clear photos of your items, write detailed descriptions, set competitive prices, and publish your listings. Make sure to follow our quality guidelines for the best results.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What payment methods do you accept?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We accept Visa, Mastercard, PayPal, and bank transfers. All transactions are secured with SSL encryption. We also support cash on delivery for local purchases within Colombo area.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                How does shipping work?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sellers handle their own shipping. We recommend using reliable courier services like DHL, UPS, or local postal services. Buyers and sellers can coordinate shipping methods and costs directly through our messaging system.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                What is your return policy?
                            </button>
                        </h3>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Returns are handled between buyers and sellers directly. We recommend clear communication before purchase. In case of disputes, our support team can help mediate and find fair solutions for both parties.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                How can I contact customer support?
                            </button>
                        </h3>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                You can reach us through this contact form, email us at support@clothesstore.lk, or call our helpline. Our support team is available Monday to Friday, 9:00 AM to 6:00 PM (Sri Lanka time). You can also use our live chat feature by visiting the Messages section.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container">
        <h2 class="section-title">Find Us</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="map-container">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                        <h4>Our Office Location</h4>
                        <p class="mb-0">123 Main Street, Colombo 03, Sri Lanka</p>
                        <p class="text-muted">Interactive map coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Additional JavaScript for this page
$additionalJS = '
<script>
    // Form validation and enhancement
    function initializeContactForm() {
        const form = document.getElementById("contactForm");
        const messageTextarea = document.getElementById("message");
        const charCounter = document.getElementById("charCounter");
        const submitBtn = document.getElementById("submitBtn");

        // Character counter for message
        function updateCharCounter() {
            const currentLength = messageTextarea.value.length;
            const maxLength = 1000;
            charCounter.textContent = `${currentLength} / ${maxLength} characters`;
            
            if (currentLength > maxLength * 0.9) {
                charCounter.classList.add("warning");
            } else {
                charCounter.classList.remove("warning");
            }
        }

        messageTextarea.addEventListener("input", updateCharCounter);
        
        // Initialize counter
        updateCharCounter();

        // Form submission handling
        form.addEventListener("submit", function(e) {
            // Prevent double submission
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }

            // Client-side validation
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add("was-validated");
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Sending Message...
            `;
        });

        // Reset form state after page load (in case of server-side validation errors)
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<i class="fas fa-paper-plane me-2"></i>Send Message`;

        // Auto-fill user info if logged in
        <?php if (isset($_SESSION["user_id"]) && isset($user)): ?>
            const nameField = document.getElementById("name");
            const emailField = document.getElementById("email");
            
            if (!nameField.value) {
                nameField.value = "<?php echo htmlspecialchars($user["first_name"] . " " . $user["last_name"]); ?>";
            }
            if (!emailField.value) {
                emailField.value = "<?php echo htmlspecialchars($user["email"]); ?>";
            }
        <?php endif; ?>
    }

    // Initialize page functionality
    function initializePage() {
        initializeContactForm();
    }

    // Initialize when DOM is loaded
    document.addEventListener("DOMContentLoaded", initializePage);
</script>
';

require_once 'footer.php';
?>