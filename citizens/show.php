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

$pageTitle = 'Détails du citoyen';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white data-table-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-person-badge me-2"></i><?= val($c['nom']) ?> <?= val($c['prenom']) ?></h5>
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
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>CIN :</strong> <?= val($c['cin']) ?></li>
                <li class="list-group-item"><strong>Nom :</strong> <?= val($c['nom']) ?></li>
                <li class="list-group-item"><strong>Prénom :</strong> <?= val($c['prenom']) ?></li>
                <li class="list-group-item"><strong>Sexe :</strong> <?= $c['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?></li>
                <li class="list-group-item"><strong>Date de naissance :</strong> <?= val($c['date_naissance']) ?></li>
                <li class="list-group-item"><strong>Lieu de naissance :</strong> <?= val($c['lieu_naissance']) ?></li>
                <li class="list-group-item"><strong>Situation familiale :</strong> <?= val($c['situation_familiale']) ?></li>
                <li class="list-group-item"><strong>Profession :</strong> <?= val($c['profession']) ?></li>
                <li class="list-group-item"><strong>Téléphone :</strong> <?= val($c['telephone']) ?></li>
                <li class="list-group-item"><strong>Email :</strong> <?= val($c['email']) ?></li>
                <li class="list-group-item"><strong>Commune :</strong> <?= val($c['commune']) ?></li>
                <li class="list-group-item"><strong>Adresse :</strong> <?= nl2br(val($c['adresse'])) ?></li>
                <li class="list-group-item"><strong>Enregistré le :</strong> <?= val($c['created_at']) ?></li>
            </ul>
        </div>
        <div class="card-footer bg-white">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Retour à la liste
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>