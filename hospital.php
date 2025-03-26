<?php
// Data utworzenia: 2025-03-23 09:42:27
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();

// Pobierz historię leczenia
$stmt = $db->prepare("
    SELECT 
        type,
        health_restored,
        cost,
        created_at
    FROM hospital_logs
    WHERE character_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$character->getId()]);
$hospital_logs = $stmt->fetchAll();

// Oblicz koszt leczenia
$healing_cost = (($character->getMaxHealth() - $character->getCurrentHealth()) * Config::HOSPITAL_HEAL_COST);

// Sprawdź czy postać jest uzależniona
$stmt = $db->prepare("
    SELECT tolerance 
    FROM character_stats 
    WHERE character_id = ?
");
$stmt->execute([$character->getId()]);
$stats = $stmt->fetch();
$is_addicted = $stats['tolerance'] >= 100;
?>

<div class="container py-4">
    <div class="row">
        <!-- Opcje leczenia -->
        <div class="col-md-8">
            <div class="card bg-dark text-light mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Szpital miejski</h5>
                </div>
                <div class="card-body">
                    <?php if ($character->isInHospital()): ?>
                        <div class="alert alert-info">
                            Jesteś w szpitalu do: <?php echo date('Y-m-d H:i:s', strtotime($character->getHospitalUntil())); ?>
                        </div>
                        <button class="btn btn-primary" onclick="leaveHospital()">
                            Opuść szpital
                        </button>
                    <?php else: ?>
                        <div class="row">
                            <!-- Leczenie -->
                            <div class="col-md-6 mb-4">
                                <div class="card bg-dark text-light border">
                                    <div class="card-body">
                                        <h5 class="card-title">Leczenie</h5>
                                        <p>Aktualne zdrowie: <?php echo $character->getCurrentHealth(); ?>/<?php echo $character->getMaxHealth(); ?></p>
                                        <p>Koszt: $<?php echo number_format($healing_cost); ?></p>
                                        <button class="btn btn-primary w-100" 
                                                onclick="heal()"
                                                <?php echo ($character->getCurrentHealth() >= $character->getMaxHealth() || 
                                                          $character->getCash() < $healing_cost) ? 'disabled' : ''; ?>>
                                            Wylecz się
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Detoks -->
                            <div class="col-md-6 mb-4">
                                <div class="card bg-dark text-light border">
                                    <div class="card-body">
                                        <h5 class="card-title">Detoks</h5>
                                        <p>Tolerancja: <?php echo $stats['tolerance']; ?>%</p>
                                        <p>Koszt: $<?php echo number_format(Config::DETOX_COST); ?></p>
                                        <button class="btn btn-warning w-100" 
                                                onclick="detox()"
                                                <?php echo (!$is_addicted || 
                                                          $character->getCash() < Config::DETOX_COST) ? 'disabled' : ''; ?>>
                                            Przejdź detoks
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Operacja plastyczna -->
                            <div class="col-md-6 mb-4">
                                <div class="card bg-dark text-light border">
                                    <div class="card-body">
                                        <h5 class="card-title">Operacja plastyczna</h5>
                                        <p>Zmień swój wygląd i ucieknij przed wrogami!</p>
                                        <p>Koszt: $<?php echo number_format(Config::PLASTIC_SURGERY_COST); ?></p>
                                        <button class="btn btn-danger w-100" 
                                                onclick="plasticSurgery()"
                                                <?php echo $character->getCash() < Config::PLASTIC_SURGERY_COST ? 'disabled' : ''; ?>>
                                            Zmień wygląd
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Historia leczenia -->
        <div class="col-md-4">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historia leczenia</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush bg-dark">
                        <?php foreach ($hospital_logs as $log): ?>
                            <div class="list-group-item bg-dark text-light border-light">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <?php 
                                        switch ($log['type']) {
                                            case 'heal':
                                                echo 'Leczenie';
                                                break;
                                            case 'detox':
                                                echo 'Detoks';
                                                break;
                                            case 'plastic_surgery':
                                                echo 'Operacja plastyczna';
                                                break;
                                        }
                                        ?>
                                    </h6>
                                    <small><?php echo date('H:i', strtotime($log['created_at'])); ?></small>
                                </div>
                                <p class="mb-1">
                                    <?php if ($log['health_restored'] > 0): ?>
                                        Przywrócone zdrowie: <?php echo $log['health_restored']; ?>
                                        <br>
                                    <?php endif; ?>
                                    Koszt: $<?php echo number_format($log['cost']); ?>
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
function heal() {
    fetch('actions/hospital_heal.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Wyleczono ${data.health_restored} punktów zdrowia za $${number_format(data.cost)}!`);
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function detox() {
    if (!confirm('Czy na pewno chcesz przejść detoks? To będzie bolesne...')) {
        return;
    }

    fetch('actions/hospital_detox.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Detoks zakończony pomyślnie! Twoja tolerancja została zresetowana.');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function plasticSurgery() {
    const newName = prompt('Podaj nową nazwę postaci (3-20 znaków):');
    if (!newName) return;

    fetch('actions/hospital_plastic_surgery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `new_name=${encodeURIComponent(newName)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Operacja zakończona sukcesem! Twoja tożsamość została zmieniona.');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function leaveHospital() {
    fetch('actions/leave_hospital.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
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