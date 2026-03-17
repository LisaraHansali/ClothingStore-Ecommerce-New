<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - ClothesStore</title>
    <link href="bootstrap.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #f39c12;
            --light-bg: #ecf0f1;
            --success-color: #27ae60;
            --warning-color: #f39c12;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }

        .auth-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }

        .auth-left {
            background: linear-gradient(135deg, var(--secondary-color), #c0392b);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            min-height: 600px;
        }

        .auth-right {
            padding: 3rem;
            min-height: 600px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .btn-custom {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-custom:hover {
            background: #1a252f;
            transform: translateY(-2px);
            color: white;
        }

        .btn-secondary-custom {
            background: var(--secondary-color);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-secondary-custom:hover {
            background: #c0392b;
            transform: translateY(-2px);
            color: white;
        }

        .btn-success-custom {
            background: var(--success-color);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-success-custom:hover {
            background: #219a52;
            transform: translateY(-2px);
            color: white;
        }

        .tab-button {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px 25px;
            border-radius: 25px;
            margin: 0 5px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .tab-button.active {
            background: var(--primary-color);
            color: white;
        }

        .tab-button:hover {
            background: var(--primary-color);
            color: white;
        }

        .auth-form {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }

        .auth-form.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .forgot-password {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            color: #e67e22;
            text-decoration: underline;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .feature-icon {
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle .toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
        }

        .footer-text {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .auth-left {
                padding: 2rem;
                min-height: auto;
            }
            
            .auth-right {
                padding: 2rem;
                min-height: auto;
            }
            
            .brand-title {
                font-size: 2rem;
            }

            .main-container {
                padding: 10px;
            }
        }

        .input-group .form-control {
            margin-bottom: 0;
        }

        .gender-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px 12px;
            padding-right: 40px;
        }
    </style>

    <link rel="icon" href="resource/basket.png" />
</head>
<body>
    <div class="container-fluid main-container">
        <div class="row justify-content-center w-100">
            <div class="col-lg-10">
                <div class="auth-container row g-0">
                    <!-- Left Side - Branding -->
                    <div class="col-md-5 auth-left">
                        <div>
                            <div class="brand-title">
                                <i class="fas fa-shopping-cart"></i><br>
                                eShop
                            </div>
                            <p class="brand-subtitle">Your Ultimate Shopping Destination</p>
                            
                            <div class="mt-4">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-shipping-fast"></i>
                                    </div>
                                    <div>
                                        <strong>Fast Delivery</strong><br>
                                        <small>Quick and reliable shipping worldwide</small>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <strong>Secure Shopping</strong><br>
                                        <small>Safe and secure transactions</small>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <strong>Quality Products</strong><br>
                                        <small>Authentic and high-quality items</small>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-headset"></i>
                                    </div>
                                    <div>
                                        <strong>24/7 Support</strong><br>
                                        <small>Round-the-clock customer service</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Side - Forms -->
                    <div class="col-md-7 auth-right">
                        <!-- Tab Buttons -->
                        <div class="text-center mb-4">
                            <button type="button" class="tab-button active" onclick="showForm('signin')">Sign In</button>
                            <button type="button" class="tab-button" onclick="showForm('signup')">Sign Up</button>
                        </div>

                        <!-- Alert Messages -->
                        <div id="alertContainer"></div>

                        <!-- Sign In Form -->
                        <div id="signin-form" class="auth-form active">
                            <h3 class="text-center mb-4">Welcome Back!</h3>
                            
                            <form id="signinForm">
                                <div class="mb-3">
                                    <label for="signin-email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="signin-email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="signin-password" class="form-label">Password</label>
                                    <div class="password-toggle">
                                        <input type="password" class="form-control" id="signin-password" name="password" required>
                                        <button type="button" class="toggle-btn" onclick="togglePassword('signin-password', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="remember-me" name="remember_me">
                                            <label class="form-check-label" for="remember-me">Remember me</label>
                                        </div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <a href="#" class="forgot-password" onclick="showForgotPassword()">Forgot Password?</a>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-custom mb-3">Sign In</button>
                                
                                <div class="text-center">
                                    <a href="adminSignin.php" class="btn btn-success-custom">Admin Login</a>
                                </div>
                            </form>
                        </div>

                        <!-- Sign Up Form -->
                        <div id="signup-form" class="auth-form">
                            <h3 class="text-center mb-4">Create New Account</h3>
                            
                            <form id="signupForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first-name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first-name" name="first_name" placeholder="ex: John" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last-name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last-name" name="last_name" placeholder="ex: Doe" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="signup-email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="signup-email" name="email" placeholder="ex: john@gmail.com" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mobile" class="form-label">Mobile Number</label>
                                            <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="ex: 0771234568" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-control gender-select" id="gender" name="gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="1">Male</option>
                                                <option value="2">Female</option>
                                                <option value="3">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="signup-password" class="form-label">Password</label>
                                    <div class="password-toggle">
                                        <input type="password" class="form-control" id="signup-password" name="password" placeholder="Minimum 6 characters" required minlength="6">
                                        <button type="button" class="toggle-btn" onclick="togglePassword('signup-password', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm-password" class="form-label">Confirm Password</label>
                                    <div class="password-toggle">
                                        <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                                        <button type="button" class="toggle-btn" onclick="togglePassword('confirm-password', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="agree-terms" name="agree_terms" required>
                                    <label class="form-check-label" for="agree-terms">
                                        I agree to the <a href="#" class="forgot-password">Terms & Conditions</a>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-secondary-custom">Create Account</button>
                            </form>
                        </div>

                        <!-- Forgot Password Form -->
                        <div id="forgot-password-form" class="auth-form">
                            <h3 class="text-center mb-4">Reset Password</h3>
                            <p class="text-muted text-center mb-4">Enter your email address and we'll send you a reset link</p>
                            
                            <form id="forgotPasswordForm">
                                <div class="mb-3">
                                    <label for="forgot-email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="forgot-email" name="email" required>
                                </div>
                                
                                <button type="submit" class="btn btn-custom mb-3">Send Reset Link</button>
                                
                                <div class="text-center">
                                    <a href="#" class="forgot-password" onclick="showForm('signin')">Back to Sign In</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="fpmodal" tabindex="-1" aria-labelledby="fpmodalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fpmodalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="np" required>
                                    <button class="btn btn-outline-secondary" type="button" id="npb" onclick="toggleModalPassword('np', 'npb')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Re-type Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="rnp" required>
                                    <button class="btn btn-outline-secondary" type="button" id="rnpb" onclick="toggleModalPassword('rnp', 'rnpb')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Verification Code</label>
                                <input type="text" class="form-control" id="vcode" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="resetPassword()">Reset Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-text d-none d-lg-block">
        <p>&copy; 2024 eShop.lk || All Rights Reserved</p>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
let currentForm = 'signin';

// Form switching functions
function showForm(formType) {
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    if (formType === 'signin') {
        document.getElementById('signin-form').classList.add('active');
        document.querySelectorAll('.tab-button')[0].classList.add('active');
        currentForm = 'signin';
    } else if (formType === 'signup') {
        document.getElementById('signup-form').classList.add('active');
        document.querySelectorAll('.tab-button')[1].classList.add('active');
        currentForm = 'signup';
    }
    
    clearAlerts();
}

function showForgotPassword() {
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    document.getElementById('forgot-password-form').classList.add('active');
    currentForm = 'forgot';
    clearAlerts();
}

// Password visibility toggle
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function toggleModalPassword(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

    </script>

    
</body> 