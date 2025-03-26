<?php
// Data utworzenia: 2025-03-23 13:24:24
// Autor: PanKrowa

define('IN_GAME', true);
require_once '../includes/config.php';

if (!$character->isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}

$prostituteId = (int)$_POST['prostitute_id'] ?? 0;

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz informacje o prostytutce
    $stmt = $db->prepare("
        SELECT * FROM prostitutes 
        WHERE id = ? AND level_required <= ?
    ");
    $stmt->execute([$prostituteId, $character->getLevel()]);
    $prostitute = $stmt->fetch();

    if (!$prostitute) {
        throw new Exception('Ta prostytutka nie jest dostępna.');
    }

    // Sprawdź czy gracza stać na zakup
    if ($character->getCash() < $prostitute['price']) {
        throw new Exception('Nie stać cię na tę prostytutkę.');
    }

    // Sprawdź limit prostytutek
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM character_prostitutes 
        WHERE character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    if ($stmt->fetch()['count'] >= 10) {
        throw new Exception('Osiągnąłeś limit prostytutek.');
    }

    // Dodaj prostytutkę do kolekcji gracza
    $stmt = $db->prepare("
        INSERT INTO character_prostitutes 
        (character_id, prostitute_id, last_collection, created_at)
        VALUES (?, ?, NOW(), NOW())
    ");
    $stmt->execute([$character->getId(), $prostituteId]);

    // Odejmij pieniądze
    $character->removeCash($prostitute['price']);

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Zakupiono ' . $prostitute['name'] . '!'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}