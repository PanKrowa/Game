<?php
// Data utworzenia: 2025-03-23 09:59:40
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

$skill = $_POST['skill'] ?? '';

if (!in_array($skill, ['strength', 'agility', 'endurance', 'intelligence'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Nieprawidłowa umiejętność.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Sprawdź czy gracz może trenować
    if ($character->isInJail()) {
        throw new Exception('Nie możesz trenować będąc w więzieniu.');
    }
    
    if ($character->isInHospital()) {
        throw new Exception('Nie możesz trenować będąc w szpitalu.');
    }

    if ($character->getCurrentEnergy() < Config::TRAINING_ENERGY_COST) {
        throw new Exception('Nie masz wystarczająco energii na trening.');
    }

    // Pobierz aktualny poziom umiejętności
    $stmt = $db->prepare("
        SELECT {$skill} as skill_level
        FROM character_stats
        WHERE character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $stats = $stmt->fetch();

    // Sprawdź czy nie osiągnięto maksymalnego poziomu
    if ($stats['skill_level'] >= Config::MAX_SKILL_LEVEL) {
        throw new Exception('Osiągnąłeś maksymalny poziom tej umiejętności.');
    }

    // Oblicz koszt treningu
    $training_cost = ($stats['skill_level'] + 1) * Config::TRAINING_COST_MULTIPLIER;
    
    if ($character->getCash() < $training_cost) {
        throw new Exception('Nie masz wystarczająco pieniędzy na trening.');
    }

    // Wykonaj trening
    $character->subtractCash($training_cost);
    $character->setCurrentEnergy(
        $character->getCurrentEnergy() - Config::TRAINING_ENERGY_COST
    );

    // 75% szans na zwiększenie umiejętności
    if (rand(1, 100) <= 75) {
        $stmt = $db->prepare("
            UPDATE character_stats 
            SET {$skill} = {$skill} + 1
            WHERE character_id = ?
        ");
        $stmt->execute([$character->getId()]);

        $success = true;
        $message = "Trening zakończony sukcesem! Twój poziom {$skill} wzrósł o 1!";
    } else {
        $success = false;
        $message = "Trening nie przyniósł rezultatów.";
    }

    // Zapisz log treningu
    $stmt = $db->prepare("
        INSERT INTO training_logs (
            character_id, skill, cost,
            success, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $skill,
        $training_cost,
        $success
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $message
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