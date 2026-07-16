<?php
/**
 * ============================================
 * CMS Baladiya - Changer Statut Document
 * ============================================
 */
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    die('CSRF token invalide');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Cycle through statuses: valide -> expire -> annule -> valide
$stmt = $pdo->prepare("SELECT statut FROM documents WHERE id = :id");
$stmt->execute([':id' => $id]);
$current = $stmt->fetchColumn();

$next = ['valide' => 'expire', 'expire' => 'annule', 'annule' => 'valide'];
$newStatut = $next[$current] ?? 'valide';

try {
    $stmt = $pdo->prepare("UPDATE documents SET statut = :statut WHERE id = :id");
    $stmt->execute([':statut' => $newStatut, ':id' => $id]);

    $labels = ['valide' => 'validé', 'expire' => 'expiré', 'annule' => 'annulé'];
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Document marqué comme ' . $labels[$newStatut] . '.'];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur : ' . $e->getMessage()];
}

header('Location: index.php');
exit;