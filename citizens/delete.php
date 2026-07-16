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

/* Récupérer la photo avant suppression */
$stmt = $pdo->prepare('SELECT photo, nom, prenom FROM citoyens WHERE id = :id');
$stmt->execute([':id' => $id]);
$c = $stmt->fetch();

if (!$c) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Citoyen introuvable.'];
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM citoyens WHERE id = :id');
$stmt->execute([':id' => $id]);

/* Supprimer le fichier photo */
if ($c['photo']) {
    $file = __DIR__ . '/../asseets/uploads/citoyens/' . $c['photo'];
    if (file_exists($file)) {
        unlink($file);
    }
}

logActivity('Suppression citoyen', 'citoyens', $id, $c['nom'] . ' ' . $c['prenom']);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Citoyen supprimé avec succès.'];
header('Location: index.php');
exit;