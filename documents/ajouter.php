<?php
/**
 * ============================================
 * CMS Baladiya - Ajouter un Document
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

$errors = [];
$typeLabels = [
    'extrait_naissance' => 'Extrait de naissance',
    'certificat_residence' => 'Certificat de résidence',
    'attestation_mariage' => 'Attestation de mariage',
    'certificat_deces' => 'Certificat de décès',
    'carte_identite' => 'Carte d'identité',
    'autre' => 'Autre'
];

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
                INSERT INTO documents (citoyen_id, type_document, numero_document, date_emission, date_expiration, statut, notes, created_by, created_at)
                VALUES (:citoyen_id, :type_document, :numero_document, :date_emission, :date_expiration, 'valide', :notes, :created_by, NOW())
            ");
            $stmt->execute([
                ':citoyen_id' => $citoyen_id,
                ':type_document' => $type_document,
                ':numero_document' => $numero_document,
                ':date_emission' => $date_emission,
                ':date_expiration' => $date_expiration ?: null,
                ':notes' => $notes,
                ':created_by' => $_SESSION['user_id']
            ]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Document ajouté avec succès.'];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de l'ajout : ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Nouveau document';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Nouveau document</h5>
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
                                        <option value="<?= $c['id'] ?>" <?= ($_POST['citoyen_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
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
                                        <option value="<?= $k ?>" <?= ($_POST['type_document'] ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Numéro de document <span class="text-danger">*</span></label>
                                <input type="text" name="numero_document" class="form-control" 
                                       placeholder="Ex: DOC-2026-0001" 
                                       value="<?= htmlspecialchars($_POST['numero_document'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date d'émission <span class="text-danger">*</span></label>
                                <input type="date" name="date_emission" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['date_emission'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date d'expiration</label>
                                <input type="date" name="date_expiration" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['date_expiration'] ?? '') ?>">
                                <small class="text-muted">Laissez vide si sans expiration</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes / Observations</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Informations complémentaires..."></textarea>
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