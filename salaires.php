<?php
$pageTitle = 'Gestion des Salaires';
require_once 'includes/header.php';
require_once 'config/database.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $employe_id = $_POST['employe_id'] ?? '';
        $mois = $_POST['mois'] ?? '';
        $annee = $_POST['annee'] ?? '';
        $salaire_base = $_POST['salaire_base'] ?? 0;
        $prime = $_POST['prime'] ?? 0;
        $heures_supplementaires = $_POST['heures_supplementaires'] ?? 0;
        $montant_heures_sup = $_POST['montant_heures_sup'] ?? 0;
        $retenues = $_POST['retenues'] ?? 0;
        $date_paiement = $_POST['date_paiement'] ?? null;
        $statut = $_POST['statut'] ?? 'en_attente';
        
        $salaire_net = $salaire_base + $prime + $montant_heures_sup - $retenues;

        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO salaires (employe_id, mois, annee, salaire_base, prime, heures_supplementaires, montant_heures_sup, retenues, salaire_net, date_paiement, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$employe_id, $mois, $annee, $salaire_base, $prime, $heures_supplementaires, $montant_heures_sup, $retenues, $salaire_net, $date_paiement ?: null, $statut]);
            header('Location: salaires.php?success=1');
        } else {
            $stmt = $db->prepare("UPDATE salaires SET employe_id=?, mois=?, annee=?, salaire_base=?, prime=?, heures_supplementaires=?, montant_heures_sup=?, retenues=?, salaire_net=?, date_paiement=?, statut=? WHERE id=?");
            $stmt->execute([$employe_id, $mois, $annee, $salaire_base, $prime, $heures_supplementaires, $montant_heures_sup, $retenues, $salaire_net, $date_paiement ?: null, $statut, $id]);
            header('Location: salaires.php?success=1');
        }
        exit();
    }
}

