<?php
// Data utworzenia: 2025-03-25 17:20:35
// Autor: PanKrowa

define('IN_GAME', true);
require_once '../includes/config.php';

$characterId = $character->getId();
$drugId = (int)$_POST['drug_id'] ?? 0;
$quantity = (int)$_POST['quantity'] ?? 0;

if ($characterId === null) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}

if ($quantity <= 0) {
    die(json_encode(['success' => false, 'message' => 'Nieprawidłowa ilość.']));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    $energyEffect = consumeDrug($characterId, $drugId);

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => "Zażyto {$quantity}g narkotyku. Efekt: Energia +{$energyEffect}%"
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>