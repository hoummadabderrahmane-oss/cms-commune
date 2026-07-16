<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM citoyens WHERE id = :id');
$stmt->execute([':id' => $id]);
$c = $stmt->fetch();

if (!$c) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Citoyen introuvable.'];
    header('Location: index.php');
    exit;
}

function val($v): string {
    return htmlspecialchars(($v !== null && $v !== '') ? (string)$v : '—');
}

$etatCivil = ['celibataire' => 'Célibataire', 'marie' => 'Marié(e)', 'divorce' => 'Divorcé(e)', 'veuf' => 'Veuf/Veuve'];
$situations = ['normal' => 'Normal', 'handicap' => 'Handicapé(e)', 'veuf' => 'Veuf/Veuve', 'orphelin' => 'Orphelin', 'demuni' => 'Démuni(e)'];
$statutLabels = ['actif' => 'Actif', 'decede' => 'Décédé(e)', 'demenage' => 'Déménagé(e)', 'inactif' => 'Inactif'];
$statutColors = ['actif' => 'success', 'decede' => 'dark', 'demenage' => 'warning', 'inactif' => 'secondary'];

$photoFile = __DIR__ . '/../asseets/uploads/citoyens/' . ($c['photo'] ?? '');
$photoSrc  = ($c['photo'] && file_exists($photoFile)) ? '../asseets/uploads/citoyens/' . $c['photo'] : null;

$pageTitle = 'Détails du citoyen';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white data-table-header d-flex justify-content-between align-items-center">
            <h5>
                <i class="bi bi-person-badge me-2"></i><?= val($c['nom']) ?> <?= val($c['prenom']) ?>
                <span class="badge bg-<?= $statutColors[$c['statut']] ?? 'secondary' ?> ms-2"><?= $statutLabels[$c['statut']] ?? $c['statut'] ?></span>
            </h5>
            <div class="d-flex gap-2">
                <a href="view.php?id=<?= (int)$c['id'] ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
                    <i class="bi bi-printer me-1"></i>Imprimer la fiche
                </a>
                <a href="edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center mb-3">
                    <?php if ($photoSrc): ?>
                        <img src="<?= htmlspecialchars($photoSrc) ?>" class="img-thumbnail" style="max-width:180px" alt="Photo">
                    <?php else: ?>
                        <div class="border rounded d-flex align-items-center justify-content-center bg-light" style="height:180px">
                            <i class="bi bi-person-fill text-secondary" style="font-size:4rem"></i>
                        </div>
                    <?php endif; ?>
                    <?php if ($c['nom_ar'] || $c['prenom_ar']): ?>
                        <h5 class="mt-2" dir="rtl"><?= val(trim($c['nom_ar'] . ' ' . $c['prenom_ar'])) ?></h5>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>CIN :</strong> <?= val($c['cin']) ?></li>
                        <li class="list-group-item"><strong>Sexe :</strong> <?= $c['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?></li>
                        <li class="list-group-item"><strong>Date de naissance :</strong> <?= $c['date_naissance'] ? date('d/m/Y', strtotime($c['date_naissance'])) : '—' ?></li>
                        <li class="list-group-item"><strong>Lieu de naissance :</strong> <?= val($c['lieu_naissance']) ?></li>
                        <li class="list-group-item"><strong>État civil :</strong> <?= $etatCivil[$c['etat_civil']] ?? val($c['etat_civil']) ?></li>
                        <li class="list-group-item"><strong>Nombre d'enfants :</strong> <?= (int)$c['nombre_enfants'] ?></li>
                        <li class="list-group-item"><strong>Situation sociale :</strong> <?= $situations[$c['situation_sociale']] ?? val($c['situation_sociale']) ?></li>
                        <li class="list-group-item"><strong>Profession :</strong> <?= val($c['profession']) ?></li>
                        <li class="list-group-item"><strong>Niveau d'étude :</strong> <?= val($c['niveau_etude']) ?></li>
                        <li class="list-group-item"><strong>Quartier :</strong> <?= val($c['quartier']) ?></li>
                        <li class="list-group-item"><strong>Adresse :</strong> <?= nl2br(val($c['adresse'])) ?></li>
                        <li class="list-group-item"><strong>Téléphone :</strong> <?= val($c['telephone']) ?></li>
                        <li class="list-group-item"><strong>Email :</strong> <?= val($c['email']) ?></li>
                        <li class="list-group-item"><strong>Notes :</strong> <?= nl2br(val($c['notes'])) ?></li>
                        <li class="list-group-item text-muted">
                            <small>Enregistré le <?= val($c['created_at']) ?> — Dernière modification : <?= val($c['updated_at']) ?></small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Retour à la liste
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>