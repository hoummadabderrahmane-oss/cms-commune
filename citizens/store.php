<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

/* POST only + CSRF */
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Requête invalide.'];
    header('Location: index.php');
    exit;
}

/* ---------- Collect ---------- */
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

/* ---------- Validate ---------- */
$errors = [];
if ($data['cin'] === '')            $errors[] = 'Le CIN est obligatoire.';
if ($data['nom'] === '')            $errors[] = 'Le nom est obligatoire.';
if ($data['prenom'] === '')         $errors[] = 'Le prénom est obligatoire.';
if ($data['date_naissance'] === '') $errors[] = 'La date de naissance est obligatoire.';
if (!in_array($data['sexe'], ['M', 'F'], true)) $errors[] = 'Sexe invalide.';
if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Adresse email invalide.';
}

/* CIN unique */
if (!$errors) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM citoyens WHERE cin = :cin');
    $stmt->execute([':cin' => $data['cin']]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Ce CIN existe déjà dans la base de données.';
    }
}

if ($errors) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data;
    header('Location: ajouter.php');
    exit;
}

/* ---------- Insert ---------- */
$sql = "INSERT INTO citoyens
        (cin, nom, prenom, sexe, date_naissance, lieu_naissance,
         situation_familiale, profession, telephone, email, commune, adresse)
        VALUES
        (:cin, :nom, :prenom, :sexe, :date_naissance, :lieu_naissance,
         :situation_familiale, :profession, :telephone, :email, :commune, :adresse)";
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
]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Citoyen ajouté avec succès.'];
header('Location: index.php');
exit;