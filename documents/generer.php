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

$typeLabels = [
    'extrait_naissance'    => 'Extrait de naissance',
    'certificat_residence' => 'Certificat de résidence',
    'attestation_mariage'  => 'Attestation de mariage',
    'certificat_deces'     => 'Certificat de décès',
    'carte_identite'       => "Carte d'identité",
    'autre'                => 'Autre',
];

/* Citoyens actifs pour la sélection */
$citoyens = $pdo->query("SELECT id, cin, nom, prenom FROM citoyens WHERE statut = 'actif' ORDER BY nom, prenom")->fetchAll();

$pageTitle = 'Générer un document';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white data-table-header">
            <h5><i class="bi bi-file-earmark-plus-fill me-2"></i>Générer un document</h5>
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

                <div class="col-md-6">
                    <label class="form-label">Citoyen <span class="text-danger">*</span></label>
                    <input type="text" id="filterCitoyen" class="form-control mb-2"
                           placeholder="Tapez un nom ou un CIN pour filtrer...">
                    <select name="citoyen_id" id="citoyenSelect" class="form-select" size="8" required>
                        <?php foreach ($citoyens as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= old('citoyen_id') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['cin'] . ' — ' . $c['nom'] . ' ' . $c['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$citoyens): ?>
                        <div class="form-text text-danger">Aucun citoyen actif — ajoutez d'abord un citoyen.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Type de document <span class="text-danger">*</span></label>
                            <select name="type_document" class="form-select" required>
                                <?php foreach ($typeLabels as $k => $label): ?>
                                    <option value="<?= $k ?>" <?= old('type_document') === $k ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'émission</label>
                            <input type="date" name="date_emission" class="form-control"
                                   value="<?= old('date_emission', date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'expiration</label>
                            <input type="date" name="date_expiration" class="form-control"
                                   value="<?= old('date_expiration') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fichier joint (pdf/jpg/png, max 3 Mo — optionnel)</label>
                            <input type="file" name="fichier" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?= old('notes') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Générer et imprimer
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('filterCitoyen').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#citoyenSelect option').forEach(function (o) {
        o.style.display = o.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>