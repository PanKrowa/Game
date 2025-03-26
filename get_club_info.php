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
    die(json_encode(['error' => 'Nie jesteś zalogowany.']));
}

$club_id = filter_input(INPUT_GET, 'club_id', FILTER_VALIDATE_INT);
if (!$club_id) {
    die(json_encode(['error' => 'Nieprawidłowe ID klubu.']));
}

try {
    $club = Nightclub::getById($club_id);
    if (!$club) {
        throw new Exception('Klub nie istnieje.');
    }
    
    echo json_encode([
        'name' => $club->getName(),
        'entry_fee' => $club->getEntryFee(),
        'drug_type' => $club->getDrugType(),
        'drug_name' => $club->getDrugName(),
        'drug_unit' => $club->getDrugUnit(),
        'drug_price' => $club->getDrugPrice(),
        'max_drugs' => $club->getMaxDrugs(),
        'energy_per_gram' => $club->getEnergyPerGram()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}