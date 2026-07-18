<?php
/**
 * ============================================
 * CMS Baladiya - Sidebar (Self-Contained)
 * Works on ANY page without auth_check.php
 * ============================================
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Build currentUser from session (works even without auth_check.php)
$currentUser = [
    'id'      => $_SESSION['user_id'] ?? null,
    'nom'     => $_SESSION['nom'] ?? '',
    'prenom'  => $_SESSION['prenom'] ?? '',
    'email'   => $_SESSION['email'] ?? '',
    'role'    => $_SESSION['role'] ?? '',
    'commune' => $_SESSION['commune'] ?? '',
    'avatar'  => $_SESSION['avatar'] ?? 'default.png'
];

// Permission helpers - only define if not already defined by auth_check.php
if (!function_exists('hasRole')) {
    function hasRole(string $role): bool {
        global $currentUser;
        return $currentUser['role'] === $role || $currentUser['role'] === 'super_admin';
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin(): bool {
        global $currentUser;
        return $currentUser['role'] === 'super_admin';
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin(): bool {
        global $currentUser;
    }
}
