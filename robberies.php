<?php
// Data utworzenia: 2025-03-23 12:39:33
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();

// Pobierz dostępne napady
$stmt = $db->prepare("
    SELECT 
        r.*,
        loc.name,
        loc.min_level,
        loc.min_cash,
        loc.max_cash,
        loc.energy_cost,
        loc.success_chance,
        (
            SELECT COUNT(*) 
            FROM robbery_logs rl 
            WHERE rl.robbery_id = r.id 
            AND rl.character_id = ? 
            AND rl.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ) as attempts_last_hour
    FROM robberies r
    JOIN robbery_locations loc ON r.location_id = loc.id
    WHERE r.character_id = ?
    AND r.status = 'available'
    AND loc.min_level <= ?
    ORDER BY loc.min_level ASC
");
$stmt->execute([$character->getId(), $character->getId(), $character->getLevel()]);
$robberies = $stmt->fetchAll();

// Pobierz ostatnie napady gracza
$stmt = $db->prepare("
    SELECT 
        loc.name,
        rl.success,
        rl.cash_gained as reward,
        rl.created_at
    FROM robbery_logs rl
    JOIN robberies r ON rl.robbery_id = r.id
    JOIN robbery_locations loc ON r.location_id = loc.id
    WHERE rl.character_id = ?
    ORDER BY rl.created_at DESC
    LIMIT 10
");
$stmt->execute([$character->getId()]);
$robbery_logs = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <!-- Lista napadów -->
        <div class="col-md-8">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Dostępne napady</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($robberies as $robbery): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card bg-dark text-light border">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($robbery['name']); ?></h5>
                                        <p>Trudność: 
                                            <?php for ($i = 0; $i < 5 - floor($robbery['success_chance']/20); $i++): ?>
                                                ⭐
                                            <?php endfor; ?>
                                        </p>
                                        <p>Wymagany poziom: <?php echo $robbery['min_level']; ?></p>
                                        <p>Min. nagroda: $<?php echo number_format($robbery['min_cash']); ?></p>
                                        <p>Max. nagroda: $<?php echo number_format($robbery['max_cash']); ?></p>
                                        <p>Energia: <?php echo $robbery['energy_cost']; ?></p>
                                        <p>Szansa: <?php echo $robbery['success_chance']; ?>%</p>
                                        <?php
                                        $maxAttempts = defined('MAX_ROBBERY_ATTEMPTS') ? MAX_ROBBERY_ATTEMPTS : 3;
                                        ?>
                                        <p>Próby w ostatniej godzinie: <?php echo $robbery['attempts_last_hour']; ?>/<?php echo $maxAttempts; ?></p>
                                        
                                        <button class="btn btn-primary w-100" 
                                                onclick="performRobbery(<?php echo $robbery['id']; ?>)"
                                                <?php echo ($character->getCurrentEnergy() < $robbery['energy_cost'] || 
                                                          $robbery['attempts_last_hour'] >= $maxAttempts) ? 'disabled' : ''; ?>>
                                            Wykonaj napad
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historia napadów -->
        <div class="col-md-4">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historia napadów</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush bg-dark">
                        <?php foreach ($robbery_logs as $log): ?>
                            <div class="list-group-item bg-dark text-light border-light">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($log['name']); ?></h6>
                                    <small><?php echo date('H:i', strtotime($log['created_at'])); ?></small>
                                </div>
                                <p class="mb-1">
                                    <?php if ($log['success']): ?>
                                        <span class="text-success">Udany</span>
                                        <br>
                                        Łup: $<?php echo number_format($log['reward']); ?>
                                    <?php else: ?>
                                        <span class="text-danger">Nieudany</span>
                                    <?php endif; ?>
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
function performRobbery(robberyId) {
    fetch('actions/perform_robbery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `robbery_id=${robberyId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = data.message + '\n';
            if (data.reward > 0) {
                message += `\nZdobyłeś: $${data.reward}`;
            }
            if (data.experience > 0) {
                message += `\nZdobyte doświadczenie: ${data.experience}`;
            }
            alert(message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>