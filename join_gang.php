<?php
// Data utworzenia: 2025-03-23 09:54:24
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

$gang_id = $_POST['gang_id'] ?? 0;

if (!$gang_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie wybrano gangu.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Sprawdź czy gracz nie należy już do gangu
    $stmt = $db->prepare("
        SELECT COUNT(*) as gang_member
        FROM gang_members
        WHERE character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $is_member = $stmt->fetch();

    if ($is_member['gang_member'] > 0) {
        throw new Exception('Należysz już do gangu.');
    }

    // Sprawdź czy gang rekrutuje
    $stmt = $db->prepare("
        SELECT recruiting, member_count
        FROM gangs
        WHERE id = ?
    ");
    $stmt->execute([$gang_id]);
    $gang = $stmt->fetch();

    if (!$gang) {
        throw new Exception('Ten gang nie istnieje.');
    }

    if (!$gang['recruiting']) {
        throw new Exception('Ten gang obecnie nie rekrutuje.');
    }

    if ($gang['member_count'] >= Config::MAX_GANG_MEMBERS) {
        throw new Exception('Ten gang osiągnął maksymalną liczbę członków.');
    }

    // Dołącz do gangu
    $stmt = $db->prepare("
        INSERT INTO gang_members (
            gang_id, character_id, role,
            joined_at
        ) VALUES (?, ?, 'member', NOW())
    ");
    $stmt->execute([$gang_id, $character->getId()]);

    // Aktualizuj licznik członków
    $stmt = $db->prepare("
        UPDATE gangs 
        SET member_count = member_count + 1
        WHERE id = ?
    ");
    $stmt->execute([$gang_id]);

    // Zapisz log gangu
    $stmt = $db->prepare("
        INSERT INTO gang_logs (
            gang_id, character_id, message,
            created_at
        ) VALUES (?, ?, 'dołączył do gangu', NOW())
    ");
    $stmt->execute([
        $gang_id,
        $character->getId()
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Dołączyłeś do gangu!'
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