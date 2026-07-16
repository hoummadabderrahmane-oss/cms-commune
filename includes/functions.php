<?php
/**
 * ============================================
 * CMS Baladiya - Fonctions utilitaires
 * ============================================
 */

/**
 * Formater une date
 */
function formatDate(string $date, string $format = 'd/m/Y'): string {
    return date($format, strtotime($date));
}

/**
 * Formater un numéro de téléphone
 */
function formatPhone(string $phone): string {
    return preg_replace('/(\d{2})(?=\d)/', '$1 ', $phone);
}

/**
 * Tronquer un texte
 */
function truncate(string $text, int $length = 50): string {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Générer un avatar avec les initiales
 */
function getInitials(string $prenom, string $nom): string {
    return strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
}

/**
 * Vérifier si l'utilisateur a un rôle spécifique
 */
function hasRole(string $role): bool {
    global $currentUser;
    return ($currentUser['role'] ?? '') === $role || ($currentUser['role'] ?? '') === 'super_admin';
}

/**
 * Vérifier si super admin
 */
function isSuperAdmin(): bool {
    global $currentUser;
    return ($currentUser['role'] ?? '') === 'super_admin';
}