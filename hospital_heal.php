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

    // Oblicz koszt leczenia
    $missing_health = $character->getMaxHealth() - $character->getCurrentHealth();
    if ($missing_health <= 0) {
        throw new Exception('Masz pełne zdrowie.');
    }

    $heal_cost = $missing_health * Config::HOSPITAL_HEAL_COST_PER_HP;
    
    if ($character->getCash() < $heal_cost) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }

    // Wykonaj leczenie
    $character->subtractCash($heal_cost);
    $character->setCurrentHealth($character->getMaxHealth());

    // Zapisz log leczenia
    $stmt = $db->prepare("
        INSERT INTO hospital_logs (
            character_id, action_type, cost,
            created_at
        ) VALUES (?, 'heal', ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $heal_cost
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Zostałeś wyleczony za \${$heal_cost}!"
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