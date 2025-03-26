<?php
// Data utworzenia: 2025-03-23 09:51:45
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Nieprawidłowa metoda żądania.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);

    // Sprawdź członkostwo w gangu
    $stmt = $db->prepare("
        SELECT 
            gm.gang_id,
            gm.role
        FROM gang_members gm
        WHERE gm.character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $member = $stmt->fetch();

    if (!$member) {
        throw new Exception('Nie należysz do żadnego gangu.');
    }

    if ($member['role'] === 'leader') {
        throw new Exception('Lider nie może opuścić gangu. Najpierw przekaż przywództwo lub rozwiąż gang.');
    }

    // Opuść gang
    $stmt = $db->prepare("
        DELETE FROM gang_members 
        WHERE character_id = ? AND gang_id = ?
    ");
    $stmt->execute([$character->getId(), $member['gang_id']]);

    // Zapisz log gangu
    $stmt = $db->prepare("
        INSERT INTO gang_logs (
            gang_id, character_id, message,
            created_at
        ) VALUES (?, ?, 'opuścił gang', NOW())
    ");
    $stmt->execute([
        $member['gang_id'],
        $character->getId()
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Opuściłeś gang!'
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