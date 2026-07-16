<?php
/**
 * ============================================
 * CMS Baladiya - Générer Document Officiel
 * ============================================
 */
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID invalide');
}

$stmt = $pdo->prepare("
    SELECT d.*, c.*, u.prenom as agent_prenom, u.nom as agent_nom
    FROM documents d
    LEFT JOIN citoyens c ON d.citoyen_id = c.id
    LEFT JOIN utilisateurs u ON d.created_by = u.id
    WHERE d.id = :id
");
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Document non trouvé');
}

$typeLabels = [
    'extrait_naissance' => 'EXTRAIT D'ACTE DE NAISSANCE',
    'certificat_residence' => 'CERTIFICAT DE RÉSIDENCE',
    'attestation_mariage' => 'ATTESTATION DE MARIAGE',
    'certificat_deces' => 'CERTIFICAT DE DÉCÈS',
    'carte_identite' => 'CARTE NATIONALE D'IDENTITÉ',
    'autre' => 'DOCUMENT ADMINISTRATIF'
];

$typeLabel = $typeLabels[$doc['type_document']] ?? 'DOCUMENT ADMINISTRATIF';
$commune = $_SESSION['commune'] ?? 'EL KELÂA DES SRAGHNA';
$province = $_SESSION['province'] ?? 'El Kelâa des Sraghna';

// Calculate age
$age = null;
if ($doc['date_naissance']) {
    $birth = new DateTime($doc['date_naissance']);
    $today = new DateTime();
    $age = $today->diff($birth)->y;
}

