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

$etatCivil  = ['celibataire', 'marie', 'divorce', 'veuf'];
$situations = ['normal', 'handicap', 'veuf', 'orphelin', 'demuni'];
$statuts    = ['actif', 'decede', 'demenage', 'inactif'];

$data = [
    'cin'               => trim($_POST['cin'] ?? ''),
    'nom'               => trim($_POST['nom'] ?? ''),
    'prenom'            => trim($_POST['prenom'] ?? ''),
    'nom_ar'            => trim($_POST['nom_ar'] ?? ''),
    'prenom_ar'         => trim($_POST['prenom_ar'] ?? ''),
    'date_naissance'    => $_POST['date_naissance'] ?? '',
    'lieu_naissance'    => trim($_POST['lieu_naissance'] ?? ''),
    'sexe'              => $_POST['sexe'] ?? 'M',
    'etat_civil'        => $_POST['etat_civil'] ?? 'celibataire',
    'adresse'           => trim($_POST['adresse'] ?? ''),
    'quartier'          => trim($_POST['quartier'] ?? ''),
    'telephone'         => trim($_POST['telephone'] ?? ''),
    'email'             => trim($_POST['email'] ?? ''),
    'profession'        => trim($_POST['profession'] ?? ''),
    'niveau_etude'      => trim($_POST['niveau_etude'] ?? ''),
    'situation_sociale' => $_POST['situation_sociale'] ?? 'normal',
    'nombre_enfants'    => max(0, (int)($_POST['nombre_enfants'] ?? 0)),
    'statut'            => $_POST['statut'] ?? 'actif',
    'notes'             => trim($_POST['notes'] ?? ''),
];

/* ---------- Validation ---------- */
$errors = [];
if ($data['cin'] === '')    $errors[] = 'Le CIN est obligatoire.';
if ($data['nom'] === '')    $errors[] = 'Le nom est obligatoire.';
if ($data['prenom'] === '') $errors[] = 'Le prénom est obligatoire.';
if (!in_array($data['sexe'], ['M', 'F'], true))              $errors[] = 'Sexe invalide.';
if (!in_array($data['etat_civil'], $etatCivil, true))        $errors[] = 'État civil invalide.';
if (!in_array($data['situation_sociale'], $situations, true)) $errors[] = 'Situation sociale invalide.';
if (!in_array($data['statut'], $statuts, true))              $errors[] = 'Statut invalide.';
if ($data['date_naissance'] !== '' && !strtotime($data['date_naissance'])) $errors[] = 'Date de naissance invalide.';
if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse email invalide.';

/* CIN unique */
if (!$errors) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM citoyens WHERE cin = :cin');
    $stmt->execute([':cin' => $data['cin']]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Ce CIN existe déjà dans la base de données.';
    }
}

/* ---------- Photo ---------- */
$photoName = null;
if (!empty($_FILES['photo']['name'])) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)
        || $_FILES['photo']['error'] !== UPLOAD_ERR_OK
        || $_FILES['photo']['size'] > 2 * 1024 * 1024) {
        $errors[] = 'Photo invalide (formats jpg/png, 2 Mo maximum).';
    } else {
        $uploadDir = __DIR__ . '/../asseets/uploads/citoyens/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $photoName = 'citoyen_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName)) {
            $errors[] = "Erreur lors de l'envoi de la photo.";
            $photoName = null;
        }
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
        (cin, nom, prenom, nom_ar, prenom_ar, date_naissance, lieu_naissance, sexe,
         etat_civil, adresse, quartier, telephone, email, profession, niveau_etude,
         situation_sociale, nombre_enfants, photo, statut, notes, created_by)
        VALUES
        (:cin, :nom, :prenom, :nom_ar, :prenom_ar, :date_naissance, :lieu_naissance, :sexe,
         :etat_civil, :adresse, :quartier, :telephone, :email, :profession, :niveau_etude,
         :situation_sociale, :nombre_enfants, :photo, :statut, :notes, :created_by)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':cin'               => $data['cin'],
    ':nom'               => $data['nom'],
    ':prenom'            => $data['prenom'],
    ':nom_ar'            => $data['nom_ar'] ?: null,
    ':prenom_ar'         => $data['prenom_ar'] ?: null,
    ':date_naissance'    => $data['date_naissance'] ?: null,
    ':lieu_naissance'    => $data['lieu_naissance'] ?: null,
    ':sexe'              => $data['sexe'],
    ':etat_civil'        => $data['etat_civil'],
    ':adresse'           => $data['adresse'] ?: null,
    ':quartier'          => $data['quartier'] ?: null,
    ':telephone'         => $data['telephone'] ?: null,
    ':email'             => $data['email'] ?: null,
    ':profession'        => $data['profession'] ?: null,
    ':niveau_etude'      => $data['niveau_etude'] ?: null,
    ':situation_sociale' => $data['situation_sociale'],
    ':nombre_enfants'    => $data['nombre_enfants'],
    ':photo'             => $photoName,
    ':statut'            => $data['statut'],
    ':notes'             => $data['notes'] ?: null,
    ':created_by'        => $_SESSION['user_id'],
]);

logActivity('Ajout citoyen', 'citoyens', (int)$pdo->lastInsertId(), $data['nom'] . ' ' . $data['prenom'] . ' (' . $data['cin'] . ')');

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Citoyen ajouté avec succès.'];
header('Location: index.php');
exit;