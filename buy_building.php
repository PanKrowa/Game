<?php
// Data utworzenia: 2025-03-23 09:46:36 
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

$type_id = $_POST['type_id'] ?? 0;
if (!$type_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie wybrano typu budynku.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Pobierz dane typu budynku
    $stmt = $db->prepare("
        SELECT * FROM building_types WHERE id = ?
    ");
    $stmt->execute([$type_id]);
    $building_type = $stmt->fetch();

    if (!$building_type) {
        throw new Exception('Ten typ budynku nie istnieje.');
    }

    // Sprawdź wymagania
    if ($character->getLevel() < $building_type['level_required']) {
        throw new Exception('Masz za niski poziom na ten budynek.');
    }

    if ($character->getCash() < $building_type['base_cost']) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }

    // Sprawdź limit budynków tego typu
    $stmt = $db->prepare("
        SELECT COUNT(*) as owned
        FROM buildings
        WHERE character_id = ? AND type_id = ?
    ");
    $stmt->execute([$character->getId(), $type_id]);
    $owned = $stmt->fetch();

    if ($owned['owned'] >= $building_type['max_owned']) {
        throw new Exception('Osiągnąłeś limit budynków tego typu.');
    }

    // Kup budynek
    $character->subtractCash($building_type['base_cost']);

    $stmt = $db->prepare("
        INSERT INTO buildings (
            character_id, type_id, name, level,
            last_collection, created_at
        ) VALUES (?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $type_id,
        $building_type['name'] . ' #' . ($owned['owned'] + 1)
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Kupiłeś nowy budynek!'
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