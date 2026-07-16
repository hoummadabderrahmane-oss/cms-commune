<?php
/**
 * ============================================
 * CMS Baladiya - Sidebar Bootstrap 4
 * ============================================
 */
global $currentUser;

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-landmark"></i>
        <h4>CMS Baladiya</h4>
        <small>Système de Gestion Municipale</small>
    </div>
    
    <div class="sidebar-menu">
        <a href="../admin/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Tableau de bord</span>
        </a>
        
        <a href="../citizens/index.php" class="nav-link <?= ($currentDir == 'citizens') ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Gestion des Citoyens</span>
        </a>
        
        <a href="#" class="nav-link">
            <i class="fas fa-file-alt"></i>
            <span>Documents</span>
        </a>
        
        <a href="#" class="nav-link">
            <i class="fas fa-chart-bar"></i>
            <span>Statistiques</span>
        </a>
        
        <?php if (isSuperAdmin()): ?>
        <a href="#" class="nav-link">
            <i class="fas fa-user-shield"></i>
            <span>Utilisateurs</span>
        </a>
        
        <a href="#" class="nav-link">
            <i class="fas fa-history"></i>
            <span>Journal d'activités</span>
        </a>
        
        <a href="#" class="nav-link">
            <i class="fas fa-cog"></i>
            <span>Paramètres</span>
        </a>
        <?php endif; ?>
    </div>
    
    <div class="sidebar-footer">
        <i class="fas fa-code"></i> CMS Baladiya v1.0<br>
        <?= htmlspecialchars($currentUser['commune'] ?? 'Commune') ?>
    </div>
</nav>