// Suppression
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM salaires WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: salaires.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $salaire = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM salaires WHERE id = ?");
        $stmt->execute([$id]);
        $salaire = $stmt->fetch();
        if (!$salaire) {
            header('Location: salaires.php');
            exit();
        }
    }
    
    $stmt = $db->query("SELECT id, matricule, nom, prenom, salaire_base FROM employes WHERE statut = 'actif' ORDER BY nom, prenom");
    $employes = $stmt->fetchAll();
    ?>
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-<?= $action === 'add' ? 'cash-stack' : 'cash-coin' ?>"></i>
                <?= $action === 'add' ? 'Ajouter un salaire' : 'Modifier un salaire' ?>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" id="salaireForm">
                        <div class="mb-3">
                            <label class="form-label">Employé *</label>
                            <select class="form-select" name="employe_id" id="employe_id" required>
                                <option value="">Sélectionner un employé</option>
                                <?php foreach ($employes as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" data-salaire="<?= $emp['salaire_base'] ?>" <?= ($salaire['employe_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['matricule'] . ' - ' . $emp['prenom'] . ' ' . $emp['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Mois *</label>
                                <select class="form-select" name="mois" required>
                                    <?php
                                    $mois_noms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                                    for ($i = 1; $i <= 12; $i++):
                                    ?>
                                        <option value="<?= $i ?>" <?= ($salaire['mois'] ?? '') == $i ? 'selected' : '' ?>>
                                            <?= $mois_noms[$i] ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Année *</label>
                                <input type="number" class="form-control" name="annee" value="<?= $salaire['annee'] ?? date('Y') ?>" min="2020" max="2100" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Statut *</label>
                                <select class="form-select" name="statut" required>
                                    <option value="en_attente" <?= ($salaire['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                    <option value="payé" <?= ($salaire['statut'] ?? '') === 'payé' ? 'selected' : '' ?>>Payé</option>
                                    <option value="annulé" <?= ($salaire['statut'] ?? '') === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                                </select>
                            </div>
                        </div>
                        <hr>
                        <h5 class="mb-3">Détails du salaire</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salaire de base *</label>
                                <input type="number" step="0.01" class="form-control" name="salaire_base" id="salaire_base" value="<?= $salaire['salaire_base'] ?? 0 ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prime</label>
                                <input type="number" step="0.01" class="form-control" name="prime" id="prime" value="<?= $salaire['prime'] ?? 0 ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Heures supplémentaires</label>
                                <input type="number" step="0.01" class="form-control" name="heures_supplementaires" id="heures_supplementaires" value="<?= $salaire['heures_supplementaires'] ?? 0 ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Montant heures sup</label>
                                <input type="number" step="0.01" class="form-control" name="montant_heures_sup" id="montant_heures_sup" value="<?= $salaire['montant_heures_sup'] ?? 0 ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Retenues</label>
                                <input type="number" step="0.01" class="form-control" name="retenues" id="retenues" value="<?= $salaire['retenues'] ?? 0 ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salaire net</label>
                                <input type="number" step="0.01" class="form-control" name="salaire_net" id="salaire_net" value="<?= $salaire['salaire_net'] ?? 0 ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date de paiement</label>
                            <input type="date" class="form-control" name="date_paiement" value="<?= $salaire['date_paiement'] ?? '' ?>">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Enregistrer
                            </button>
                            <a href="salaires.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    $mois_filter = $_GET['mois'] ?? date('n');
    $annee_filter = $_GET['annee'] ?? date('Y');
    
    $stmt = $db->prepare("SELECT s.*, e.matricule, e.nom, e.prenom FROM salaires s JOIN employes e ON s.employe_id = e.id WHERE s.mois = ? AND s.annee = ? ORDER BY s.date_creation DESC");
    $stmt->execute([$mois_filter, $annee_filter]);
    $salaires = $stmt->fetchAll();
    
    // Calcul du total
    $total_net = array_sum(array_column($salaires, 'salaire_net'));
    ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><i class="bi bi-cash-coin"></i> Gestion des Salaires</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="salaires.php?action=add" class="btn btn-primary">
                <i class="bi bi-cash-stack"></i> Ajouter un salaire
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Opération réussie !
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" name="mois">
                        <?php
                        $mois_noms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                        for ($i = 1; $i <= 12; $i++):
                        ?>
                            <option value="<?= $i ?>" <?= $mois_filter == $i ? 'selected' : '' ?>>
                                <?= $mois_noms[$i] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control" name="annee" value="<?= $annee_filter ?>" min="2020" max="2100">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-0">Total des salaires nets: <strong class="text-success"><?= number_format($total_net, 2, ',', ' ') ?> FCFA</strong></h5>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Période</th>
                            <th>Salaire base</th>
                            <th>Prime</th>
                            <th>Heures sup</th>
                            <th>Retenues</th>
                            <th>Salaire net</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salaires)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Aucun salaire pour cette période</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salaires as $sal): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sal['matricule'] . ' - ' . $sal['prenom'] . ' ' . $sal['nom']) ?></td>
                                    <td><?= $mois_noms[$sal['mois']] ?> <?= $sal['annee'] ?></td>
                                    <td><?= number_format($sal['salaire_base'], 2, ',', ' ') ?> FCFA</td>
                                    <td><?= number_format($sal['prime'], 2, ',', ' ') ?> FCFA</td>
                                    <td><?= number_format($sal['montant_heures_sup'], 2, ',', ' ') ?> FCFA</td>
                                    <td><?= number_format($sal['retenues'], 2, ',', ' ') ?> FCFA</td>
                                    <td><strong><?= number_format($sal['salaire_net'], 2, ',', ' ') ?> FCFA</strong></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'en_attente' => 'warning',
                                            'payé' => 'success',
                                            'annulé' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $badge_class[$sal['statut']] ?>">
                                            <?= ucfirst($sal['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="salaires.php?action=edit&id=<?= $sal['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="salaires.php?action=delete&id=<?= $sal['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce salaire ?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
require_once 'includes/footer.php';
?>
