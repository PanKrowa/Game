<?php
// Data utworzenia: 2025-03-23 13:15:09
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();

// Pobierz ekwipunek (broń i pancerz)
$stmt = $db->prepare("
    SELECT 
        e.*,
        ce.equipped
    FROM equipment e
    LEFT JOIN character_equipment ce ON e.id = ce.equipment_id AND ce.character_id = ?
    WHERE e.level_required <= ?
    ORDER BY e.level_required ASC, e.price ASC
");
$stmt->execute([$character->getId(), $character->getLevel()]);
$equipment = $stmt->fetchAll();

// Pobierz prostytutki
$stmt = $db->prepare("
    SELECT 
        p.*,
        (
            SELECT COUNT(*) 
            FROM character_prostitutes cp 
            WHERE cp.prostitute_id = p.id 
            AND cp.character_id = ?
        ) as owned
    FROM prostitutes p
    WHERE p.level_required <= ?
    ORDER BY p.level_required ASC, p.price ASC
");
$stmt->execute([$character->getId(), $character->getLevel()]);
$prostitutes = $stmt->fetchAll();

// Pobierz narkotyki
$stmt = $db->prepare("
    SELECT 
        d.*,
        cd.quantity as owned_quantity
    FROM drugs d
    LEFT JOIN character_drugs cd ON d.id = cd.drug_id AND cd.character_id = ?
    WHERE d.level_required <= ?
    ORDER BY d.level_required ASC, d.dealer_price ASC
");
$stmt->execute([$character->getId(), $character->getLevel()]);
$drugs = $stmt->fetchAll();

// Pobierz historię transakcji
$stmt = $db->prepare("
    SELECT 
        'equipment' as type,
        e.name as item_name,
        e.price as price,
        ce.created_at
    FROM character_equipment ce
    JOIN equipment e ON ce.equipment_id = e.id
    WHERE ce.character_id = ?
    
    UNION ALL
    
    SELECT 
        'prostitute' as type,
        p.name as item_name,
        p.price as price,
        cp.created_at
    FROM character_prostitutes cp
    JOIN prostitutes p ON cp.prostitute_id = p.id
    WHERE cp.character_id = ?
    
    UNION ALL
    
    SELECT 
        'drug' as type,
        d.name as item_name,
        d.dealer_price as price,
        cd.created_at
    FROM character_drugs cd
    JOIN drugs d ON cd.drug_id = d.id
    WHERE cd.character_id = ?
    
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$character->getId(), $character->getId(), $character->getId()]);
$transactions = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <!-- Zakładki dla różnych sprzedawców -->
        <div class="col-md-8">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active text-light" data-bs-toggle="tab" href="#weapons" role="tab">Handlarz bronią</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" data-bs-toggle="tab" href="#pimp" role="tab">Alfons</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" data-bs-toggle="tab" href="#dealer" role="tab">Diler</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Handlarz bronią -->
                        <div class="tab-pane fade show active" id="weapons" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Przedmiot</th>
                                            <th>Typ</th>
                                            <th>Statystyki</th>
                                            <th>Cena</th>
                                            <th>Status</th>
                                            <th>Akcja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($equipment as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo ucfirst($item['type']); ?></td>
                                                <td>
                                                    <?php if ($item['attack'] > 0): ?>
                                                        <span class="badge bg-danger">+<?php echo $item['attack']; ?> atak</span>
                                                    <?php endif; ?>
                                                    <?php if ($item['defense'] > 0): ?>
                                                        <span class="badge bg-primary">+<?php echo $item['defense']; ?> obrona</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>$<?php echo number_format($item['price']); ?></td>
                                                <td>
                                                    <?php if ($item['equipped']): ?>
                                                        <span class="badge bg-success">Założone</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$item['equipped']): ?>
                                                        <button class="btn btn-primary btn-sm" 
                                                                onclick="buyEquipment(<?php echo $item['id']; ?>)"
                                                                <?php echo $character->getCash() < $item['price'] ? 'disabled' : ''; ?>>
                                                            Kup
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-warning btn-sm" 
                                                                onclick="unequipItem(<?php echo $item['id']; ?>)">
                                                            Zdejmij
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Alfons -->
                        <div class="tab-pane fade" id="pimp" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nazwa</th>
                                            <th>Opis</th>
                                            <th>Zarobek/h</th>
                                            <th>Cena</th>
                                            <th>Status</th>
                                            <th>Akcja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prostitutes as $prostitute): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($prostitute['name']); ?></td>
                                                <td><?php echo htmlspecialchars($prostitute['description']); ?></td>
                                                <td>$<?php echo number_format($prostitute['hourly_income']); ?></td>
                                                <td>$<?php echo number_format($prostitute['price']); ?></td>
                                                <td>
                                                    <?php if ($prostitute['owned']): ?>
                                                        <span class="badge bg-success">Posiadana</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$prostitute['owned']): ?>
                                                        <button class="btn btn-primary btn-sm" 
                                                                onclick="buyProstitute(<?php echo $prostitute['id']; ?>)"
                                                                <?php echo $character->getCash() < $prostitute['price'] ? 'disabled' : ''; ?>>
                                                            Kup
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Diler -->
                        <div class="tab-pane fade" id="dealer" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nazwa</th>
                                            <th>Opis</th>
                                            <th>Energia</th>
                                            <th>Uzależnienie</th>
                                            <th>Cena</th>
                                            <th>Posiadane</th>
                                            <th>Akcja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($drugs as $drug): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($drug['name']); ?></td>
                                                <td><?php echo htmlspecialchars($drug['description']); ?></td>
                                                <td>+<?php echo $drug['energy_boost']; ?></td>
                                                <td><?php echo $drug['addiction_rate']; ?>%</td>
                                                <td>$<?php echo number_format($drug['dealer_price']); ?></td>
                                                <td><?php echo number_format($drug['owned_quantity'] ?? 0); ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" 
                                                            onclick="buyDrug(<?php echo $drug['id']; ?>)"
                                                            <?php echo $character->getCash() < $drug['dealer_price'] ? 'disabled' : ''; ?>>
                                                        Kup
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historia transakcji -->
        <div class="col-md-4">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historia transakcji</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush bg-dark">
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="list-group-item bg-dark text-light border-light">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($transaction['item_name']); ?></h6>
                                    <small><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                </div>
                                <p class="mb-1">
                                    <?php 
                                    switch($transaction['type']) {
                                        case 'equipment':
                                            echo 'Zakup ekwipunku';
                                            break;
                                        case 'prostitute':
                                            echo 'Zakup prostytutki';
                                            break;
                                        case 'drug':
                                            echo 'Zakup narkotyków';
                                            break;
                                    }
                                    ?>
                                </p>
                                <small>
                                    Cena: $<?php echo number_format($transaction['price']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function buyEquipment(equipmentId) {
    if (!confirm('Czy na pewno chcesz kupić ten przedmiot?')) return;

    fetch('actions/buy_equipment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `equipment_id=${equipmentId}`
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

function buyProstitute(prostituteId) {
    if (!confirm('Czy na pewno chcesz kupić tę prostytutkę?')) return;

    fetch('actions/buy_prostitute.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `prostitute_id=${prostituteId}`
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

function buyDrug(drugId) {
    const quantity = prompt('Podaj ilość:', '1');
    if (!quantity) return;

    fetch('actions/buy_drug.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `drug_id=${drugId}&quantity=${quantity}`
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

function unequipItem(equipmentId) {
    fetch('actions/unequip_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `equipment_id=${equipmentId}`
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

function number_format(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>