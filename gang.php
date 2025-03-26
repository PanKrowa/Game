<?php
// Data utworzenia: 2025-03-23 09:36:35
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();

// Sprawdź czy gracz należy do gangu
$stmt = $db->prepare("
    SELECT 
        g.*,
        gm.role,
        (
            SELECT COUNT(*) 
            FROM gang_members 
            WHERE gang_id = g.id
        ) as member_count,
        (
            SELECT SUM(respect_points) 
            FROM characters c
            JOIN gang_members gm ON c.id = gm.character_id
            WHERE gm.gang_id = g.id
        ) as total_respect
    FROM gangs g
    JOIN gang_members gm ON g.id = gm.gang_id
    WHERE gm.character_id = ?
");
$stmt->execute([$character->getId()]);
$gang = $stmt->fetch();

// Jeśli gracz nie należy do gangu, pobierz listę dostępnych gangów
if (!$gang) {
    $stmt = $db->prepare("
        SELECT 
            g.*,
            (
                SELECT COUNT(*) 
                FROM gang_members 
                WHERE gang_id = g.id
            ) as member_count,
            (
                SELECT SUM(respect_points) 
                FROM characters c
                JOIN gang_members gm ON c.id = gm.character_id
                WHERE gm.gang_id = g.id
            ) as total_respect
        FROM gangs g
        WHERE g.recruiting = 1
        ORDER BY total_respect DESC
    ");
    $stmt->execute();
    $available_gangs = $stmt->fetchAll();
}

// Jeśli gracz jest w gangu, pobierz listę członków
if ($gang) {
    $stmt = $db->prepare("
        SELECT 
            c.name,
            c.level,
            c.respect_points,
            gm.role,
            TIMESTAMPDIFF(MINUTE, c.last_activity, NOW()) as minutes_idle
        FROM gang_members gm
        JOIN characters c ON gm.character_id = c.id
        WHERE gm.gang_id = ?
        ORDER BY gm.role DESC, c.respect_points DESC
    ");
    $stmt->execute([$gang['id']]);
    $gang_members = $stmt->fetchAll();

    // Pobierz logi gangu
    $stmt = $db->prepare("
        SELECT 
            gl.*,
            c.name as character_name
        FROM gang_logs gl
        LEFT JOIN characters c ON gl.character_id = c.id
        WHERE gl.gang_id = ?
        ORDER BY gl.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$gang['id']]);
    $gang_logs = $stmt->fetchAll();
}
?>

<div class="container py-4">
    <?php if ($gang): ?>
        <!-- Widok dla członka gangu -->
        <div class="row">
            <div class="col-md-8">
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($gang['name']); ?></h5>
                        <?php if ($gang['role'] === 'leader' || $gang['role'] === 'officer'): ?>
                            <button class="btn btn-primary btn-sm" onclick="toggleRecruiting(<?php echo $gang['id']; ?>)">
                                <?php echo $gang['recruiting'] ? 'Zakończ rekrutację' : 'Rozpocznij rekrutację'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p>Założony: <?php echo date('Y-m-d', strtotime($gang['created_at'])); ?></p>
                                <p>Członkowie: <?php echo $gang['member_count']; ?>/<?php echo Config::MAX_GANG_MEMBERS; ?></p>
                                <p>Łączny respekt: <?php echo number_format($gang['total_respect']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p>Twoja rola: <?php echo ucfirst($gang['role']); ?></p>
                                <p>Stan kasy: $<?php echo number_format($gang['bank']); ?></p>
                                <?php if ($gang['role'] === 'leader'): ?>
                                    <button class="btn btn-danger btn-sm" onclick="disbandGang(<?php echo $gang['id']; ?>)">
                                        Rozwiąż gang
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" onclick="leaveGang()">
                                        Opuść gang
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Lista członków -->
                        <h6>Członkowie gangu</h6>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Gracz</th>
                                        <th>Poziom</th>
                                        <th>Respekt</th>
                                        <th>Rola</th>
                                        <th>Status</th>
                                        <?php if ($gang['role'] === 'leader'): ?>
                                            <th>Akcje</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gang_members as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                                            <td><?php echo $member['level']; ?></td>
                                            <td><?php echo number_format($member['respect_points']); ?></td>
                                            <td><?php echo ucfirst($member['role']); ?></td>
                                            <td>
                                                <?php if ($member['minutes_idle'] < 5): ?>
                                                    <span class="text-success">Online</span>
                                                <?php elseif ($member['minutes_idle'] < 15): ?>
                                                    <span class="text-warning">AFK</span>
                                                <?php else: ?>
                                                    <span class="text-danger">Offline</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($gang['role'] === 'leader' && $member['role'] !== 'leader'): ?>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($member['role'] === 'member'): ?>
                                                            <button class="btn btn-primary" 
                                                                    onclick="promoteToOfficer('<?php echo $member['name']; ?>')">
                                                                Awansuj
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-danger" 
                                                                onclick="kickMember('<?php echo $member['name']; ?>')">
                                                            Wyrzuć
                                                        </button>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logi gangu -->
            <div class="col-md-4">
                <div class="card bg-dark text-light">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Historia gangu</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush bg-dark">
                            <?php foreach ($gang_logs as $log): ?>
                                <div class="list-group-item bg-dark text-light border-light">
                                    <div class="d-flex w-100 justify-content-between">
                                        <small><?php echo date('H:i', strtotime($log['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <?php 
                                        echo $log['character_name'] ? 
                                            htmlspecialchars($log['character_name']) . ' ' : '';
                                        echo htmlspecialchars($log['message']); 
                                        ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Widok dla gracza bez gangu -->
        <div class="row">
            <!-- Lista dostępnych gangów -->
            <div class="col-md-8">
                <div class="card bg-dark text-light">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Dostępne gangi</h5>
                        <?php if ($character->getLevel() >= Config::MIN_GANG_LEVEL): ?>
                            <button class="btn btn-primary" 
                                    onclick="createGang()"
                                    <?php echo $character->getCash() < Config::GANG_CREATE_COST ? 'disabled' : ''; ?>>
                                Załóż gang ($<?php echo number_format(Config::GANG_CREATE_COST); ?>)
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($character->getLevel() < Config::MIN_GANG_LEVEL): ?>
                            <div class="alert alert-warning">
                                Musisz osiągnąć <?php echo Config::MIN_GANG_LEVEL; ?> poziom, aby dołączyć do gangu.
                            </div>
                        <?php elseif (empty($available_gangs)): ?>
                            <p>Aktualnie żaden gang nie prowadzi rekrutacji.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nazwa</th>
                                            <th>Członkowie</th>
                                            <th>Respekt</th>
                                            <th>Założony</th>
                                            <th>Akcja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($available_gangs as $gang): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($gang['name']); ?></td>
                                                <td><?php echo $gang['member_count']; ?>/<?php echo Config::MAX_GANG_MEMBERS; ?></td>
                                                <td><?php echo number_format($gang['total_respect']); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($gang['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" 
                                                            onclick="applyToGang(<?php echo $gang['id']; ?>)">
                                                        Aplikuj
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Funkcje dla członków gangu
function toggleRecruiting(gangId) {
    fetch('actions/toggle_gang_recruiting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `gang_id=${gangId}`
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

function disbandGang(gangId) {
    if (!confirm('Czy na pewno chcesz rozwiązać gang? Tej operacji nie można cofnąć!')) {
        return;
    }

    fetch('actions/disband_gang.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `gang_id=${gangId}`
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

function leaveGang() {
    if (!confirm('Czy na pewno chcesz opuścić gang?')) {
        return;
    }

    fetch('actions/leave_gang.php', {
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

function promoteToOfficer(memberName) {
    fetch('actions/promote_gang_member.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `member_name=${memberName}`
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

function kickMember(memberName) {
    if (!confirm(`Czy na pewno chcesz wyrzucić ${memberName} z gangu?`)) {
        return;
    }

    fetch('actions/kick_gang_member.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `member_name=${memberName}`
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

// Funkcje dla graczy bez gangu
function createGang() {
    const gangName = prompt('Podaj nazwę gangu (3-20 znaków):');
    if (!gangName) return;

    fetch('actions/create_gang.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `name=${encodeURIComponent(gangName)}`
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

function applyToGang(gangId) {
    fetch('actions/send_gang_application.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `gang_id=${gangId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Aplikacja została wysłana!');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>