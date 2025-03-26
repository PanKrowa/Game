<?php
// Data utworzenia: 2025-03-23 09:50:25
// Autor: PanKrowa

require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Character.php';

session_start();

if (!isset($_SESSION['character_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie jesteś zalogowany.'
    ]));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Nieprawidłowa metoda żądania.'
    ]));
}

$member_name = $_POST['member_name'] ?? '';

if (!$member_name) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie wybrano członka gangu.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Sprawdź czy gracz jest liderem gangu
    $stmt = $db->prepare("
        SELECT 
            gm.gang_id,
            gm.role
        FROM gang_members gm
        WHERE gm.character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $leader_check = $stmt->fetch();

    if (!$leader_check || $leader_check['role'] !== 'leader') {
        throw new Exception('Nie jesteś liderem gangu.');
    }

    // Pobierz dane awansowanego członka
    $stmt = $db->prepare("
        SELECT 
            c.id,
            gm.role
        FROM characters c
        JOIN gang_members gm ON c.id = gm.character_id
        WHERE c.name = ? AND gm.gang_id = ?
    ");
    $stmt->execute([$member_name, $leader_check['gang_id']]);
    $member = $stmt->fetch();

    if (!$member) {
        throw new Exception('Ten gracz nie należy do twojego gangu.');
    }

    if ($member['role'] !== 'member') {
        throw new Exception('Ten członek ma już wyższą rangę.');
    }

    // Awansuj członka na oficera
    $stmt = $db->prepare("
        UPDATE gang_members 
        SET role = 'officer'
        WHERE character_id = ? AND gang_id = ?
    ");
    $stmt->execute([$member['id'], $leader_check['gang_id']]);

    // Zapisz log gangu
    $stmt = $db->prepare("
        INSERT INTO gang_logs (
            gang_id, character_id, message,
            created_at
        ) VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $leader_check['gang_id'],
        $character->getId(),
        "awansował {$member_name} na oficera"
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "{$member_name} został awansowany na oficera!"
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}