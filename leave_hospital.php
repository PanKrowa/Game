<?php
// Data utworzenia: 2025-03-23 09:57:28
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

    if (!$character->isInHospital()) {
        throw new Exception('Nie jesteś w szpitalu.');
    }

    // Sprawdź czy minął czas pobytu
    $stmt = $db->prepare("
        SELECT hospital_until
        FROM characters
        WHERE id = ?
    ");
    $stmt->execute([$character->getId()]);
    $hospital_data = $stmt->fetch();

    $hospital_until = new DateTime($hospital_data['hospital_until']);
    $now = new DateTime();

    if ($hospital_until > $now) {
        $remaining_time = $now->diff($hospital_until);
        throw new Exception("Musisz jeszcze pozostać w szpitalu przez {$remaining_time->format('%H:%I:%S')}.");
    }

    // Opuść szpital
    $character->setInHospital(false);
    $character->setHospitalUntil(null);

    // Zapisz log wyjścia ze szpitala
    $stmt = $db->prepare("
        INSERT INTO hospital_logs (
            character_id, action_type,
            created_at
        ) VALUES (?, 'leave', NOW())
    ");
    $stmt->execute([$character->getId()]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Opuściłeś szpital!'
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