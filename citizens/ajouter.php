<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_id'])) {
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
    return htmlspecialchars($old[$key] ?? $default);
}

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

            <form action="store.php" method="POST" class="row g-3">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

                <div class="col-md-4">
                    <label class="form-label">CIN <span class="text-danger">*</span></label>
                    <input type="text" name="cin" class="form-control" required value="<?= old('cin') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control" required value="<?= old('nom') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="prenom" class="form-control" required value="<?= old('prenom') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sexe <span class="text-danger">*</span></label>
                    <select name="sexe" class="form-select" required>
                        <option value="M" <?= old('sexe') === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= old('sexe') === 'F' ? 'selected' : '' ?>>Féminin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                    <input type="date" name="date_naissance" class="form-control" required value="<?= old('date_naissance') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lieu de naissance</label>
                    <input type="text" name="lieu_naissance" class="form-control" value="<?= old('lieu_naissance') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Situation familiale</label>
                    <select name="situation_familiale" class="form-select">
                        <?php foreach (['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf/Veuve'] as $sf): ?>
                            <option value="<?= $sf ?>" <?= old('situation_familiale') === $sf ? 'selected' : '' ?>><?= $sf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Profession</label>
                    <input type="text" name="profession" class="form-control" value="<?= old('profession') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" value="<?= old('telephone') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Commune</label>
                    <input type="text" name="commune" class="form-control" value="<?= old('commune') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Adresse</label>
                    <textarea name="adresse" class="form-control" rows="2"><?= old('adresse') ?></textarea>
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