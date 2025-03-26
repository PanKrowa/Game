<?php
// Data utworzenia: 2025-03-23 09:35:33
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();

// Pobierz budynki gracza
$stmt = $db->prepare("
    SELECT 
        b.*,
        bt.name as type_name,
        bt.production_time,
        bt.production_amount,
        bt.upgrade_cost_multiplier,
        TIMESTAMPDIFF(SECOND, b.last_collection, NOW()) as seconds_since_collection
    FROM buildings b
    JOIN building_types bt ON b.type_id = bt.id
    WHERE b.character_id = ?
    ORDER BY b.level DESC
");
$stmt->execute([$character->getId()]);
$owned_buildings = $stmt->fetchAll();

// Pobierz dostępne typy budynków do kupienia
$stmt = $db->prepare("
    SELECT 
        bt.*,
        (
            SELECT COUNT(*) 
            FROM buildings b 
            WHERE b.character_id = ? 
            AND b.type_id = bt.id
        ) as owned_count
    FROM building_types bt
    WHERE bt.level_required <= ?
    ORDER BY bt.base_cost ASC
");
$stmt->execute([$character->getId(), $character->getLevel()]);
$available_buildings = $stmt->fetchAll();

// Pobierz historię produkcji
$stmt = $db->prepare("
    SELECT 
        b.name,
        bp.amount,
        bp.collected_at
    FROM building_production bp
    JOIN buildings b ON bp.building_id = b.id
    WHERE b.character_id = ?
    ORDER BY bp.collected_at DESC
    LIMIT 10
");
$stmt->execute([$character->getId()]);
$production_logs = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <!-- Posiadane budynki -->
        <div class="col-md-8">
            <div class="card bg-dark text-light mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Twoje budynki</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($owned_buildings)): ?>
                        <p>Nie posiadasz jeszcze żadnych budynków.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($owned_buildings as $building): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card bg-dark text-light border">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($building['name']); ?>
                                                <span class="badge bg-secondary">Poziom <?php echo $building['level']; ?></span>
                                            </h5>
                                            <p class="text-muted"><?php echo htmlspecialchars($building['type_name']); ?></p>

                                            <?php
												// ...

												$production_cycles = 0;
												$available_production = 0;	
												$next_production_in = 0;

												if ($building['production_time'] > 0) {
													$production_cycles = floor($building['seconds_since_collection'] / $building['production_time']);
													$available_production = $production_cycles * ($building['production_amount'] * $building['level']);
													$next_production_in = $building['production_time'] - ($building['seconds_since_collection'] % $building['production_time']);
												}

												?>

                                            <p>Dostępna produkcja: <?php echo $available_production; ?> g</p>
                                            <p>Następna produkcja za: <?php echo gmdate("i:s", $next_production_in); ?></p>

                                            <div class="btn-group w-100">
                                                <?php if ($available_production > 0): ?>
                                                    <button class="btn btn-success" 
                                                            onclick="collectProduction(<?php echo $building['id']; ?>)">
                                                        Zbierz produkcję
                                                    </button>
                                                <?php endif; ?>

                                                <button class="btn btn-primary" 
                                                        onclick="upgradeBuilding(<?php echo $building['id']; ?>)"
                                                        <?php echo $character->getCash() < ($building['level'] * $building['upgrade_cost_multiplier']) ? 'disabled' : ''; ?>>
                                                    Ulepsz ($<?php echo number_format($building['level'] * $building['upgrade_cost_multiplier']); ?>)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dostępne budynki -->
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Dostępne budynki</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($available_buildings as $type): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card bg-dark text-light border">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($type['name']); ?></h5>
                                        <p class="text-muted"><?php echo htmlspecialchars($type['description']); ?></p>
                                        
                                        <p>Koszt: $<?php echo number_format($type['base_cost']); ?></p>
                                        <p>Produkcja: <?php echo $type['production_amount']; ?> g/<?php echo gmdate("i:s", $type['production_time']); ?></p>
                                        <p>Wymagany poziom: <?php echo $type['level_required']; ?></p>
                                        <p>Posiadane: <?php echo $type['owned_count']; ?>/<?php echo $type['max_owned']; ?></p>

                                        <button class="btn btn-primary w-100" 
                                                onclick="buyBuilding(<?php echo $type['id']; ?>)"
                                                <?php echo ($character->getCash() < $type['base_cost'] || 
                                                          $type['owned_count'] >= $type['max_owned']) ? 'disabled' : ''; ?>>
                                            Kup budynek
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historia produkcji -->
        <div class="col-md-4">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historia produkcji</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush bg-dark">
                        <?php foreach ($production_logs as $log): ?>
                            <div class="list-group-item bg-dark text-light border-light">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($log['name']); ?></h6>
                                    <small><?php echo date('H:i', strtotime($log['collected_at'])); ?></small>
                                </div>
                                <p class="mb-1">
                                    Zebrano: <?php echo $log['amount']; ?> g
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function buyBuilding(typeId) {
    fetch('actions/buy_building.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `type_id=${typeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function upgradeBuilding(buildingId) {
    fetch('actions/upgrade_building.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `building_id=${buildingId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function collectProduction(buildingId) {
    fetch('actions/collect_drugs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `building_id=${buildingId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Zebrano ${data.amount} g narkotyków!`);
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>