<?php
// Data utworzenia: 2025-03-23 13:24:24
// Autor: PanKrowa

define('IN_GAME', true);
require_once '../includes/config.php';

if (!isset($_SESSION['character_id'])) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}
$character = new Character($_SESSION['character_id']);

$clubId = (int)$_POST['club_id'] ?? 0;
$drugId = (int)$_POST['drug_id'] ?? 0;
$price = (int)$_POST['price'] ?? 0;

if ($price <= 0) {
    die(json_encode(['success' => false, 'message' => 'Nieprawidłowa cena.']));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Sprawdź czy klub należy do gracza
    $stmt = $db->prepare("
        SELECT * FROM clubs 
        WHERE id = ? AND character_id = ?
    ");
    $stmt->execute([$clubId, $character->getId()]);
    if (!$stmt->fetch()) {
        throw new Exception('Ten klub nie należy do ciebie.');
    }

    // Sprawdź czy gracz ma ten narkotyk
    $stmt = $db->prepare("
        SELECT * FROM character_drugs 
        WHERE character_id = ? AND drug_id = ? AND quantity > 0
    ");
    $stmt->execute([$character->getId(), $drugId]);
    if (!$stmt->fetch()) {
        throw new Exception('Nie posiadasz tego narkotyku.');
    }

    // Ustaw/zaktualizuj cenę narkotyku w klubie
    $stmt = $db->prepare("
        INSERT INTO club_drugs (club_id, drug_id, price, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE price = ?
    ");
    $stmt->execute([$clubId, $drugId, $price, $price]);

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Cena została zaktualizowana!'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}