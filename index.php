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
    
</body> 