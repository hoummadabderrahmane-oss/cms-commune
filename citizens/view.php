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

$photoFile = __DIR__ . '/../asseets/uploads/citoyens/' . ($c['photo'] ?? '');
$photoSrc  = ($c['photo'] && file_exists($photoFile)) ? '../asseets/uploads/citoyens/' . $c['photo'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche citoyen — <?= val($c['cin']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .fiche { max-width: 800px; margin: 2rem auto; background: #fff; padding: 2.5rem; }
        .fiche h1 { font-size: 1.4rem; }
        .fiche table td { padding: .45rem .75rem; }
        .fiche table td:first-child { font-weight: 700; width: 38%; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .fiche { margin: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

<div class="fiche shadow-sm border">
    <div class="text-center mb-4">
        <h1>Fiche d'information du citoyen</h1>
        <?php if (!empty($_SESSION['commune'])): ?>
            <p class="mb-0">Commune : <?= val($_SESSION['commune']) ?></p>
        <?php endif; ?>
        <p class="text-muted">Générée le <?= date('d/m/Y à H:i') ?></p>
    </div>

    <?php if ($photoSrc): ?>
        <div class="text-center mb-3">
            <img src="<?= htmlspecialchars($photoSrc) ?>" class="img-thumbnail" style="max-width:150px" alt="Photo">
        </div>
    <?php endif; ?>

    <table class="table table-bordered">
        <tr><td>CIN</td><td><?= val($c['cin']) ?></td></tr>
        <tr><td>Nom complet</td><td><?= val($c['nom']) ?> <?= val($c['prenom']) ?></td></tr>
        <?php if ($c['nom_ar'] || $c['prenom_ar']): ?>
        <tr><td>الاسم الكامل</td><td dir="rtl"><?= val(trim($c['nom_ar'] . ' ' . $c['prenom_ar'])) ?></td></tr>
        <?php endif; ?>
        <tr><td>Sexe</td><td><?= $c['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?></td></tr>
        <tr><td>Date de naissance</td><td><?= $c['date_naissance'] ? date('d/m/Y', strtotime($c['date_naissance'])) : '—' ?></td></tr>
        <tr><td>Lieu de naissance</td><td><?= val($c['lieu_naissance']) ?></td></tr>
        <tr><td>État civil</td><td><?= $etatCivil[$c['etat_civil']] ?? val($c['etat_civil']) ?></td></tr>
        <tr><td>Nombre d'enfants</td><td><?= (int)$c['nombre_enfants'] ?></td></tr>
        <tr><td>Situation sociale</td><td><?= $situations[$c['situation_sociale']] ?? val($c['situation_sociale']) ?></td></tr>
        <tr><td>Profession</td><td><?= val($c['profession']) ?></td></tr>
        <tr><td>Niveau d'étude</td><td><?= val($c['niveau_etude']) ?></td></tr>
        <tr><td>Quartier</td><td><?= val($c['quartier']) ?></td></tr>
        <tr><td>Adresse</td><td><?= nl2br(val($c['adresse'])) ?></td></tr>
        <tr><td>Téléphone</td><td><?= val($c['telephone']) ?></td></tr>
        <tr><td>Email</td><td><?= val($c['email']) ?></td></tr>
        <tr><td>Statut</td><td><?= $statutLabels[$c['statut']] ?? val($c['statut']) ?></td></tr>
        <tr><td>Notes</td><td><?= nl2br(val($c['notes'])) ?></td></tr>
    </table>

    <div class="d-flex justify-content-between mt-5">
        <span>Signature de l'agent :</span>
        <span>____________________________</span>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimer</button>
        <a href="show.php?id=<?= (int)$c['id'] ?>" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>

</body>
</html>