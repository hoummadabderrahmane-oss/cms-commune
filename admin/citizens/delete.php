<?php
/**
 * ============================================
 * SGC - Supprimer un Citoyen (Direct)
 * ============================================
 */
define('SGC_ACCESS', true);
require_once '../auth/auth_check.php';
require_once '../config/database.php';

global $currentUser;

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID du citoyen invalide.";
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT nom, prenom FROM citoyens WHERE id = ?");
    $stmt->execute([$id]);
    $citoyen = $stmt->fetch();
    
    if ($citoyen) {
        // Suppression logique
        $stmt = $db->prepare("UPDATE citoyens SET statut = 'inactif' WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity('suppression_citoyen', 'citoyens', $id, "Citoyen: {$citoyen['prenom']} {$citoyen['nom']}");
        $_SESSION['success'] = "Citoyen '{$citoyen['prenom']} {$citoyen['nom']}' supprimé avec succès!";
    } else {
        $_SESSION['error'] = "Citoyen non trouvé.";
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la suppression.";
    error_log("Erreur suppression: " . $e->getMessage());
}

header('Location: index.php');
exit;
