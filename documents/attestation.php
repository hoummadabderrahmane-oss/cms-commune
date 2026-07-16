<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT d.*, c.nom, c.prenom, c.nom_ar, c.prenom_ar, c.cin, c.sexe,
                              c.date_naissance, c.lieu_naissance, c.adresse, c.quartier, c.etat_civil
                       FROM documents d
                       JOIN citoyens c ON c.id = d.citoyen_id
                       WHERE d.id = :id");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();

if (!$d) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Document introuvable.'];
    header('Location: index.php');
    exit;
}

function val($v): string {
    return htmlspecialchars(($v !== null && $v !== '') ? (string)$v : '—');
}
function dfr($v): string {
    return $v ? date('d/m/Y', strtotime($v)) : '—';
}

$typeLabels = [
    'extrait_naissance'    => 'Extrait de naissance',
    'certificat_residence' => 'Certificat de résidence',
    'attestation_mariage'  => 'Attestation de mariage',
    'certificat_deces'     => 'Certificat de décès',
    'carte_identite'       => "Carte d'identité",
    'autre'                => 'Autre',
];
$etatCivil = ['celibataire' => 'Célibataire', 'marie' => 'Marié(e)', 'divorce' => 'Divorcé(e)', 'veuf' => 'Veuf/Veuve'];

$commune   = $_SESSION['commune'] ?? '';
$adminName = trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? ''));
$nomComplet = $d['nom'] . ' ' . $d['prenom'];
$sexe = $d['sexe'] === 'F' ? 'Féminin' : 'Masculin';

/* ---------- Titre + corps selon le type ---------- */
switch ($d['type_document']) {
    case 'extrait_naissance':
        $titre = "EXTRAIT D'ACTE DE NAISSANCE";
        $corps = "Nous soussigné(e), <strong>" . val($adminName) . "</strong>, agissant au nom de la Commune de <strong>" . val($commune) . "</strong>,
                  certifions que <strong>" . val($nomComplet) . "</strong>, de sexe " . strtolower($sexe) . ",
                  est né(e) le <strong>" . dfr($d['date_naissance']) . "</strong> à <strong>" . val($d['lieu_naissance']) . "</strong>
                  et qu'il/elle est titulaire de la CIN n° <strong>" . val($d['cin']) . "</strong>,
                  conformément aux registres de l'état civil de la commune.";
        break;
    case 'certificat_residence':
        $titre = "ATTESTATION DE RÉSIDENCE";
        $corps = "Nous soussigné(e), <strong>" . val($adminName) . "</strong>, agissant au nom de la Commune de <strong>" . val($commune) . "</strong>,
                  attestons que <strong>" . val($nomComplet) . "</strong>, titulaire de la CIN n° <strong>" . val($d['cin']) . "</strong>,
                  né(e) le <strong>" . dfr($d['date_naissance']) . "</strong>,
                  réside effectivement à l'adresse suivante : <strong>" . nl2br(val($d['adresse'])) . "</strong>,
                  quartier <strong>" . val($d['quartier']) . "</strong>, commune de <strong>" . val($commune) . "</strong>.";
        break;
    case 'attestation_mariage':
        $titre = "ATTESTATION DE SITUATION MATRIMONIALE";
        $corps = "Nous soussigné(e), <strong>" . val($adminName) . "</strong>, agissant au nom de la Commune de <strong>" . val($commune) . "</strong>,
                  attestons que <strong>" . val($nomComplet) . "</strong>, titulaire de la CIN n° <strong>" . val($d['cin']) . "</strong>,
                  est de situation matrimoniale : <strong>" . ($etatCivil[$d['etat_civil']] ?? val($d['etat_civil'])) . "</strong>,
                  selon les informations enregistrées auprès de nos services.";
        break;
    case 'certificat_deces':
        $titre = "CERTIFICAT DE DÉCÈS";
        $corps = "Nous soussigné(e), <strong>" . val($adminName) . "</strong>, agissant au nom de la Commune de <strong>" . val($commune) . "</strong>,
                  certifions le décès de <strong>" . val($nomComplet) . "</strong>, titulaire de la CIN n° <strong>" . val($d['cin']) . "</strong>,
                  né(e) le <strong>" . dfr($d['date_naissance']) . "</strong> à <strong>" . val($d['lieu_naissance']) . "</strong>."
                  . ($d['notes'] ? "<br>Observations : " . nl2br(val($d['notes'])) : "");
        break;
    case 'carte_identite':
        $titre = "ATTESTATION DE DEMANDE DE CARTE D'IDENTITÉ";
        $corps = "Nous soussigné(e), <strong>" . val($adminName) . "</strong>, agissant au nom de la Commune de <strong>" . val($commune) . "</strong>,
                  attestons que la demande de carte nationale d'identité de <strong>" . val($nomComplet) . "</strong>,
                  titulaire de la CIN n° <strong>" . val($d['cin']) . "</strong>, résidant à <strong>" . val($d['adresse']) . "</strong>,
                  quartier <strong>" . val($d['quartier']) . "</strong>, a été dûment enregistrée auprès de nos services.";
        break;
    default:
        $titre = "ATTESTATION";
        $corps = "Nous soussigné(e), <strong>" . val($adminName) . "</strong>, agissant au nom de la Commune de <strong>" . val($commune) . "</strong>,
                  délivrons la présente attestation concernant <strong>" . val($nomComplet) . "</strong>,
                  titulaire de la CIN n° <strong>" . val($d['cin']) . "</strong>."
                  . ($d['notes'] ? "<br>" . nl2br(val($d['notes'])) : "");
        break;
}

