<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM citoyens WHERE id = :id');
$stmt->execute([':id' => $id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Citoyen introuvable.'];
    header('Location: index.php');
    exit;
}

function val(?string $v): string {
    return htmlspecialchars($v !== null && $v !== '' ? $v : '—');
}
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
        .fiche table td:first-child { font-weight: 700; width: 40%; }
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
        <p class="text-muted mb-0">Générée le <?= date('d/m/Y à H:i') ?></p>
    </div>

    <table class="table table-bordered">
        <tr><td>CIN</td><td><?= val($c['cin']) ?></td></tr>
        <tr><td>Nom</td><td><?= val($c['nom']) ?></td></tr>
        <tr><td>Prénom</td><td><?= val($c['prenom']) ?></td></tr>
        <tr><td>Sexe</td><td><?= $c['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?></td></tr>
        <tr><td>Date de naissance</td><td><?= val($c['date_naissance']) ?></td></tr>
        <tr><td>Lieu de naissance</td><td><?= val($c['lieu_naissance']) ?></td></tr>
        <tr><td>Situation familiale</td><td><?= val($c['situation_familiale']) ?></td></tr>
        <tr><td>Profession</td><td><?= val($c['profession']) ?></td></tr>
        <tr><td>Téléphone</td><td><?= val($c['telephone']) ?></td></tr>
        <tr><td>Email</td><td><?= val($c['email']) ?></td></tr>
        <tr><td>Commune</td><td><?= val($c['commune']) ?></td></tr>
        <tr><td>Adresse</td><td><?= nl2br(val($c['adresse'])) ?></td></tr>
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