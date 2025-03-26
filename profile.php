<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$character = new Character($_SESSION['character_id']);
$stats = $character->getStats();

// Pobierz historię walk
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT cl.*, 
           a.name as attacker_name,
           d.name as defender_name
    FROM combat_logs cl
    JOIN characters a ON cl.attacker_id = a.id
    JOIN characters d ON cl.defender_id = d.id
    WHERE cl.attacker_id = ? OR cl.defender_id = ?
    ORDER BY cl.timestamp DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['character_id'], $_SESSION['character_id']]);
$combat_history = $stmt->fetchAll();

// Pobierz statystyki napadów
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_robberies,
        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_robberies,
        SUM(CASE WHEN success = 1 THEN reward ELSE 0 END) as total_earnings
    FROM robbery_logs
    WHERE character_id = ?
");
$stmt->execute([$_SESSION['character_id']]);
$robbery_stats = $stmt->fetch();

// Pobierz osiągnięcia
$stmt = $db->prepare("
    SELECT a.*, ca.completed_at
    FROM achievements a
    JOIN character_achievements ca ON a.id = ca.achievement_id
    WHERE ca.character_id = ?
    ORDER BY ca.completed_at DESC
");
$stmt->execute([$_SESSION['character_id']]);
$achievements = $stmt->fetchAll();
?>

<div class="row">
    <!-- Statystyki postaci -->
    <div class="col-md-6 mb-4">
        <div class="card bg-dark border-light">
            <div class="card-header border-light">
                <h4>Profil: <?php echo htmlspecialchars($stats['name']); ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Poziom:</strong> <?php echo $stats['level']; ?></p>
                        <p><strong>Doświadczenie:</strong> <?php echo number_format($stats['experience']); ?></p>
                        <p><strong>Gotówka:</strong> $<?php echo number_format($stats['cash'], 2); ?></p>
                        <p><strong>Respekt:</strong> <?php echo number_format($stats['respect_points']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Siła:</strong> <?php echo $stats['strength']; ?></p>
                        <p><strong>Charyzma:</strong> <?php echo $stats['charisma']; ?></p>
                        <p><strong>Inteligencja:</strong> <?php echo $stats['intelligence']; ?></p>
                        <p><strong>Wytrzymałość:</strong> <?php echo $stats['endurance']; ?></p>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Statystyki napadów</h5>
                    <p><strong>Wykonane napady:</strong> <?php echo number_format($robbery_stats['total_robberies']); ?></p>
                    <p><strong>Udane napady:</strong> <?php echo number_format($robbery_stats['successful_robberies']); ?></p>
                    <p><strong>Zarobiona gotówka:</strong> $<?php echo number_format($robbery_stats['total_earnings'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Historia walk -->
    <div class="col-md-6 mb-4">
        <div class="card bg-dark border-light">
            <div class="card-header border-light">
                <h4>Historia walk</h4>
            </div>
            <div class="card-body">
                <div class="list-group bg-dark">
                    <?php foreach ($combat_history as $combat): ?>
                        <?php 
                        $isAttacker = $combat['attacker_id'] == $_SESSION['character_id'];
                        $won = ($isAttacker && $combat['result'] === 'win') || (!$isAttacker && $combat['result'] === 'loss');
                        ?>
                        <div class="list-group-item bg-dark text-light border-light">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">
                                    <?php if ($isAttacker): ?>
                                        Walka z <?php echo htmlspecialchars($combat['defender_name']); ?>
                                    <?php else: ?>
                                        Zaatakowany przez <?php echo htmlspecialchars($combat['attacker_name']); ?>
                                    <?php endif; ?>
                                </h5>
                                <small><?php echo date('Y-m-d H:i', strtotime($combat['timestamp'])); ?></small>
                            </div>
                            <p class="mb-1">
                                Wynik: <span class="badge bg-<?php echo $won ? 'success' : 'danger'; ?>">
                                    <?php echo $won ? 'Wygrana' : 'Przegrana'; ?>
                                </span>
                                <?php if ($combat['cash_stolen'] > 0): ?>
                                    <?php if ($isAttacker && $won): ?>
                                        - Zdobyto: $<?php echo number_format($combat['cash_stolen'], 2); ?>
                                    <?php elseif (!$isAttacker && !$won): ?>
                                        - Stracono: $<?php echo number_format($combat['cash_stolen'], 2); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Osiągnięcia -->
<div class="card bg-dark border-light mt-4">
    <div class="card-header border-light">
        <h4>Osiągnięcia</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($achievements as $achievement): ?>
                <div class="col-md-4 mb-3">
                    <div class="card bg-dark border-light">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($achievement['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($achievement['description']); ?></p>
                            <p class="card-text">
                                <small class="text-muted">
                                    Zdobyto: <?php echo date('Y-m-d H:i', strtotime($achievement['completed_at'])); ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <span class="badge bg-success">
                                    +$<?php echo number_format($achievement['reward_cash'], 2); ?>
                                </span>
                                <span class="badge bg-primary">
                                    +<?php echo number_format($achievement['reward_respect']); ?> respektu
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>