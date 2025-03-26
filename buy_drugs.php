<?php
// Data utworzenia: 2025-03-23 08:44:50
// Autor: PanKrowa

session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Character.php';
require_once '../includes/Nightclub.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();
    
    $club_id = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    
    if (!$club_id || !$quantity) {
        throw new Exception('Nieprawidłowe dane.');
    }
    
    $character = new Character($_SESSION['character_id']);
    $club = Nightclub::getById($club_id);
    
    if (!$club) {
        throw new Exception('Klub nie istnieje.');
    }
    
    if (!$club->canEnter($character)) {
        throw new Exception('Nie spełniasz wymagań tego klubu.');
    }
    
    // Sprawdź tolerancję
    $stmt = $db->prepare("
        SELECT tolerance_level, TIMESTAMPDIFF(MINUTE, visited_at, NOW()) as minutes_since_last_use
        FROM nightclub_visits 
        WHERE character_id = ? AND drug_type = ?
        ORDER BY visited_at DESC
        LIMIT 1
    ");
    $stmt->execute([$character->getId(), $club->getDrugType()]);
    $tolerance_data = $stmt->fetch();
    
    $tolerance_level = $tolerance_data ? (int)$tolerance_data['tolerance_level'] : 0;
    $minutes_since_last_use = $tolerance_data ? (int)$tolerance_data['minutes_since_last_use'] : PHP_INT_MAX;
    
    // Zmniejsz tolerancję jeśli minęło wystarczająco czasu
    if ($minutes_since_last_use >= 60) {
        $tolerance_level = max(0, $tolerance_level - floor($minutes_since_last_use / 60));
    }
    
    // Sprawdź czy nie nastąpi przedawkowanie
    if ($tolerance_level >= Config::MAX_TOLERANCE && $quantity > 1) {
        throw new Exception('Twoja tolerancja jest zbyt wysoka! Musisz poczekać lub wziąć mniejszą dawkę.');
    }
    
    // Oblicz koszty
    $total_cost = $club->getEntryFee() + ($club->getDrugPrice() * $quantity);
    
    if ($character->getCash() < $total_cost) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }
    
    // Pobierz opłatę
    $character->reduceCash($total_cost);
    
    // Dodaj energię
    $energy_gain = $quantity * $club->getEnergyPerGram();
    $new_energy = min(Config::MAX_ENERGY, $character->getCurrentEnergy() + $energy_gain);
    
    $stmt = $db->prepare("
        UPDATE characters 
        SET current_energy = ?, 
            last_activity = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_energy, $character->getId()]);
    
    // Zapisz wizytę i tolerancję
    $new_tolerance = min(
        Config::MAX_TOLERANCE, 
        $tolerance_level + ($quantity * $club->getToleranceIncrease())
    );
    
    $stmt = $db->prepare("
        INSERT INTO nightclub_visits 
        (character_id, nightclub_id, drug_type, tolerance_level, visited_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $club->getId(),
        $club->getDrugType(),
        $new_tolerance
    ]);
    
    // Zapisz log zakupu
    $stmt = $db->prepare("
        INSERT INTO nightclub_logs 
        (character_id, nightclub_id, drug_type, quantity, total_cost)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $character->getId(),
        $club->getId(),
        $club->getDrugType(),
        $quantity,
        $total_cost
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Kupiłeś {$quantity} {$club->getDrugUnit()} {$club->getDrugName()} za $" . number_format($total_cost, 2),
        'energy_gain' => $energy_gain,
        'new_tolerance' => $new_tolerance
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