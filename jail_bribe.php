<?php
// Data utworzenia: 2025-03-23 09:58:43
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

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    if (!$character->isInJail()) {
        throw new Exception('Nie jesteś w więzieniu.');
    }

    // Oblicz koszt łapówki
    $stmt = $db->prepare("
        SELECT 
            jail_until,
            TIMESTAMPDIFF(HOUR, NOW(), jail_until) as hours_left
        FROM characters
        WHERE id = ?
    ");
    $stmt->execute([$character->getId()]);
    $jail_data = $stmt->fetch();

    $bribe_cost = $jail_data['hours_left'] * Config::JAIL_BRIBE_COST_PER_HOUR;
    
    if ($character->getCash() < $bribe_cost) {
        throw new Exception('Nie masz wystarczająco pieniędzy na łapówkę.');
    }

    // 25% szans że łapówka się nie powiedzie i stracisz pieniądze
    if (rand(1, 100) <= 25) {
        $character->subtractCash($bribe_cost);
        
        throw new Exception('Strażnik wziął łapówkę ale cię nie wypuścił!');
    }

    // Wypuść z więzienia
    $character->subtractCash($bribe_cost);
    $character->setInJail(false);
    $character->setJailUntil(null);

    // Zapisz log łapówki
    $stmt = $db->prepare("
        INSERT INTO jail_logs (
            character_id, action_type, cost,
            created_at
        ) VALUES (?, 'bribe', ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $bribe_cost
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Przekupiłeś strażnika i wyszedłeś z więzienia!'
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