<?php
// Data utworzenia: 2025-03-23 15:02:37
// Autor: PanKrowa

define('IN_GAME', true);
require_once('../includes/config.php');

if (!isset($_SESSION['character_id'])) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}

$character = new Character($_SESSION['character_id']);
$clubId = (int)$_POST['club_id'];
$drugId = (int)$_POST['drug_id'];
$quantity = (int)$_POST['quantity'];
$price = (int)$_POST['price'];

try {
    $db = Database::getInstance();
    
    // Sprawdź czy klub należy do gracza
    $stmt = $db->prepare("
        SELECT * FROM clubs 
        WHERE id = ? AND character_id = ?
    ");
    $stmt->execute([$clubId, $character->getId()]);
    if (!$stmt->fetch()) {
        throw new Exception('Ten klub nie należy do ciebie.');
    }

    // Sprawdź czy gracz ma wystarczającą ilość narkotyków
    $stmt = $db->prepare("
        SELECT quantity FROM character_drugs 
        WHERE character_id = ? AND drug_id = ?
    ");
    $stmt->execute([$character->getId(), $drugId]);
    $playerDrug = $stmt->fetch();

    if (!$playerDrug || $playerDrug['quantity'] < $quantity) {
        throw new Exception('Nie masz wystarczającej ilości tego narkotyku.');
    }

    $db->beginTransaction();

    // Odejmij narkotyki od gracza
    $stmt = $db->prepare("
        UPDATE character_drugs 
        SET quantity = quantity - ? 
        WHERE character_id = ? AND drug_id = ?
    ");
    $stmt->execute([$quantity, $character->getId(), $drugId]);

    // Dodaj narkotyki do klubu
    $stmt = $db->prepare("
        INSERT INTO club_drugs (club_id, drug_id, quantity, price, created_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        quantity = quantity + ?,
        price = ?,
        updated_at = NOW()
    ");
    $stmt->execute([$clubId, $drugId, $quantity, $price, $quantity, $price]);

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => "Dodano {$quantity} jednostek narkotyku do klubu!"
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}