<?php
// Data utworzenia: 2025-03-23 12:13:22
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

// Inicjalizacja tablicy events jako pustej tablicy
$events = [];

// Pobierz ostatnie wydarzenia
$db = Database::getInstance();
try {
    $stmt = $db->prepare("
        (SELECT 
            'fight' as type,
            fl.created_at as time,
            CASE 
                WHEN fl.attacker_id = ? THEN 'Zaatakowałeś'
                ELSE 'Zostałeś zaatakowany przez'
            END as action,
            CASE 
                WHEN fl.attacker_id = ? THEN 
                    (SELECT name FROM characters WHERE id = fl.defender_id)
                ELSE 
                    (SELECT name FROM characters WHERE id = fl.attacker_id)
            END as opponent,
            fl.damage_dealt as damage,
            CASE 
                WHEN fl.winner_id = ? THEN 'Wygrałeś'
                ELSE 'Przegrałeś'
            END as result,
            fl.money_stolen as money
        FROM fight_logs fl 
        WHERE (fl.attacker_id = ? OR fl.defender_id = ?))

        UNION ALL

        (SELECT 
            'robbery' as type,
            rl.created_at as time,
            'Napad na' as action,
            loc.name as opponent,
            0 as damage,
            CASE WHEN rl.success = 1 THEN 'Udany' ELSE 'Nieudany' END as result,
            COALESCE(rl.cash_gained, 0) as money
        FROM robbery_logs rl
        JOIN robberies r ON rl.robbery_id = r.id
        JOIN robbery_locations loc ON r.location_id = loc.id
        WHERE rl.character_id = ?)

        UNION ALL

        (SELECT 
            'nightclub' as type,
            nv.visited_at as time,
            'Wizyta w' as action,
            n.name as opponent,
            0 as damage,
            CASE 
                WHEN nv.status = 'kicked_out' THEN 'Wyrzucony'
                WHEN nv.status = 'completed' THEN 'Zakończona'
                ELSE 'W trakcie'
            END as result,
            COALESCE(nv.total_cost, 0) as money
        FROM nightclub_visits nv
        JOIN nightclubs n ON nv.nightclub_id = n.id
        WHERE nv.character_id = ?)

        ORDER BY time DESC
        LIMIT 10
    ");

    $stmt->execute([
        $character->getId(),
        $character->getId(),
        $character->getId(),
        $character->getId(),
        $character->getId(),
        $character->getId(),
        $character->getId()
    ]);

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Logowanie błędu
    error_log("Database error: " . $e->getMessage());
    $events = [];
}

// Statystyki walk
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_fights,
            SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) as fights_won,
            SUM(damage_dealt) as total_damage,
            SUM(CASE WHEN winner_id = ? THEN money_stolen ELSE 0 END) as money_stolen
        FROM fight_logs 
        WHERE attacker_id = ? OR defender_id = ?
    ");
    $stmt->execute([
        $character->getId(),
        $character->getId(),
        $character->getId(),
        $character->getId()
    ]);
    $combat_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $combat_stats = [
        'total_fights' => 0,
        'fights_won' => 0,
        'total_damage' => 0,
        'money_stolen' => 0
    ];
}

// Statystyki napadów
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_robberies,
            SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_robberies,
            SUM(CASE WHEN success = 1 THEN cash_gained ELSE 0 END) as total_reward
        FROM robbery_logs 
        WHERE character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $robbery_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $robbery_stats = [
        'total_robberies' => 0,
        'successful_robberies' => 0,
        'total_reward' => 0
    ];
}

// Pobieranie statystyk postaci
try {
    $stmt = $db->prepare("
        SELECT 
            cs.strength,
            cs.agility,
            cs.endurance,
            cs.intelligence,
            cs.tolerance as charisma
        FROM character_stats cs
        WHERE cs.character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $character_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $character_stats = [
        'strength' => 0,
        'agility' => 0,
        'endurance' => 0,
        'intelligence' => 0,
        'charisma' => 0
    ];
}

// Pobieranie wartości obrony
$defense = $character->getDefense() ?? 0;
?>

<div class="container py-4">
    <div class="row">
        <!-- Statystyki -->
        <div class="col-md-4">
            <div class="card bg-dark text-light mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statystyki postaci</h5>
                </div>
                <div class="card-body">
                    <p>Poziom: <?php echo htmlspecialchars((string)$character->getLevel()); ?></p>
                    <p>Doświadczenie: <?php echo number_format($character->getExperience()); ?>/<?php echo number_format(($character->getLevel() * 1000)); ?></p>
                    <p>Respekt: <?php echo number_format($character->getRespectPoints()); ?></p>
                    <p>Siła: <?php echo htmlspecialchars((string)($character_stats['strength'] ?? 0)); ?></p>
                    <p>Obrona: <?php echo htmlspecialchars((string)$defense); ?></p>
                    <p>Inteligencja: <?php echo htmlspecialchars((string)($character_stats['intelligence'] ?? 0)); ?></p>
                    <p>Zwinność: <?php echo htmlspecialchars((string)($character_stats['agility'] ?? 0)); ?></p>
                    <p>Wytrzymałość: <?php echo htmlspecialchars((string)($character_stats['endurance'] ?? 0)); ?></p>
                    <p>Charyzma: <?php echo htmlspecialchars((string)($character_stats['charisma'] ?? 0)); ?></p>
                </div>
            </div>
        </div>

        <!-- Statystyki walk -->
        <div class="col-md-4">
            <div class="card bg-dark text-light mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statystyki walk</h5>
                </div>
                <div class="card-body">
                    <p>Stoczone walki: <?php echo number_format($combat_stats['total_fights'] ?? 0); ?></p>
                    <p>Wygrane walki: <?php echo number_format($combat_stats['fights_won'] ?? 0); ?></p>
                    <p>Zadane obrażenia: <?php echo number_format($combat_stats['total_damage'] ?? 0); ?></p>
                    <p>Ukradziona kasa: $<?php echo number_format($combat_stats['money_stolen'] ?? 0, 2); ?></p>
                    <p>Wykonane napady: <?php echo number_format($robbery_stats['total_robberies'] ?? 0); ?></p>
                    <p>Udane napady: <?php echo number_format($robbery_stats['successful_robberies'] ?? 0); ?></p>
                    <p>Łączny łup: $<?php echo number_format($robbery_stats['total_reward'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Ostatnie wydarzenia -->
        <div class="col-md-4">
            <div class="card bg-dark text-light mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ostatnie wydarzenia</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush bg-dark">
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <div class="list-group-item bg-dark text-light border-light">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars((string)$event['action']); ?> <?php echo htmlspecialchars((string)$event['opponent']); ?></h6>
                                        <small><?php echo date('H:i', strtotime($event['time'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars((string)$event['result']); ?></p>
                                    <?php if ($event['money'] > 0): ?>
                                        <small>$<?php echo number_format($event['money'], 2); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item bg-dark text-light border-light">
                                <p class="mb-0">Brak wydarzeń do wyświetlenia</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>