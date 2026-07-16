<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_id'])) {
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

/* Make sure the citizen exists */
$stmt = $pdo->prepare('SELECT id FROM citoyens WHERE id = :id');
$stmt->execute([':id' => $id]);
if (!$stmt->fetch()) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Citoyen introuvable.'];
    header('Location: index.php');
    exit;
}

$data = [
    'cin'                 => trim($_POST['cin'] ?? ''),
    'nom'                 => trim($_POST['nom'] ?? ''),
    'prenom'              => trim($_POST['prenom'] ?? ''),
    'sexe'                => $_POST['sexe'] ?? 'M',
    'date_naissance'      => $_POST['date_naissance'] ?? '',
    'lieu_naissance'      => trim($_POST['lieu_naissance'] ?? ''),
    'situation_familiale' => $_POST['situation_familiale'] ?? 'Célibataire',
    'profession'          => trim($_POST['profession'] ?? ''),
    'telephone'           => trim($_POST['telephone'] ?? ''),
    'email'               => trim($_POST['email'] ?? ''),
    'commune'             => trim($_POST['commune'] ?? ''),
    'adresse'             => trim($_POST['adresse'] ?? ''),
];

$errors = [];
if ($data['cin'] === '')            $errors[] = 'Le CIN est obligatoire.';
if ($data['nom'] === '')            $errors[] = 'Le nom est obligatoire.';
if ($data['prenom'] === '')         $errors[] = 'Le prénom est obligatoire.';
if ($data['date_naissance'] === '') $errors[] = 'La date de naissance est obligatoire.';
if (!in_array($data['sexe'], ['M', 'F'], true)) $errors[] = 'Sexe invalide.';
if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Adresse email invalide.';
}

/* CIN unique (excluding current record) */
if (!$errors) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM citoyens WHERE cin = :cin AND id != :id');
    $stmt->execute([':cin' => $data['cin'], ':id' => $id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Ce CIN est déjà utilisé par un autre citoyen.';
    }
}

if ($errors) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data;
    header('Location: edit.php?id=' . $id);
    exit;
}

$sql = "UPDATE citoyens SET
            cin = :cin, nom = :nom, prenom = :prenom, sexe = :sexe,
            date_naissance = :date_naissance, lieu_naissance = :lieu_naissance,
            situation_familiale = :situation_familiale, profession = :profession,
            telephone = :telephone, email = :email, commune = :commune, adresse = :adresse
        WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':cin'                 => $data['cin'],
    ':nom'                 => $data['nom'],
    ':prenom'              => $data['prenom'],
    ':sexe'                => $data['sexe'],
    ':date_naissance'      => $data['date_naissance'],
    ':lieu_naissance'      => $data['lieu_naissance'] ?: null,
    ':situation_familiale' => $data['situation_familiale'],
    ':profession'          => $data['profession'] ?: null,
    ':telephone'           => $data['telephone'] ?: null,
    ':email'               => $data['email'] ?: null,
    ':commune'             => $data['commune'] ?: null,
    ':adresse'             => $data['adresse'] ?: null,
    ':id'                  => $id,
]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Citoyen mis à jour avec succès.'];
header('Location: index.php');
exit;