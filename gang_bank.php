<?php
// Data utworzenia: 2025-03-23 09:54:24
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

$action = $_POST['action'] ?? '';
$amount = intval($_POST['amount'] ?? 0);

if (!in_array($action, ['deposit', 'withdraw']) || $amount < 1) {
    die(json_encode([
        'success' => false,
        'message' => 'Nieprawidłowe parametry.'
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
            g.id as gang_id,
            g.bank as gang_bank,
            gm.role
        FROM gang_members gm
        JOIN gangs g ON gm.gang_id = g.id
        WHERE gm.character_id = ?
    ");
    $stmt->execute([$character->getId()]);
    $member = $stmt->fetch();

    if (!$member) {
        throw new Exception('Nie należysz do żadnego gangu.');
    }

    // Sprawdź uprawnienia
    if ($action === 'withdraw' && !in_array($member['role'], ['leader', 'officer'])) {
        throw new Exception('Nie masz uprawnień do wypłacania z banku gangu.');
    }

    if ($action === 'deposit') {
        // Wpłata do banku
        if ($character->getCash() < $amount) {
            throw new Exception('Nie masz wystarczająco pieniędzy.');
        }

        $character->subtractCash($amount);

        $stmt = $db->prepare("
            UPDATE gangs 
            SET bank = bank + ?
            WHERE id = ?
        ");
        $stmt->execute([$amount, $member['gang_id']]);

        $log_message = "wpłacił \${$amount} do banku gangu";
    } else {
        // Wypłata z banku
        if ($member['gang_bank'] < $amount) {
            throw new Exception('W banku gangu nie ma tylu pieniędzy.');
        }

        $character->addCash($amount);

        $stmt = $db->prepare("
            UPDATE gangs 
            SET bank = bank - ?
            WHERE id = ?
        ");
        $stmt->execute([$amount, $member['gang_id']]);

        $log_message = "wypłacił \${$amount} z banku gangu";
    }

    // Zapisz log gangu
    $stmt = $db->prepare("
        INSERT INTO gang_logs (
            gang_id, character_id, message,
            created_at
        ) VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $member['gang_id'],
        $character->getId(),
        $log_message
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => ucfirst($log_message) . '!'
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