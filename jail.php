<?php
// Data utworzenia: 2025-03-23 09:43:42
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();

// Pobierz historię więzienia
$stmt = $db->prepare("
    SELECT 
        reason,
        sentence_hours,
        bribe_amount,
        created_at,
        released_at
    FROM jail_logs
    WHERE character_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$character->getId()]);
$jail_logs = $stmt->fetchAll();

// Oblicz koszt łapówki
$remaining_time = 0;
if ($character->isInJail()) {
    $jail_until = new DateTime($character->getJailUntil());
    $now = new DateTime();
    $remaining_time = $jail_until->getTimestamp() - $now->getTimestamp();
}
$bribe_cost = ceil($remaining_time / 3600) * Config::JAIL_BRIBE_COST_PER_HOUR;
?>

<div class="container py-4">
    <div class="row">
        <!-- Status więzienia -->
        <div class="col-md-8">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Więzienie</h5>
                </div>
                <div class="card-body">
                    <?php if ($character->isInJail()): ?>
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">Jesteś w więzieniu!</h4>
                            <p>Pozostały czas: <?php echo gmdate("H:i:s", $remaining_time); ?></p>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p>Możesz przekupić strażnika, aby wyjść wcześniej.</p>
                                    <p>Koszt łapówki: $<?php echo number_format($bribe_cost); ?></p>
                                    <button class="btn btn-warning" 
                                            onclick="bribeGuard()"
                                            <?php echo $character->getCash() < $bribe_cost ? 'disabled' : ''; ?>>
                                        Daj łapówkę
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <p>Możesz też spróbować uciec, ale to ryzykowne...</p>
                                    <button class="btn btn-danger" onclick="attemptEscape()">
                                        Spróbuj ucieczki
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            Jesteś wolnym człowiekiem! Postaraj się, aby tak zostało...
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Historia więzienia -->
        <div class="col-md-4">
            <div class="card bg-dark text-light">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historia odsiadek</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush bg-dark">
                        <?php foreach ($jail_logs as $log): ?>
                            <div class="list-group-item bg-dark text-light border-light">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Powód: <?php echo htmlspecialchars($log['reason']); ?></h6>
                                    <small><?php echo date('Y-m-d', strtotime($log['created_at'])); ?></small>
                                </div>
                                <p class="mb-1">
                                    Wyrok: <?php echo $log['sentence_hours']; ?> godzin
                                    <?php if ($log['bribe_amount'] > 0): ?>
                                        <br>
                                        <span class="text-warning">
                                            Wyszedłeś za łapówkę: $<?php echo number_format($log['bribe_amount']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($log['released_at']): ?>
                                        <br>
                                        <small>
                                            Wypuszczony: <?php echo date('Y-m-d H:i', strtotime($log['released_at'])); ?>
                                        </small>
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
function bribeGuard() {
    if (!confirm('Czy na pewno chcesz dać łapówkę strażnikowi?')) {
        return;
    }

    fetch('actions/jail_bribe.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Strażnik przyjął łapówkę. Jesteś wolny!');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function attemptEscape() {
    if (!confirm('Próba ucieczki jest ryzykowna. Możesz dostać dodatkowy wyrok. Kontynuować?')) {
        return;
    }

    fetch('actions/jail_escape.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Udało ci się uciec!');
            location.reload();
        } else {
            alert(data.message);
            if (data.additional_sentence) {
                location.reload();
            }
        }
    });
}
</script>