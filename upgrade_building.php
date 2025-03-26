<?php
// Data utworzenia: 2025-03-23 09:48:19
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

$building_id = $_POST['building_id'] ?? 0;
if (!$building_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie wybrano budynku.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Pobierz dane budynku
    $stmt = $db->prepare("
        SELECT 
            b.*,
            bt.upgrade_cost_multiplier
        FROM buildings b
        JOIN building_types bt ON b.type_id = bt.id
        WHERE b.id = ? AND b.character_id = ?
    ");
    $stmt->execute([$building_id, $character->getId()]);
    $building = $stmt->fetch();

    if (!$building) {
        throw new Exception('Ten budynek nie istnieje lub nie należy do ciebie.');
    }

    // Oblicz koszt ulepszenia
    $upgrade_cost = $building['level'] * $building['upgrade_cost_multiplier'];

    if ($character->getCash() < $upgrade_cost) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }

    // Ulepsz budynek
    $character->subtractCash($upgrade_cost);

    $stmt = $db->prepare("
        UPDATE buildings 
        SET level = level + 1,
            last_collection = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$building_id]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Budynek został ulepszony do poziomu ' . ($building['level'] + 1) . '!'
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