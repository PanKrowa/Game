<?php
// Data utworzenia: 2025-03-23 13:24:24
// Autor: PanKrowa

define('IN_GAME', true);
require_once '../includes/config.php';

if (!isset($_SESSION['character_id'])) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}
$character = new Character($_SESSION['character_id']);

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz informacje o klubie
    $stmt = $db->prepare("
        SELECT * FROM clubs 
        WHERE id = ? AND character_id = ?
    ");
    $stmt->execute([$clubId, $character->getId()]);
    $club = $stmt->fetch();

    if (!$club) {
        throw new Exception('Ten klub nie należy do ciebie.');
    }

    // Zwróć 70% wartości klubu
    $refund = floor($club['price'] * 0.7);

    // Usuń wszystkie narkotyki z klubu
    $stmt = $db->prepare("DELETE FROM club_drugs WHERE club_id = ?");
    $stmt->execute([$clubId]);

    // Zwolnij klub
    $stmt = $db->prepare("
        UPDATE clubs 
        SET character_id = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$clubId]);

    // Dodaj pieniądze
    $character->addCash($refund);

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => "Sprzedano klub {$club['name']} za \${$refund}!"
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}