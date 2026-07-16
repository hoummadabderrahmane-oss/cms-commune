<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

function old(string $key, $default = '') {
    global $old;
    return htmlspecialchars((string)($old[$key] ?? $default));
}

$etatCivil = ['celibataire' => 'Célibataire', 'marie' => 'Marié(e)', 'divorce' => 'Divorcé(e)', 'veuf' => 'Veuf/Veuve'];
$situations = ['normal' => 'Normal', 'handicap' => 'Handicapé(e)', 'veuf' => 'Veuf/Veuve', 'orphelin' => 'Orphelin', 'demuni' => 'Démuni(e)'];
$statuts = ['actif' => 'Actif', 'decede' => 'Décédé(e)', 'demenage' => 'Déménagé(e)', 'inactif' => 'Inactif'];

$pageTitle = 'Ajouter un citoyen';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white data-table-header">
            <h5><i class="bi bi-person-plus-fill me-2"></i>Ajouter un citoyen</h5>
        </div>
        <div class="card-body">

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="store.php" method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

                <h6 class="text-muted border-bottom pb-1 mt-2">Identité</h6>

                <div class="col-md-3">
                    <label class="form-label">CIN <span class="text-danger">*</span></label>
                    <input type="text" name="cin" class="form-control" required value="<?= old('cin') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control" required value="<?= old('nom') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="prenom" class="form-control" required value="<?= old('prenom') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sexe <span class="text-danger">*</span></label>
                    <select name="sexe" class="form-select" required>
                        <option value="M" <?= old('sexe', 'M') === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= old('sexe') === 'F' ? 'selected' : '' ?>>Féminin</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nom en arabe</label>
                    <input type="text" name="nom_ar" dir="rtl" class="form-control" value="<?= old('nom_ar') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prénom en arabe</label>
                    <input type="text" name="prenom_ar" dir="rtl" class="form-control" value="<?= old('prenom_ar') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance" class="form-control" value="<?= old('date_naissance') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lieu de naissance</label>
                    <input type="text" name="lieu_naissance" class="form-control" value="<?= old('lieu_naissance') ?>">
                </div>

                <h6 class="text-muted border-bottom pb-1 mt-4">Coordonnées</h6>

                <div class="col-md-4">
                    <label class="form-label">Quartier</label>
                    <input type="text" name="quartier" class="form-control" value="<?= old('quartier') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" value="<?= old('telephone') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Adresse</label>
                    <textarea name="adresse" class="form-control" rows="2"><?= old('adresse') ?></textarea>
                </div>

                <h6 class="text-muted border-bottom pb-1 mt-4">Situation</h6>

                <div class="col-md-3">
                    <label class="form-label">État civil</label>
                    <select name="etat_civil" class="form-select">
                        <?php foreach ($etatCivil as $k => $label): ?>
                            <option value="<?= $k ?>" <?= old('etat_civil', 'celibataire') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Situation sociale</label>
                    <select name="situation_sociale" class="form-select">
                        <?php foreach ($situations as $k => $label): ?>
                            <option value="<?= $k ?>" <?= old('situation_sociale', 'normal') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nombre d'enfants</label>
                    <input type="number" name="nombre_enfants" min="0" class="form-control" value="<?= old('nombre_enfants', '0') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <?php foreach ($statuts as $k => $label): ?>
                            <option value="<?= $k ?>" <?= old('statut', 'actif') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Profession</label>
                    <input type="text" name="profession" class="form-control" value="<?= old('profession') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Niveau d'étude</label>
                    <input type="text" name="niveau_etude" class="form-control" value="<?= old('niveau_etude') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Photo (jpg/png, max 2 Mo)</label>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= old('notes') ?></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
===== FIN =====


================================================================
FILE 3/8 : citizens/store.php
===== DEBUT =====
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
===== FIN =====
