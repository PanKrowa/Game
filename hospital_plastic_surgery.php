<?php
// Data utworzenia: 2025-03-23 09:57:28
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

$new_name = trim($_POST['new_name'] ?? '');

if (strlen($new_name) < 3 || strlen($new_name) > 20) {
    die(json_encode([
        'success' => false,
        'message' => 'Nowa nazwa musi mieć od 3 do 20 znaków.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Sprawdź czy nazwa nie jest zajęta
    $stmt = $db->prepare("
        SELECT COUNT(*) as name_taken
        FROM characters
        WHERE name = ?
    ");
    $stmt->execute([$new_name]);
    $name_check = $stmt->fetch();

    if ($name_check['name_taken'] > 0) {
        throw new Exception('Ta nazwa jest już zajęta.');
    }

    if ($character->getCash() < Config::HOSPITAL_NAME_CHANGE_COST) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }

    // Zmień nazwę
    $character->subtractCash(Config::HOSPITAL_NAME_CHANGE_COST);

    $stmt = $db->prepare("
        UPDATE characters 
        SET name = ? 
        WHERE id = ?
    ");
    $stmt->execute([
        $new_name,
        $character->getId()
    ]);

    // Zapisz log operacji plastycznej
    $stmt = $db->prepare("
        INSERT INTO hospital_logs (
            character_id, action_type, cost,
            details, created_at
        ) VALUES (?, 'plastic_surgery', ?, ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        Config::HOSPITAL_NAME_CHANGE_COST,
        "Zmiana nazwy na: {$new_name}"
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Twoja nazwa została zmieniona na {$new_name}!"
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