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

$statutLabels = ['actif' => 'Actif', 'decede' => 'Décédé(e)', 'demenage' => 'Déménagé(e)', 'inactif' => 'Inactif'];
$statutColors = ['actif' => 'success', 'decede' => 'dark', 'demenage' => 'warning', 'inactif' => 'secondary'];

/* ---------- Search + filter + pagination ---------- */
$q      = trim($_GET['q'] ?? '');
$statut = $_GET['statut'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(nom LIKE :q OR prenom LIKE :q OR cin LIKE :q OR quartier LIKE :q OR nom_ar LIKE :q OR prenom_ar LIKE :q)';
    $params[':q'] = "%{$q}%";
}
if (array_key_exists($statut, $statutLabels)) {
    $where[] = 'statut = :statut';
    $params[':statut'] = $statut;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM citoyens $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$sql = "SELECT * FROM citoyens $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$citoyens = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Gestion des citoyens';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white data-table-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-people-fill me-2"></i>Liste des citoyens <span class="badge bg-secondary"><?= $total ?></span></h5>
            <a href="ajouter.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Ajouter un citoyen
            </a>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control"
                           placeholder="Rechercher (nom, prénom, CIN, quartier)..."
                           value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">— Tous les statuts —</option>
                        <?php foreach ($statutLabels as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $statut === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrer</button>
                    <a href="index.php" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>CIN</th>
                            <th>Nom complet</th>
                            <th>Sexe</th>
                            <th>Naissance</th>
                            <th>Quartier</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$citoyens): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Aucun citoyen trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($citoyens as $i => $c): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td><?= htmlspecialchars($c['cin']) ?></td>
                            <td>
                                <?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?>
                                <?php if ($c['nom_ar'] || $c['prenom_ar']): ?>
                                    <br><small class="text-muted" dir="rtl"><?= htmlspecialchars(trim($c['nom_ar'] . ' ' . $c['prenom_ar'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($c['sexe']) ?></td>
                            <td><?= $c['date_naissance'] ? date('d/m/Y', strtotime($c['date_naissance'])) : '—' ?></td>
                            <td><?= htmlspecialchars($c['quartier'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($c['telephone'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $statutColors[$c['statut']] ?? 'secondary' ?>"><?= $statutLabels[$c['statut']] ?? $c['statut'] ?></span></td>
                            <td class="text-end text-nowrap">
                                <a href="show.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="delete.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('Voulez-vous vraiment supprimer ce citoyen ?');">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($q) ?>&statut=<?= urlencode($statut) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>