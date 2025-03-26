<?php
session_start();

define('IN_GAME', true);
require_once('../includes/config.php');

// Detailed debugging
error_log("=== Buy Club Debug ===");
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));
error_log("Cookie data: " . print_r($_COOKIE, true));

if (!isset($_SESSION['character_id'])) {
    error_log("No character_id in session!");
    die(json_encode([
        'success' => false, 
        'message' => 'Nie jesteś zalogowany.', 
        'debug' => [
            'session' => $_SESSION,
            'cookies' => $_COOKIE,
            'post' => $_POST,
            'time' => date('Y-m-d H:i:s')
        ]
    ]));
}

$character = new Character($_SESSION['character_id']);
$clubId = (int)$_POST['club_id'];

try {
    $db = Database::getInstance();
    
    // Debug info
    error_log("Trying to buy club: " . $clubId . " for character: " . $character->getId());
    
    // Sprawdź czy klub jest dostępny
    $stmt = $db->prepare("
        SELECT * FROM clubs 
        WHERE id = ? AND character_id IS NULL
    ");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();

    if (!$club) {
        throw new Exception('Ten klub nie jest dostępny do kupienia.');
    }

    // Sprawdź wymagany poziom
    if ($character->getLevel() < $club['level_required']) {
        throw new Exception('Masz za niski poziom na ten klub.');
    }

    // Sprawdź czy gracza stać
    if ($character->getCash() < $club['price']) {
        throw new Exception('Nie stać cię na ten klub.');
    }

    // Sprawdź limit klubów
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM clubs 
        WHERE character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    if ($stmt->fetch()['count'] >= 4) {
        throw new Exception('Osiągnąłeś limit klubów (max. 4).');
    }

    $db->beginTransaction();

    // Przypisz klub do gracza
    $stmt = $db->prepare("
        UPDATE clubs 
        SET character_id = ?,
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$character->getId(), $clubId]);

    // Odejmij pieniądze
    $character->subtractCash($club['price']);

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => "Zakupiono klub {$club['name']}!",
        'redirect' => 'nightclubs.php'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error buying club: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}