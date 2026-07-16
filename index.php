<?php
/**
 * ============================================
 * CMS Baladiya - Page de connexion
 * Bootstrap 4 - Design Moderne
 * ============================================
 */
define('SGC_ACCESS', true);
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT id, nom, prenom, email, mot_de_passe, role, commune, statut, avatar 
                FROM utilisateurs 
                WHERE email = :email AND statut = 1
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['nom']        = $user['nom'];
                $_SESSION['prenom']     = $user['prenom'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['commune']    = $user['commune'];
                $_SESSION['avatar']     = $user['avatar'];
                $_SESSION['login_time'] = time();
                
                $db->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = :id")
                   ->execute([':id' => $user['id']]);
                
                logActivity('connexion');
                
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
                logActivity('echec_connexion', null, null, "Tentative: $email");
            }
        } catch (PDOException $e) {
            $error = 'Erreur système. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Baladiya - Connexion</title>
    
    <!-- Bootstrap 4.6.2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a5f2a;
            --primary-light: #2d8a3e;
            --primary-dark: #0d3d16;
            --secondary: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Particules d'arrière-plan */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 15s infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        
        /* Carte de login */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Logo */
        .login-logo {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(26, 95, 42, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .login-logo i {
            font-size: 2.5rem;
            color: white;
        }
        
        .login-title {
            color: var(--primary-dark);
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.3rem;
            font-size: 1.8rem;
        }
        
        .login-subtitle {
            color: #6c757d;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            font-weight: 400;
        }
        
        /* Champs de formulaire */
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border-radius: 12px;
            padding: 0.85rem 1rem 0.85rem 3rem;
            border: 2px solid #e9ecef;
            height: auto;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.15);
            background: white;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 1.1rem;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .form-control:focus + .input-icon,
        .form-group:focus-within .input-icon {
            color: var(--primary);
        }
        
        /* Bouton */
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: 12px;
            padding: 0.9rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(26, 95, 42, 0.3);
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(26, 95, 42, 0.4);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        /* Alert */
        .alert-custom {
            border-radius: 12px;
            border: none;
            background: #fff3cd;
            color: #856404;
            padding: 1rem 1.25rem;
        }
        
        .alert-custom .close {
            color: #856404;
            opacity: 0.7;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            color: rgba(255,255,255,0.8);
            margin-top: 2rem;
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }
        
        .login-footer a {
            color: rgba(255,255,255,0.9);
            text-decoration: underline;
        }
        
        /* Info par défaut */
        .default-info {
            background: #e8f5e9;
            border-radius: 10px;
            padding: 0.75rem;
            text-align: center;
            margin-top: 1rem;
            border-left: 4px solid var(--primary);
        }
        
        .default-info small {
            color: var(--primary-dark);
            font-size: 0.8rem;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                margin: 1rem;
                padding: 2rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
        
        /* Checkbox remember */
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Particules animées -->
    <div class="particles">
        <?php for($i = 0; $i < 20; $i++): ?>
            <div class="particle" style="
                left: <?= rand(0, 100) ?>%;
                width: <?= rand(5, 15) ?>px;
                height: <?= rand(5, 15) ?>px;
                animation-delay: <?= rand(0, 15) ?>s;
                animation-duration: <?= rand(10, 20) ?>s;
            "></div>
        <?php endfor; ?>
    </div>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="login-card">
                    <!-- Logo -->
                    <div class="login-logo">
                        <i class="fas fa-landmark"></i>
                    </div>
                    
                    <!-- Titre -->
                    <h2 class="login-title">CMS Baladiya</h2>
                    <p class="login-subtitle">Système de Gestion Municipale</p>
                    
                    <!-- Erreur -->
                    <?php if ($error): ?>
                        <div class="alert alert-custom alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulaire -->
                    <form method="POST" action="" id="loginForm">
                        <div class="form-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-control" name="email" id="email" 
                                   placeholder="Adresse email" required autofocus
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" name="password" id="password" 
                                   placeholder="Mot de passe" required>
                        </div>
                        
                        <div class="form-group d-flex justify-content-between align-items-center">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                                <label class="custom-control-label" for="remember" style="font-size: 0.85rem; color: #6c757d;">
                                    Se souvenir de moi
                                </label>
                            </div>
                            <a href="#" class="text-success" style="font-size: 0.85rem;">Mot de passe oublié?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                        </button>
                    </form>
                    
                    <!-- Info par défaut -->
                    <div class="default-info">
                        <small>
                            <i class="fas fa-info-circle mr-1"></i>
                            Par défaut: <strong>admin@commune.ma</strong> / <strong>password</strong>
                        </small>
                    </div>
                </div>
                
                <!-- Footer -->
                <p class="login-footer">
                    <i class="fas fa-code mr-1"></i> CMS Baladiya v1.0 - Système de Gestion Municipale<br>
                    <span style="opacity: 0.7;">Développé avec <i class="fas fa-heart" style="color: #ff6b6b;"></i> pour les communes</span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Animation d'entrée
            $('.login-card').hide().fadeIn(600);
            
            // Focus sur le premier champ
            $('#email').focus();
            
            // Animation au clic sur le bouton
            $('#loginForm').on('submit', function() {
                $('.btn-login').html('<i class="fas fa-spinner fa-spin mr-2"></i>Connexion...');
            });
        });
    </script>
</body>
</html>