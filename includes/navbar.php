<?php
/**
 * ============================================
 * SGC - Navbar supérieure
 * ============================================
 */
global $currentUser;  // ← AJOUTÉ
global $pageTitle;    // ← AJOUTÉ
global $pageIcon;     // ← AJOUTÉ
?>
<!-- Navbar -->
<div class="navbar-custom">
    <div class="page-title">
        <i class="fas <?= $pageIcon ?? 'fa-home' ?>"></i>
        <?= htmlspecialchars($pageTitle ?? 'SGC') ?>
    </div>
    
    <div class="navbar-actions">
        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn btn-link text-dark position-relative" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell fa-lg"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                    3
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-user-plus text-success me-2"></i>Nouveau citoyen ajouté</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-file text-primary me-2"></i>Document expiré</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation text-warning me-2"></i>Alerte système</a></li>
            </ul>
        </div>
        
        <!-- User Dropdown -->
        <div class="dropdown">
            <div class="user-dropdown" data-bs-toggle="dropdown">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['prenom'] ?? 'A', 0, 1) . substr($currentUser['nom'] ?? 'D', 0, 1)) ?>
                </div>
                <div class="user-info d-none d-md-block">
                    <div class="user-name"><?= htmlspecialchars(($currentUser['prenom'] ?? '') . ' ' . ($currentUser['nom'] ?? '')) ?></div>
                    <div class="user-role"><?= htmlspecialchars($currentUser['role'] ?? '') ?></div>
                </div>
                <i class="fas fa-chevron-down text-muted ms-2" style="font-size: 0.7rem;"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</div>