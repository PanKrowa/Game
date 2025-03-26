<?php
// Data utworzenia: 2025-03-23 13:24:24
// Autor: PanKrowa

define('IN_GAME', true);
require_once '../includes/config.php';

if ($character->getId() === null) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}

$drugId = (int)$_POST['drug_id'] ?? 0;
$quantity = (int)$_POST['quantity'] ?? 0;

if ($quantity <= 0) {
    die(json_encode(['success' => false, 'message' => 'Nieprawidłowa ilość.']));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz informacje o narkotyku
    $stmt = $db->prepare("
        SELECT * FROM drugs 
        WHERE id = ? AND level_required <= ?
    ");
    $stmt->execute([$drugId, $character->getLevel()]);
    $drug = $stmt->fetch();

    if (!$drug) {
        throw new Exception('Ten narkotyk nie jest dostępny.');
    }

    $totalCost = $drug['dealer_price'] * $quantity;

    // Sprawdź czy gracza stać na zakup
    if ($character->getCash() < $totalCost) {
        throw new Exception('Nie stać cię na tę ilość narkotyków.');
    }

    // Dodaj/zaktualizuj narkotyki gracza
    $stmt = $db->prepare("
        INSERT INTO character_drugs (character_id, drug_id, quantity, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE quantity = quantity + ?
    ");
    $stmt->execute([$character->getId(), $drugId, $quantity, $quantity]);

    // Odejmij pieniądze
    $character->removeCash($totalCost);

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => "Zakupiono {$quantity}g {$drug['name']}!"
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}