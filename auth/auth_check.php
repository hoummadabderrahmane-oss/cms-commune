<?php
/**
 * ============================================
 * SGC - Vérification d'authentification
 * Include ce fichier dans TOUTES les pages protégées
 * ============================================
 */
if (!defined('SGC_ACCESS')) {
    define('SGC_ACCESS', true);
}

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Vérifier le timeout de session (30 minutes)
$timeout = 1800; // 30 minutes
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: ../index.php?timeout=1');
    exit;
}

// Rafraîchir le timer
$_SESSION['login_time'] = time();

// Variables globales pour les templates
$currentUser = [
    'id'      => $_SESSION['user_id'] ?? null,
    'nom'     => $_SESSION['nom'] ?? '',
    'prenom'  => $_SESSION['prenom'] ?? '',
    'email'   => $_SESSION['email'] ?? '',
    'role'    => $_SESSION['role'] ?? '',
    'commune' => $_SESSION['commune'] ?? '',
    'avatar'  => $_SESSION['avatar'] ?? 'default.png'
];

// Helper: Vérifier les permissions
function hasRole(string $role): bool {
    global $currentUser;
    return $currentUser['role'] === $role || $currentUser['role'] === 'super_admin';
}

function isSuperAdmin(): bool {
    global $currentUser;
    return $currentUser['role'] === 'super_admin';
}