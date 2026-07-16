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

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT fichier, numero_document FROM documents WHERE id = :id');
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch();

if (!$doc) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Document introuvable.'];
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM documents WHERE id = :id');
$stmt->execute([':id' => $id]);

/* Supprimer le fichier joint */
if ($doc['fichier']) {
    $file = __DIR__ . '/../asseets/uploads/documents/' . $doc['fichier'];
    if (file_exists($file)) {
        unlink($file);
    }
}

logActivity('Suppression document', 'documents', $id, $doc['numero_document'] ?? '');

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Document supprimé avec succès.'];
header('Location: index.php');
exit;
