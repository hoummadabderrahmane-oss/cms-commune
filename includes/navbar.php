<?php
/**
 * ============================================
 * CMS Baladiya - Navbar Bootstrap 4
 * ============================================
 */
global $currentUser;
global $pageTitle;
global $pageIcon;
?>
<div class="navbar-custom">
    <div class="page-title">
        <i class="fas <?= $pageIcon ?? 'fa-home' ?>"></i>
        <?= htmlspecialchars($pageTitle ?? 'CMS Baladiya') ?>
    </div>
    
    <div class="navbar-actions">
        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn btn-link text-dark position-relative" type="button" data-toggle="dropdown">
                <i class="fas fa-bell fa-lg"></i>
                <span class="position-absolute badge badge-danger rounded-pill" style="font-size: 0.6rem; top: -5px; right: -5px;">
                    3
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-right shadow">
                <h6 class="dropdown-header">Notifications</h6>
                <a class="dropdown-item" href="#"><i class="fas fa-user-plus text-success mr-2"></i>Nouveau citoyen ajouté</a>
                <a class="dropdown-item" href="#"><i class="fas fa-file text-primary mr-2"></i>Document expiré</a>
                <a class="dropdown-item" href="#"><i class="fas fa-exclamation text-warning mr-2"></i>Alerte système</a>
            </div>
        </div>
        
        <!-- User Dropdown -->
        <div class="dropdown">
            <div class="user-dropdown" data-toggle="dropdown">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['prenom'] ?? 'A', 0, 1) . substr($currentUser['nom'] ?? 'D', 0, 1)) ?>
                </div>
                <div class="user-info d-none d-md-block">
                    <div class="user-name"><?= htmlspecialchars(($currentUser['prenom'] ?? '') . ' ' . ($currentUser['nom'] ?? '')) ?></div>
                    <div class="user-role"><?= htmlspecialchars($currentUser['role'] ?? '') ?></div>
                </div>
                <i class="fas fa-chevron-down text-muted ml-2" style="font-size: 0.7rem;"></i>
            </div>
            <div class="dropdown-menu dropdown-menu-right shadow">
                <a class="dropdown-item" href="#"><i class="fas fa-user mr-2"></i>Mon profil</a>
                <a class="dropdown-item" href="#"><i class="fas fa-cog mr-2"></i>Paramètres</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Déconnexion</a>
            </div>
        </div>
    </div>
</div>