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

    if ($character->getCurrentEnergy() < Config::JAIL_ESCAPE_ENERGY_COST) {
        throw new Exception('Nie masz wystarczająco energii na próbę ucieczki.');
    }

    // Sprawdź czy nie przekroczono limitu prób ucieczki
    $stmt = $db->prepare("
        SELECT COUNT(*) as attempts
        FROM jail_logs
        WHERE character_id = ?
        AND action_type = 'escape'
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$character->getId()]);
    $escape_attempts = $stmt->fetch();

    if ($escape_attempts['attempts'] >= Config::MAX_JAIL_ESCAPE_ATTEMPTS) {
        throw new Exception('Osiągnąłeś limit prób ucieczki w tej godzinie.');
    }

    // Odejmij energię
    $character->setCurrentEnergy(
        $character->getCurrentEnergy() - Config::JAIL_ESCAPE_ENERGY_COST
    );

    // 15% szans na ucieczkę
    $escape_successful = rand(1, 100) <= 15;

    if ($escape_successful) {
        // Uciekł z więzienia
        $character->setInJail(false);
        $character->setJailUntil(null);

        // Zapisz log udanej ucieczki
        $stmt = $db->prepare("
            INSERT INTO jail_logs (
                character_id, action_type, success,
                created_at
            ) VALUES (?, 'escape', 1, NOW())
        ");
        $stmt->execute([$character->getId()]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Udało ci się uciec z więzienia!'
        ]);
    } else {
        // Nieudana ucieczka - dodatkowa kara
        $additional_hours = rand(1, 3);
        
        $stmt = $db->prepare("
            UPDATE characters 
            SET jail_until = DATE_ADD(jail_until, INTERVAL ? HOUR)
            WHERE id = ?
        ");
        $stmt->execute([$additional_hours, $character->getId()]);

        // Zapisz log nieudanej ucieczki
        $stmt = $db->prepare("
            INSERT INTO jail_logs (
                character_id, action_type, success,
                details, created_at
            ) VALUES (?, 'escape', 0, ?, NOW())
        ");
        $stmt->execute([
            $character->getId(),
            "Dodano {$additional_hours}h do wyroku"
        ]);

        $db->commit();

        echo json_encode([
            'success' => false,
            'message' => "Próba ucieczki nie powiodła się! Dodano {$additional_hours}h do twojego wyroku!"
        ]);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}