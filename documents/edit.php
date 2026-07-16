<?php
/**
 * ============================================
 * CMS Baladiya - Modifier Document
 * ============================================
 */
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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$errors = [];
$typeLabels = [
    'extrait_naissance' => 'Extrait de naissance',
    'certificat_residence' => 'Certificat de résidence',
    'attestation_mariage' => 'Attestation de mariage',
    'certificat_deces' => 'Certificat de décès',
    'carte_identite' => 'Carte d'identité',
    'autre' => 'Autre'
];

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = :id");
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch();

if (!$doc) {
    header('Location: index.php');
    exit;
}

// Get citizens for dropdown
$stmt = $pdo->query("SELECT id, nom, prenom, cin FROM citoyens WHERE statut = 'actif' ORDER BY nom, prenom");
$citoyens = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('CSRF token invalide');
    }

    $citoyen_id = (int)($_POST['citoyen_id'] ?? 0);
    $type_document = $_POST['type_document'] ?? '';
    $numero_document = trim($_POST['numero_document'] ?? '');
    $date_emission = $_POST['date_emission'] ?? '';
    $date_expiration = $_POST['date_expiration'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($citoyen_id <= 0) {
        $errors[] = 'Veuillez sélectionner un citoyen.';
    }
    if (!array_key_exists($type_document, $typeLabels)) {
        $errors[] = 'Type de document invalide.';
    }
    if (empty($numero_document)) {
        $errors[] = 'Le numéro de document est obligatoire.';
    }
    if (empty($date_emission)) {
        $errors[] = 'La date d'émission est obligatoire.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE documents 
                SET citoyen_id = :citoyen_id, type_document = :type_document, 
                    numero_document = :numero_document, date_emission = :date_emission, 
                    date_expiration = :date_expiration, notes = :notes
                WHERE id = :id
            ");
            $stmt->execute([
                ':citoyen_id' => $citoyen_id,
                ':type_document' => $type_document,
                ':numero_document' => $numero_document,
                ':date_emission' => $date_emission,
                ':date_expiration' => $date_expiration ?: null,
                ':notes' => $notes,
                ':id' => $id
            ]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Document modifié avec succès.'];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la modification : ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Modifier document';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Modifier document</h5>
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

                    <form method="POST">
                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Citoyen <span class="text-danger">*</span></label>
                                <select name="citoyen_id" class="form-select" required>
                                    <option value="">— Sélectionner un citoyen —</option>
                                    <?php foreach ($citoyens as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($doc['citoyen_id'] == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom'] . ' (' . $c['cin'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type de document <span class="text-danger">*</span></label>
                                <select name="type_document" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($typeLabels as $k => $label): ?>
                                        <option value="<?= $k ?>" <?= ($doc['type_document'] === $k) ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Numéro de document <span class="text-danger">*</span></label>
                                <input type="text" name="numero_document" class="form-control" 
                                       value="<?= htmlspecialchars($doc['numero_document'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date d'émission <span class="text-danger">*</span></label>
                                <input type="date" name="date_emission" class="form-control" 
                                       value="<?= htmlspecialchars($doc['date_emission'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date d'expiration</label>
                                <input type="date" name="date_expiration" class="form-control" 
                                       value="<?= htmlspecialchars($doc['date_expiration'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes / Observations</label>
                                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($doc['notes'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-1"></i>Enregistrer
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>