$dateEmission = $d['date_emission'] ?: date('Y-m-d', strtotime($d['created_at']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $typeLabels[$d['type_document']] ?? 'Document' ?> — <?= val($d['numero_document']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .fiche { position: relative; max-width: 800px; margin: 2rem auto; background: #fff; padding: 3rem; }
        .entete { text-align: center; border-bottom: 2px solid #1a5f2a; padding-bottom: 1rem; margin-bottom: 2rem; }
        .entete h6 { margin: 0; letter-spacing: 1px; }
        .titre-doc { text-align: center; text-decoration: underline; margin: 2rem 0; font-weight: 700; }
        .corps { line-height: 2; text-align: justify; font-size: 1.05rem; }
        .watermark {
            position: absolute; top: 45%; left: 15%; transform: rotate(-28deg);
            font-size: 5rem; font-weight: 800; color: rgba(220, 53, 69, .12);
            pointer-events: none; z-index: 0;
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .fiche { margin: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

<div class="fiche shadow-sm border">
    <?php if ($d['statut'] !== 'valide'): ?>
        <div class="watermark"><?= $d['statut'] === 'annule' ? 'ANNULÉ' : 'EXPIRÉ' ?></div>
    <?php endif; ?>

    <?php if (!empty($_GET['new'])): ?>
        <div class="alert alert-success no-print">
            Document généré avec succès — n° <strong><?= val($d['numero_document']) ?></strong>.
        </div>
    <?php endif; ?>

    <div class="entete">
        <h6>ROYAUME DU MAROC</h6>
        <h6>MINISTÈRE DE L'INTÉRIEUR</h6>
        <h6>COMMUNE DE <?= mb_strtoupper(val($commune)) ?></h6>
    </div>

    <div class="d-flex justify-content-between mb-2">
        <span>N° <strong><?= val($d['numero_document']) ?></strong></span>
        <span>Statut : <strong><?= val($d['statut']) ?></strong></span>
    </div>

    <h5 class="titre-doc"><?= $titre ?></h5>

    <p class="corps"><?= $corps ?></p>

    <p class="corps mt-3">En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</p>

    <div class="mt-5 text-end">
        <p class="mb-4">Fait à <?= val($commune) ?>, le <?= dfr($dateEmission) ?></p>
        <p class="mb-0"><strong>L'Administrateur</strong></p>
        <p><?= val($adminName) ?></p>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimer</button>
        <a href="index.php" class="btn btn-outline-secondary">Retour à la liste</a>
    </div>
</div>

</body>
</html>