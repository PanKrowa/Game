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

$name = trim($_POST['name'] ?? '');

if (strlen($name) < 3 || strlen($name) > 20) {
    die(json_encode([
        'success' => false,
        'message' => 'Nazwa gangu musi mieć od 3 do 20 znaków.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Sprawdź wymagania
    if ($character->getLevel() < Config::MIN_GANG_LEVEL) {
        throw new Exception('Masz za niski poziom aby założyć gang.');
    }

    if ($character->getCash() < Config::GANG_CREATE_COST) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }

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

    // Sprawdź czy nazwa nie jest już zajęta
    $stmt = $db->prepare("
        SELECT COUNT(*) as name_taken
        FROM gangs
        WHERE name = ?
    ");
    $stmt->execute([$name]);
    $name_check = $stmt->fetch();

    if ($name_check['name_taken'] > 0) {
        throw new Exception('Ta nazwa jest już zajęta.');
    }

    // Utwórz gang
    $character->subtractCash(Config::GANG_CREATE_COST);

    $stmt = $db->prepare("
        INSERT INTO gangs (
            name, bank, recruiting, created_at
        ) VALUES (?, 0, 1, NOW())
    ");
    $stmt->execute([$name]);
    $gang_id = $db->lastInsertId();

    // Dodaj założyciela jako lidera
    $stmt = $db->prepare("
        INSERT INTO gang_members (
            gang_id, character_id, role,
            joined_at
        ) VALUES (?, ?, 'leader', NOW())
    ");
    $stmt->execute([$gang_id, $character->getId()]);

    // Zapisz log gangu
    $stmt = $db->prepare("
        INSERT INTO gang_logs (
            gang_id, character_id, message,
            created_at
        ) VALUES (?, ?, 'założył gang', NOW())
    ");
    $stmt->execute([$gang_id, $character->getId()]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Gang został utworzony!'
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