<?php
// Function to consume a drug
function consumeDrug($characterId, $drugId) {
    $db = Database::getInstance();
    
    // Fetch drug information
    $stmt = $db->prepare("SELECT * FROM drugs WHERE id = ?");
    $stmt->execute([$drugId]);
    $drug = $stmt->fetch();
    
    if (!$drug) {
        throw new Exception('Narkotyk nie istnieje.');
    }

    // Fetch or initialize tolerance information
    $stmt = $db->prepare("
        SELECT * FROM character_drug_tolerance 
        WHERE character_id = ? AND drug_id = ?
    ");
    $stmt->execute([$characterId, $drugId]);
    $tolerance = $stmt->fetch();

    if (!$tolerance) {
        $tolerance = [
            'tolerance' => 0,
            'last_used' => null
        ];
    }

    // Calculate energy effect based on tolerance
    $energyEffect = $drug['initial_energy_effect'] - ($drug['initial_energy_effect'] - $drug['energy_effect_100_tolerance']) * $tolerance['tolerance'] / 100;

    // Update character's energy level
    $stmt = $db->prepare("UPDATE characters SET energy = LEAST(100, energy + ?) WHERE id = ?");
    $stmt->execute([$energyEffect, $characterId]);

    // Increase tolerance
    $newTolerance = min(100, $tolerance['tolerance'] + $drug['tolerance_increase_per_use']);
    $stmt = $db->prepare("
        INSERT INTO character_drug_tolerance (character_id, drug_id, tolerance, last_used)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE tolerance = VALUES(tolerance), last_used = VALUES(last_used)
    ");
    $stmt->execute([$characterId, $drugId, $newTolerance]);

    return $energyEffect;
}

// Function to decrease tolerance over time
function decreaseTolerance($characterId, $drugId) {
    $db = Database::getInstance();
    
    // Fetch tolerance information
    $stmt = $db->prepare("
        SELECT * FROM character_drug_tolerance 
        WHERE character_id = ? AND drug_id = ?
    ");
    $stmt->execute([$characterId, $drugId]);
    $tolerance = $stmt->fetch();

    if ($tolerance) {
        // Calculate time since last use
        $timeSinceLastUse = (new DateTime())->diff(new DateTime($tolerance['last_used']))->days;

        // Decrease tolerance
        $newTolerance = max(0, $tolerance['tolerance'] - $timeSinceLastUse * $drug['tolerance_decrease_per_day']);
        $stmt = $db->prepare("UPDATE character_drug_tolerance SET tolerance = ? WHERE character_id = ? AND drug_id = ?");
        $stmt->execute([$newTolerance, $characterId, $drugId]);
    }
}