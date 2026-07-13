<?php
session_start();
require "cms-baladiya-\config\database.php";

$message = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Plain text comparison (matches your database)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['last_activity'] = time();
        
        header("Location: http://localhost\cms-baladiya\dashboard\index.php");
        exit();
    } else {
        $message = "Invalid Email or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CMS Baladiya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            position: relative;
        }

        /* Animated background shapes */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float-shape 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 300px;
            height: 300px;
            top: 50%;
            right: -50px;
            animation-delay: 5s;
        }

        .shape:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: -50px;
            left: 30%;
            animation-delay: 10s;
        }

        .shape:nth-child(4) {
            width: 150px;
            height: 150px;
            top: 20%;
            left: 60%;
            animation-delay: 15s;
        }

        @keyframes float-shape {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(50px, -30px) rotate(90deg);
            }
            50% {
                transform: translate(-30px, 50px) rotate(180deg);
            }
            75% {
                transform: translate(30px, 30px) rotate(270deg);
            }
        }

        /* Login container */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2) inset;
            overflow: hidden;
            animation: card-appear 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes card-appear {
            0% {
                opacity: 0;
                transform: translateY(60px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header */
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            color: white;
            animation: logo-bounce 2s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes logo-bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        .login-header h1 {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        /* Body */
        .login-body {
            padding: 40px 35px;
        }

        /* Form inputs */
        .input-group-custom {
            position: relative;
            margin-bottom: 24px;
        }

        .input-group-custom input {
            width: 100%;
            padding: 16px 20px 16px 55px;
            border: 2px solid #e9ecef;
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .input-group-custom input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .input-group-custom input::placeholder {
            color: #adb5bd;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .input-group-custom input:focus ~ .input-icon {
            color: #667eea;
        }

        /* Remember me */
        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .custom-checkbox input {
            display: none;
        }

        .checkmark {
            width: 22px;
            height: 22px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .custom-checkbox input:checked + .checkmark {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
        }

        .checkmark i {
            color: white;
            font-size: 0.8rem;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s ease;
        }

        .custom-checkbox input:checked + .checkmark i {
            opacity: 1;
            transform: scale(1);
        }

        .remember-text {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Login button */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login .btn-text {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login .spinner {
            display: none;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        /* Alert */
        .alert-custom {
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
            border: 1px solid #feb2b2;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-custom i {
            color: #e53e3e;
            font-size: 1.2rem;
        }

        .alert-custom span {
            color: #c53030;
            font-size: 0.95rem;
            flex: 1;
        }

        .alert-close {
            background: none;
            border: none;
            color: #9b2c2c;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }

        .login-footer p {
            color: #adb5bd;
            font-size: 0.85rem;
        }

        .login-footer i {
            color: #667eea;
        }

        /* Private badge */
        .private-badge {
            position: fixed;
            top: 25px;
            right: 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            animation: fadeInDown 0.8s ease;
            z-index: 10;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 15px;
            }

            .login-body {
                padding: 30px 25px;
            }

            .login-header {
                padding: 40px 25px;
            }

            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <!-- Background shapes -->
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Private badge -->
    <div class="private-badge">
        <i class="bi bi-shield-lock me-2"></i>Private Access
    </div>

    <!-- Login card -->
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <i class="bi bi-building"></i>
                </div>
                <h1>CMS Baladiya</h1>
                <p>Private Management System</p>
            </div>
            <div class="login-body">
                <?php if($message != ""): ?>
                    <div class="alert-custom">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><?php echo $message; ?></span>
                        <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm" autocomplete="off">
                    <div class="input-group-custom">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" name="email" placeholder="Email Address" value="admin@baladiya.com" required>
                    </div>
                    <div class="input-group-custom">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" name="password" placeholder="Password" required autocomplete="new-password">
                    </div>
                    <div class="remember-row">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="remember">
                            <span class="checkmark">
                                <i class="bi bi-check-lg"></i>
                            </span>
                            <span class="remember-text">Remember me</span>
                        </label>
                    </div>
                    <button type="submit" name="login" class="btn-login" id="loginBtn">
                        <span class="btn-text">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Secure Login
                        </span>
                        <span class="spinner spinner-border spinner-border-sm"></span>
                    </button>
                </form>

                <div class="login-footer">
                    <p>
                        <i class="bi bi-shield-check me-1"></i>
                        This is a private system. Unauthorized access is prohibited.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Loading animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Auto-focus password field
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.querySelector('input[type="password"]');
            if (passwordInput && !passwordInput.value) {
                passwordInput.focus();
            }
        });

        // Input animation
        const inputs = document.querySelectorAll('.input-group-custom input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>

</body>
</html>