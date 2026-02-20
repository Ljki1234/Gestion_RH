<?php
$pageTitle = 'Tableau de bord';
require_once 'includes/header.php';
require_once 'config/database.php';

$db = getDB();

// Statistiques générales
$stats = [];

// Nombre total d'employés
$stmt = $db->query("SELECT COUNT(*) as total FROM employes WHERE statut = 'actif'");
$stats['employes_actifs'] = $stmt->fetch()['total'];

// Nombre total de départements
$stmt = $db->query("SELECT COUNT(*) as total FROM departements");
$stats['departements'] = $stmt->fetch()['total'];

// Congés en attente
$stmt = $db->query("SELECT COUNT(*) as total FROM conges WHERE statut = 'en_attente'");
$stats['conges_attente'] = $stmt->fetch()['total'];

// Salaires du mois en cours
$mois_actuel = date('n');
$annee_actuelle = date('Y');
$stmt = $db->prepare("SELECT COUNT(*) as total FROM salaires WHERE mois = ? AND annee = ?");
$stmt->execute([$mois_actuel, $annee_actuelle]);
$stats['salaires_mois'] = $stmt->fetch()['total'];

// Derniers employés ajoutés
$stmt = $db->query("SELECT e.*, d.nom as departement_nom FROM employes e LEFT JOIN departements d ON e.departement_id = d.id ORDER BY e.date_creation DESC LIMIT 5");
$derniers_employes = $stmt->fetchAll();

// Congés récents
$stmt = $db->query("SELECT c.*, e.nom, e.prenom FROM conges c JOIN employes e ON c.employe_id = e.id ORDER BY c.date_demande DESC LIMIT 5");
$conges_recents = $stmt->fetchAll();
?>
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0"><i class="bi bi-speedometer2"></i> Tableau de bord</h1>
    </div>
</div>

<!-- Cartes de statistiques -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Employés actifs</h6>
                        <h2 class="mb-0"><?= $stats['employes_actifs'] ?></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Départements</h6>
                        <h2 class="mb-0"><?= $stats['departements'] ?></h2>
                    </div>
                    <i class="bi bi-building fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Congés en attente</h6>
                        <h2 class="mb-0"><?= $stats['conges_attente'] ?></h2>
                    </div>
                    <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Salaires ce mois</h6>
                        <h2 class="mb-0"><?= $stats['salaires_mois'] ?></h2>
                    </div>
                    <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Derniers employés -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-people"></i> Derniers employés</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Poste</th>
                                <th>Département</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($derniers_employes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aucun employé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($derniers_employes as $emp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($emp['matricule']) ?></td>
                                        <td><?= htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']) ?></td>
                                        <td><?= htmlspecialchars($emp['poste']) ?></td>
                                        <td><?= htmlspecialchars($emp['departement_nom'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <a href="employes.php" class="btn btn-sm btn-primary">Voir tous les employés</a>
            </div>
        </div>
    </div>

    <!-- Congés récents -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Congés récents</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($conges_recents)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aucun congé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($conges_recents as $conge): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($conge['prenom'] . ' ' . $conge['nom']) ?></td>
                                        <td><?= htmlspecialchars($conge['type_conge']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($conge['date_debut'])) ?> - <?= date('d/m/Y', strtotime($conge['date_fin'])) ?></td>
                                        <td>
                                            <?php
                                            $badge_class = [
                                                'en_attente' => 'warning',
                                                'approuvé' => 'success',
                                                'refusé' => 'danger'
                                            ];
                                            $statut_text = [
                                                'en_attente' => 'En attente',
                                                'approuvé' => 'Approuvé',
                                                'refusé' => 'Refusé'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $badge_class[$conge['statut']] ?>">
                                                <?= $statut_text[$conge['statut']] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <a href="conges.php" class="btn btn-sm btn-warning">Voir tous les congés</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
