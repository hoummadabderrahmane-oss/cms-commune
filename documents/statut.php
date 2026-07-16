<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Requête invalide.'];
    header('Location: index.php');
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$statut = $_POST['statut'] ?? '';

if (!in_array($statut, ['valide', 'expire', 'annule'], true)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Statut invalide.'];
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('UPDATE documents SET statut = :statut WHERE id = :id');
$stmt->execute([':statut' => $statut, ':id' => $id]);

if ($stmt->rowCount() > 0) {
    logActivity('Changement statut document', 'documents', $id, 'Nouveau statut : ' . $statut);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut du document mis à jour.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Document introuvable.'];
}

header('Location: index.php');
exit;