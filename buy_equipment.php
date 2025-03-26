<?php
// Data utworzenia: 2025-03-23 13:24:24
// Autor: PanKrowa

define('IN_GAME', true);
require_once '../includes/config.php';

if (!$character->isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}

$equipmentId = (int)$_POST['equipment_id'] ?? 0;

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz informacje o ekwipunku
    $stmt = $db->prepare("
        SELECT * FROM equipment 
        WHERE id = ? AND level_required <= ?
    ");
    $stmt->execute([$equipmentId, $character->getLevel()]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        throw new Exception('Ten przedmiot nie jest dostępny.');
    }

    // Sprawdź czy gracza stać na zakup
    if ($character->getCash() < $equipment['price']) {
        throw new Exception('Nie stać cię na ten przedmiot.');
    }

    // Sprawdź czy gracz już nie ma tego przedmiotu
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM character_equipment 
        WHERE character_id = ? AND equipment_id = ?
    ");
    $stmt->execute([$character->getId(), $equipmentId]);
    if ($stmt->fetch()['count'] > 0) {
        throw new Exception('Już posiadasz ten przedmiot.');
    }

    // Dodaj przedmiot do ekwipunku gracza
    $stmt = $db->prepare("
        INSERT INTO character_equipment (character_id, equipment_id, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$character->getId(), $equipmentId]);

    // Odejmij pieniądze
    $character->removeCash($equipment['price']);

    // Dodaj statystyki jeśli przedmiot jest automatycznie zakładany
    if ($equipment['type'] === 'weapon' || $equipment['type'] === 'armor') {
        $stmt = $db->prepare("
            UPDATE character_equipment 
            SET equipped = TRUE 
            WHERE character_id = ? AND equipment_id = ?
        ");
        $stmt->execute([$character->getId(), $equipmentId]);

        $character->addAttack($equipment['attack']);
        $character->addDefense($equipment['defense']);
    }

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Zakupiono ' . $equipment['name'] . '!'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}