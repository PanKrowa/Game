<?php
// Data utworzenia: 2025-03-23 09:56:49
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

    // Pobierz poziom tolerancji gracza
    $stmt = $db->prepare("
        SELECT tolerance 
        FROM character_stats 
        WHERE character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $stats = $stmt->fetch();

    if ($stats['tolerance'] <= 0) {
        throw new Exception('Nie masz uzależnienia do wyleczenia.');
    }

    $detox_cost = $stats['tolerance'] * Config::HOSPITAL_DETOX_COST_PER_POINT;
    
    if ($character->getCash() < $detox_cost) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }

    // Wykonaj detoks
    $character->subtractCash($detox_cost);
    
    $stmt = $db->prepare("
        UPDATE character_stats 
        SET tolerance = 0 
        WHERE character_id = ?
    ");
    $stmt->execute([$character->getId()]);

    // Zapisz log detoksu
    $stmt = $db->prepare("
        INSERT INTO hospital_logs (
            character_id, action_type, cost,
            created_at
        ) VALUES (?, 'detox', ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $detox_cost
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Przeszedłeś detoks za \${$detox_cost}!"
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