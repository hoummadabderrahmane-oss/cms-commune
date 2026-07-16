<?php
/**
 * ============================================
 * CMS Baladiya - Création d'un Administrateur
 * ============================================
 * 
 * ⚠️ IMPORTANT: Supprimez ce fichier après utilisation!
 */

define('SGC_ACCESS', true);

$message = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $commune = trim($_POST['commune'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role = $_POST['role'] ?? 'admin';
    
    // Validation
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    if (strlen($password) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    if ($password !== $password_confirm) $errors[] = "Les mots de passe ne correspondent pas";
    if (empty($commune)) $errors[] = "Le nom de la commune est requis";
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Vérifier si l'email existe déjà
            $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = "Cet email existe déjà!";
                $type = "danger";
            } else {
                // Hasher le mot de passe
                $hash = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $db->prepare("
                    INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, commune, telephone, statut)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$nom, $prenom, $email, $hash, $role, $commune, $telephone]);
                
                $message = "Administrateur créé avec succès! Vous pouvez maintenant vous connecter.";
                $type = "success";
            }
        } catch (PDOException $e) {
            $message = "Erreur: " . $e->getMessage();
            $type = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Baladiya - Créer un Admin</title>
    
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
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            width: 100%;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        /* Particules d'arrière-plan */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
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
            0%, 100% { 
                transform: translateY(100vh) rotate(0deg); 
                opacity: 0; 
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { 
                transform: translateY(-100vh) rotate(720deg); 
                opacity: 0; 
            }
        }
        
        /* ===== WRAPPER CENTRÉ ===== */
        .admin-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* ===== CARTE ===== */
        .admin-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.6s ease-out;
            position: relative;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Logo */
        .admin-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            box-shadow: 0 10px 30px rgba(26, 95, 42, 0.4);
        }
        
        .admin-logo i {
            font-size: 2.2rem;
            color: white;
        }
        
        .admin-title {
            color: var(--primary-dark);
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.3rem;
            font-size: 1.6rem;
        }
        
        .admin-subtitle {
            color: #6c757d;
            text-align: center;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            font-weight: 400;
        }
        
        /* Warning box */
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #856404;
        }
        
        /* Formulaire */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            height: auto;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.15);
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #495057;
            margin-bottom: 0.4rem;
        }
        
        /* Select */
        .custom-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            height: calc(1.5em + 1.5rem + 4px);
        }
        
        .custom-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.15);
        }
        
        /* Bouton */
        .btn-create {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(26, 95, 42, 0.3);
        }
        
        .btn-create:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(26, 95, 42, 0.4);
            color: white;
        }
        
        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        /* Footer links */
        .admin-footer {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .admin-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .admin-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* Footer page */
        .page-footer {
            position: fixed;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
            z-index: 1;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .admin-card {
                padding: 1.5rem;
                border-radius: 20px;
            }
            
            .admin-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <!-- Particules animées -->
    <div class="particles">
        <?php for($i = 0; $i < 15; $i++): ?>
            <div class="particle" style="
                left: <?= rand(0, 100) ?>%;
                width: <?= rand(5, 12) ?>px;
                height: <?= rand(5, 12) ?>px;
                animation-delay: <?= rand(0, 15) ?>s;
                animation-duration: <?= rand(10, 20) ?>s;
            "></div>
        <?php endfor; ?>
    </div>
    
    <!-- Wrapper centré -->
    <div class="admin-wrapper">
        <div class="admin-card">
            <!-- Logo -->
            <div class="admin-logo">
                <i class="fas fa-user-shield"></i>
            </div>
            
            <!-- Titre -->
            <h2 class="admin-title">Créer un Administrateur</h2>
            <p class="admin-subtitle">CMS Baladiya - Système de Gestion Municipale</p>
            
            <!-- Warning -->
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Attention!</strong> Supprimez ce fichier après avoir créé l'administrateur.
            </div>
            
            <!-- Message -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible fade show mb-3" role="alert">
                    <?= $message ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire -->
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" required 
                                   placeholder="Nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prenom" required 
                                   placeholder="Prénom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" required 
                           placeholder="admin@commune.ma" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" minlength="6" required
                                   placeholder="Min. 6 caractères">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Confirmer <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password_confirm" minlength="6" required
                                   placeholder="Répéter le mot de passe">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Commune <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="commune" required 
                           placeholder="Ex: Commune de Casablanca" value="<?= htmlspecialchars($_POST['commune'] ?? '') ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone" 
                                   placeholder="06XXXXXXXX" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select class="custom-select" name="role" required>
                                <option value="super_admin">Super Administrateur</option>
                                <option value="admin" selected>Administrateur</option>
                                <option value="agent">Agent</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-create mt-2">
                    <i class="fas fa-user-plus mr-2"></i>Créer l'administrateur
                </button>
            </form>
            
            <!-- Footer links -->
            <div class="admin-footer">
                <a href="index.php">
                    <i class="fas fa-arrow-left mr-1"></i>Retour à la connexion
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer page -->
    <div class="page-footer">
        <i class="fas fa-code mr-1"></i> CMS Baladiya v1.0 - Système de Gestion Municipale
    </div>
    
    <!-- Bootstrap 4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('.admin-card').hide().fadeIn(600);
        });
    </script>
</body>
</html>