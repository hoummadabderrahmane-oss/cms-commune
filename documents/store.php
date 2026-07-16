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

$types = ['extrait_naissance', 'certificat_residence', 'attestation_mariage', 'certificat_deces', 'carte_identite', 'autre'];
$prefixes = ['extrait_naissance' => 'EXT', 'certificat_residence' => 'RES', 'attestation_mariage' => 'MAR',
             'certificat_deces' => 'DEC', 'carte_identite' => 'CIN', 'autre' => 'AUT'];

$data = [
    'citoyen_id'      => (int)($_POST['citoyen_id'] ?? 0),
    'type_document'   => $_POST['type_document'] ?? '',
    'date_emission'   => $_POST['date_emission'] ?? '',
    'date_expiration' => $_POST['date_expiration'] ?? '',
    'notes'           => trim($_POST['notes'] ?? ''),
];

/* ---------- Validation ---------- */
$errors = [];

$stmt = $pdo->prepare("SELECT id, nom, prenom, cin FROM citoyens WHERE id = :id");
$stmt->execute([':id' => $data['citoyen_id']]);
$citoyen = $stmt->fetch();
if (!$citoyen) {
    $errors[] = 'Veuillez sélectionner un citoyen valide.';
}
if (!in_array($data['type_document'], $types, true)) {
    $errors[] = 'Type de document invalide.';
}
if ($data['date_emission'] !== '' && !strtotime($data['date_emission'])) {
    $errors[] = "Date d'émission invalide.";
}
if ($data['date_expiration'] !== '' && !strtotime($data['date_expiration'])) {
    $errors[] = "Date d'expiration invalide.";
}

/* ---------- Fichier joint (optionnel) ---------- */
$fichierName = null;
if (!empty($_FILES['fichier']['name'])) {
    $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)
        || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK
        || $_FILES['fichier']['size'] > 3 * 1024 * 1024) {
        $errors[] = 'Fichier invalide (pdf/jpg/png, 3 Mo maximum).';
    } else {
        $uploadDir = __DIR__ . '/../asseets/uploads/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fichierName = 'doc_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadDir . $fichierName)) {
            $errors[] = "Erreur lors de l'envoi du fichier.";
            $fichierName = null;
        }
    }
}

if ($errors) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data;
    header('Location: generer.php');
    exit;
}

/* ---------- Insert ---------- */
$stmt = $pdo->prepare("INSERT INTO documents
        (citoyen_id, type_document, fichier, date_emission, date_expiration, statut, notes, created_by)
        VALUES
        (:citoyen_id, :type_document, :fichier, :date_emission, :date_expiration, 'valide', :notes, :created_by)");
$stmt->execute([
    ':citoyen_id'      => $data['citoyen_id'],
    ':type_document'   => $data['type_document'],
    ':fichier'         => $fichierName,
    ':date_emission'   => $data['date_emission'] ?: date('Y-m-d'),
    ':date_expiration' => $data['date_expiration'] ?: null,
    ':notes'           => $data['notes'] ?: null,
    ':created_by'      => $_SESSION['user_id'],
]);

$newId = (int)$pdo->lastInsertId();

/* ---------- Numéro automatique : XXX-AAAA-0000 ---------- */
$numero = $prefixes[$data['type_document']] . '-' . date('Y') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
$pdo->prepare('UPDATE documents SET numero_document = :num WHERE id = :id')
    ->execute([':num' => $numero, ':id' => $newId]);

logActivity('Génération document', 'documents', $newId,
            $numero . ' — ' . $citoyen['nom'] . ' ' . $citoyen['prenom'] . ' (' . $citoyen['cin'] . ')');

header('Location: attestation.php?id=' . $newId . '&new=1');
exit;