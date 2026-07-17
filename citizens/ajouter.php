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