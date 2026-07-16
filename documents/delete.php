<?php
/**
 * ============================================
 * CMS Baladiya - Supprimer Document
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

try {
    $stmt = $pdo->prepare("DELETE FROM documents WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Document supprimé avec succès.'];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()];
}

header('Location: index.php');
exit;