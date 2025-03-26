<?php
// Data utworzenia: 2025-03-23 09:30:39
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();

// Pobierz listę potencjalnych przeciwników
$stmt = $db->prepare("
    SELECT 
        c.*,
        COALESCE(g.name, 'Brak') as gang_name,
        TIMESTAMPDIFF(MINUTE, c.last_activity, NOW()) as minutes_idle
    FROM characters c
    LEFT JOIN gang_members gm ON c.id = gm.character_id
    LEFT JOIN gangs g ON gm.gang_id = g.id
    WHERE c.id != ? 
    AND c.level BETWEEN ? * 0.8 AND ? * 1.2
    AND c.in_jail = 0 
    AND c.in_hospital = 0
    ORDER BY RAND()
    LIMIT 10
");

$stmt->execute([
    $character->getId(),
    $character->getLevel(),
    $character->getLevel()
]);

$opponents = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Dostępni przeciwnicy</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Gracz</th>
                                    <th>Poziom</th>
                                    <th>Gang</th>
                                    <th>Status</th>
                                    <th>Akcja</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($opponents as $opponent): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($opponent['name']); ?></td>
                                        <td><?php echo $opponent['level']; ?></td>
                                        <td><?php echo htmlspecialchars($opponent['gang_name']); ?></td>
                                        <td>
                                            <?php if ($opponent['minutes_idle'] < 5): ?>
                                                <span class="text-success">Online</span>
                                            <?php elseif ($opponent['minutes_idle'] < 15): ?>
                                                <span class="text-warning">AFK</span>
                                            <?php else: ?>
                                                <span class="text-danger">Offline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" 
                                                    onclick="initiateFight(<?php echo $opponent['id']; ?>)"
                                                    <?php echo $character->getCurrentEnergy() < Config::MIN_FIGHT_ENERGY ? 'disabled' : ''; ?>>
                                                Atakuj (<?php echo Config::MIN_FIGHT_ENERGY; ?> energii)
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

        <div class="col-md-4">
            <div class="card bg-dark text-light mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Twoje statystyki bojowe</h5>
                </div>
                <div class="card-body">
                    <p>Energia: <?php echo $character->getCurrentEnergy(); ?>/<?php echo $character->getMaxEnergy(); ?></p>
                    <p>Zdrowie: <?php echo $character->getCurrentHealth(); ?>/<?php echo $character->getMaxHealth(); ?></p>
                    <p>Siła: <?php echo $character->getStrength(); ?></p>
                    <p>Obrona: <?php echo $character->getDefense(); ?></p>
                    <p>Wytrzymałość: <?php echo $character->getEndurance(); ?></p>
                </div>
            </div>

            <!-- Wyposażenie -->
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Twoje wyposażenie</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $db->prepare("
                        SELECT i.*, t.name as type_name
                        FROM character_items ci
                        JOIN items i ON ci.item_id = i.id
                        JOIN item_types t ON i.type_id = t.id
                        WHERE ci.character_id = ?
                    ");
                    $stmt->execute([$character->getId()]);
                    $equipment = $stmt->fetchAll();
                    ?>

                    <?php if (empty($equipment)): ?>
                        <p>Nie masz żadnego wyposażenia.</p>
                    <?php else: ?>
                        <?php foreach ($equipment as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <small class="text-muted">(<?php echo $item['type_name']; ?>)</small>
                                </div>
                                <?php if ($item['damage'] > 0): ?>
                                    <span class="badge bg-danger">+<?php echo $item['damage']; ?> obrażeń</span>
                                <?php endif; ?>
                                <?php if ($item['defense'] > 0): ?>
                                    <span class="badge bg-primary">+<?php echo $item['defense']; ?> obrony</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function initiateFight(opponentId) {
    if (!confirm('Czy na pewno chcesz zaatakować tego przeciwnika?')) {
        return;
    }

    fetch('actions/fight.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `opponent_id=${opponentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = `${data.message}\n\n`;
            message += `Zadane obrażenia: ${data.damage}\n`;
            message += `Otrzymane obrażenia: ${data.received_damage}\n`;
            if (data.money_stolen > 0) {
                message += `Ukradziona kasa: $${data.money_stolen}`;
            }
            alert(message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>