// Document content based on type
$documentContent = '';
switch ($doc['type_document']) {
    case 'certificat_residence':
        $documentContent = '
            <p class="doc-body-text">
                Je soussigné, <strong>PRESIDENT DU CONSEIL COMMUNAL</strong> de la commune de <strong>' . htmlspecialchars($commune) . '</strong>, 
                atteste et certifie que :
            </p>
            <div class="citoyen-info">
                <p><strong>Nom et Prénom :</strong> ' . htmlspecialchars(($doc['nom'] ?? '') . ' ' . ($doc['prenom'] ?? '')) . '</p>
                <p><strong>Né(e) le :</strong> ' . ($doc['date_naissance'] ? date('d/m/Y', strtotime($doc['date_naissance'])) : '—') . ' <strong>à</strong> ' . htmlspecialchars($doc['lieu_naissance'] ?? '—') . '</p>
                <p><strong>Carte Nationale d'Identité N° :</strong> ' . htmlspecialchars($doc['cin'] ?? '—') . '</p>
                <p><strong>Quartier :</strong> ' . htmlspecialchars($doc['quartier'] ?? '—') . '</p>
                <p><strong>Téléphone :</strong> ' . htmlspecialchars($doc['telephone'] ?? '—') . '</p>
            </div>
            <p class="doc-body-text">
                Est bien résident(e) au sein de la circonscription territoriale de notre commune, 
                et ce, conformément aux registres de l'état civil et aux données dont nous disposons.
            </p>
            <p class="doc-body-text">
                Le présent certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.
            </p>
        ';
        break;

    case 'extrait_naissance':
        $documentContent = '
            <p class="doc-body-text">
                Je soussigné, <strong>OFFICIER DE L'ÉTAT CIVIL</strong> de la commune de <strong>' . htmlspecialchars($commune) . '</strong>, 
                certifie que les renseignements ci-après sont conformes à ceux portés sur les registres de l'état civil :
            </p>
            <div class="citoyen-info">
                <p><strong>Extrait de l'acte de naissance de :</strong></p>
                <p><strong>Nom :</strong> ' . htmlspecialchars($doc['nom'] ?? '—') . '</p>
                <p><strong>Prénom :</strong> ' . htmlspecialchars($doc['prenom'] ?? '—') . '</p>
                <p><strong>Né(e) le :</strong> ' . ($doc['date_naissance'] ? date('d/m/Y', strtotime($doc['date_naissance'])) : '—') . '</p>
                <p><strong>Lieu de naissance :</strong> ' . htmlspecialchars($doc['lieu_naissance'] ?? '—') . '</p>
                <p><strong>Sexe :</strong> ' . (($doc['sexe'] ?? '') === 'M' ? 'Masculin' : 'Féminin') . '</p>
                <p><strong>Nom du père :</strong> ' . htmlspecialchars($doc['nom_pere'] ?? '—') . '</p>
                <p><strong>Nom de la mère :</strong> ' . htmlspecialchars($doc['nom_mere'] ?? '—') . '</p>
                <p><strong>Numéro d'acte :</strong> ' . htmlspecialchars($doc['numero_document'] ?? '—') . '</p>
            </div>
            <p class="doc-body-text">
                Le présent extrait est délivré conformément aux dispositions du Dahir du 04 Moharrem 1338 (8 octobre 1919) 
                portant réorganisation de l'état civil.
            </p>
        ';
        break;

    case 'attestation_mariage':
        $documentContent = '
            <p class="doc-body-text">
                Je soussigné, <strong>OFFICIER DE L'ÉTAT CIVIL</strong> de la commune de <strong>' . htmlspecialchars($commune) . '</strong>, 
                atteste que :
            </p>
            <div class="citoyen-info">
                <p><strong>Nom et Prénom :</strong> ' . htmlspecialchars(($doc['nom'] ?? '') . ' ' . ($doc['prenom'] ?? '')) . '</p>
                <p><strong>Né(e) le :</strong> ' . ($doc['date_naissance'] ? date('d/m/Y', strtotime($doc['date_naissance'])) : '—') . '</p>
                <p><strong>CNI N° :</strong> ' . htmlspecialchars($doc['cin'] ?? '—') . '</p>
                <p><strong>Quartier :</strong> ' . htmlspecialchars($doc['quartier'] ?? '—') . '</p>
            </div>
            <p class="doc-body-text">
                N'est pas marié(e) et n'a jamais été marié(e) selon les registres de l'état civil de notre commune.
            </p>
            <p class="doc-body-text">
                La présente attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.
            </p>
        ';
        break;

    case 'certificat_deces':
        $documentContent = '
            <p class="doc-body-text">
                Je soussigné, <strong>OFFICIER DE L'ÉTAT CIVIL</strong> de la commune de <strong>' . htmlspecialchars($commune) . '</strong>, 
                certifie le décès de :
            </p>
            <div class="citoyen-info">
                <p><strong>Nom et Prénom :</strong> ' . htmlspecialchars(($doc['nom'] ?? '') . ' ' . ($doc['prenom'] ?? '')) . '</p>
                <p><strong>Né(e) le :</strong> ' . ($doc['date_naissance'] ? date('d/m/Y', strtotime($doc['date_naissance'])) : '—') . '</p>
                <p><strong>CNI N° :</strong> ' . htmlspecialchars($doc['cin'] ?? '—') . '</p>
                <p><strong>Quartier :</strong> ' . htmlspecialchars($doc['quartier'] ?? '—') . '</p>
                <p><strong>Date du décès :</strong> ' . ($doc['date_expiration'] ? date('d/m/Y', strtotime($doc['date_expiration'])) : '—') . '</p>
            </div>
            <p class="doc-body-text">
                Le présent certificat est établi conformément aux registres de l'état civil et aux pièces justificatives produites.
            </p>
        ';
        break;

    default:
        $documentContent = '
            <p class="doc-body-text">
                Je soussigné, <strong>OFFICIER DE L'ÉTAT CIVIL</strong> de la commune de <strong>' . htmlspecialchars($commune) . '</strong>, 
                atteste que :
            </p>
            <div class="citoyen-info">
                <p><strong>Nom et Prénom :</strong> ' . htmlspecialchars(($doc['nom'] ?? '') . ' ' . ($doc['prenom'] ?? '')) . '</p>
                <p><strong>Né(e) le :</strong> ' . ($doc['date_naissance'] ? date('d/m/Y', strtotime($doc['date_naissance'])) : '—') . '</p>
                <p><strong>CNI N° :</strong> ' . htmlspecialchars($doc['cin'] ?? '—') . '</p>
                <p><strong>Quartier :</strong> ' . htmlspecialchars($doc['quartier'] ?? '—') . '</p>
            </div>
            <p class="doc-body-text">
                Le présent document est délivré à l'intéressé(e) pour servir et valoir ce que de droit.
            </p>
        ';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($typeLabel) ?> - <?= htmlspecialchars($doc['numero_document'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .document-container { box-shadow: none !important; border: 2px solid #1a5f2a !important; }
        }

        body {
            background: #f5f5f5;
            font-family: 'Times New Roman', Times, serif;
        }

        .document-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: 3px double #1a5f2a;
            position: relative;
        }

        .doc-header {
            text-align: center;
            border-bottom: 3px double #1a5f2a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .doc-header h6 {
            font-size: 0.85rem;
            margin: 2px 0;
            letter-spacing: 1px;
        }

        .doc-header h4 {
            color: #1a5f2a;
            font-weight: bold;
            margin: 10px 0;
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .doc-header .royal-seal {
            font-size: 2.5rem;
            color: #1a5f2a;
            margin: 10px 0;
        }

        .doc-title {
            text-align: center;
            margin: 30px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #1a5f2a;
            border-radius: 8px;
        }

        .doc-title h3 {
            color: #1a5f2a;
            font-weight: bold;
            margin: 0;
            font-size: 1.4rem;
            text-transform: uppercase;
        }

        .doc-body-text {
            font-size: 1.05rem;
            line-height: 1.8;
            text-align: justify;
            margin: 20px 0;
        }

        .citoyen-info {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #1a5f2a;
            border-radius: 0 8px 8px 0;
        }

        .citoyen-info p {
            margin: 8px 0;
            font-size: 1.05rem;
        }

        .doc-footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .doc-signature {
            text-align: center;
        }

        .doc-signature .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 10px auto 5px;
            padding-top: 5px;
        }

        .doc-stamp {
            width: 120px;
            height: 120px;
            border: 3px solid #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            font-size: 0.75rem;
            text-align: center;
            font-weight: bold;
            transform: rotate(-15deg);
            opacity: 0.8;
        }

        .doc-meta {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
            font-size: 0.85rem;
            color: #666;
        }

        .doc-qr {
            position: absolute;
            bottom: 40px;
            right: 40px;
            width: 80px;
            height: 80px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            text-align: center;
            color: #999;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 4rem;
            color: rgba(26, 95, 42, 0.05);
            font-weight: bold;
            pointer-events: none;
            z-index: 0;
        }

        .doc-number {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 0.8rem;
            color: #666;
            border: 1px solid #ccc;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .arabic-header {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            text-align: center;
            margin-bottom: 10px;
        }

        .arabic-header h6 {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <div class="no-print text-center py-3">
        <button onclick="window.print()" class="btn btn-success btn-lg">
            <i class="bi bi-printer me-2"></i>Imprimer le document
        </button>
        <a href="index.php" class="btn btn-outline-secondary btn-lg ms-2">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>

    <div class="document-container">
        <div class="watermark"><?= htmlspecialchars($commune) ?></div>

        <div class="doc-number">
            N° <?= htmlspecialchars($doc['numero_document'] ?? '—') ?>
        </div>

        <!-- Arabic Header -->
        <div class="arabic-header">
            <h6>المملكة المغربية</h6>
            <h6>وزارة الداخلية</h6>
            <h6>عمالة أو إقليم <?= htmlspecialchars($province) ?></h6>
            <h6>جماعة <?= htmlspecialchars($commune) ?></h6>
        </div>

        <!-- French Header -->
        <div class="doc-header">
            <h6>ROYAUME DU MAROC</h6>
            <h6>MINISTÈRE DE L'INTÉRIEUR</h6>
            <h6>PRÉFECTURE / PROVINCE DE <?= strtoupper(htmlspecialchars($province)) ?></h6>
            <h6>COMMUNE DE <?= strtoupper(htmlspecialchars($commune)) ?></h6>
            <div class="royal-seal">⬡</div>
            <h4><?= htmlspecialchars($typeLabel) ?></h4>
        </div>

        <div class="doc-title">
            <h3><?= htmlspecialchars($typeLabel) ?></h3>
        </div>

        <div class="doc-body">
            <?= $documentContent ?>
        </div>

        <div class="doc-footer">
            <div class="doc-stamp">
                COMMUNE<br><?= strtoupper(htmlspecialchars($commune)) ?><br>CACHEt<br>OFFICIEL
            </div>

            <div class="doc-signature">
                <p>Fait à <strong><?= htmlspecialchars($commune) ?></strong>, le <strong><?= date('d/m/Y', strtotime($doc['date_emission'] ?? 'now')) ?></strong></p>
                <div class="signature-line">
                    <strong>Le Président du Conseil Communal</strong><br>
                    <small>(ou l'Officier de l'État Civil)</small>
                </div>
            </div>
        </div>

        <div class="doc-meta">
            <div class="row">
                <div class="col-6">
                    <strong>Document émis par :</strong> <?= htmlspecialchars(($doc['agent_prenom'] ?? '') . ' ' . ($doc['agent_nom'] ?? '')) ?><br>
                    <strong>Date d'émission :</strong> <?= date('d/m/Y à H:i', strtotime($doc['created_at'])) ?><br>
                    <?php if ($doc['date_expiration']): ?>
                        <strong>Expire le :</strong> <?= date('d/m/Y', strtotime($doc['date_expiration'])) ?>
                    <?php endif; ?>
                </div>
                <div class="col-6 text-end">
                    <strong>Référence :</strong> <?= htmlspecialchars($doc['numero_document'] ?? '—') ?><br>
                    <strong>Statut :</strong> <?= ucfirst($doc['statut'] ?? 'valide') ?><br>
                    <small class="text-muted">Ce document est établi conformément à la législation marocaine en vigueur.</small>
                </div>
            </div>
        </div>

        <div class="doc-qr">
            QR Code<br>Vérification<br><?= htmlspecialchars($doc['numero_document'] ?? '') ?>
        </div>
    </div>
</body>
</html>