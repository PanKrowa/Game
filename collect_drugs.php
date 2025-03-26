<?php
// Data utworzenia: 2025-03-23 09:48:19
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

$building_id = $_POST['building_id'] ?? 0;
if (!$building_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie wybrano budynku.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);
    
    // Sprawdź czy gracz może zbierać produkcję
    if ($character->isInJail()) {
        throw new Exception('Nie możesz zbierać produkcji będąc w więzieniu.');
    }

    // Pobierz dane budynku
    $stmt = $db->prepare("
        SELECT 
            b.*,
            bt.production_time,
            bt.production_amount,
            TIMESTAMPDIFF(SECOND, b.last_collection, NOW()) as seconds_since_collection
        FROM buildings b
        JOIN building_types bt ON b.type_id = bt.id
        WHERE b.id = ? AND b.character_id = ?
    ");
    $stmt->execute([$building_id, $character->getId()]);
    $building = $stmt->fetch();

    if (!$building) {
        throw new Exception('Ten budynek nie istnieje lub nie należy do ciebie.');
    }

    // Oblicz ilość wyprodukowanych narkotyków
    $production_cycles = floor($building['seconds_since_collection'] / $building['production_time']);
    if ($production_cycles < 1) {
        throw new Exception('Nie ma jeszcze nic do zebrania.');
    }

    $produced_amount = $production_cycles * ($building['production_amount'] * $building['level']);

    // Dodaj narkotyki do ekwipunku
    $stmt = $db->prepare("
        INSERT INTO character_items (
            character_id, item_id, quantity
        ) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        quantity = quantity + ?
    ");
    $stmt->execute([
        $character->getId(),
        Config::DRUGS_ITEM_ID,
        $produced_amount,
        $produced_amount
    ]);

    // Aktualizuj czas ostatniego zebrania
    $stmt = $db->prepare("
        UPDATE buildings 
        SET last_collection = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$building_id]);

    // Zapisz log produkcji
    $stmt = $db->prepare("
        INSERT INTO building_production (
            building_id, amount, collected_at
        ) VALUES (?, ?, NOW())
    ");
    $stmt->execute([
        $building_id,
        $produced_amount
    ]);

    // 5% szans na nalot policji
    if (rand(1, 100) <= 5) {
        // Konfiskata narkotyków
        $stmt = $db->prepare("
            UPDATE character_items 
            SET quantity = 0
            WHERE character_id = ? AND item_id = ?
        ");
        $stmt->execute([$character->getId(), Config::DRUGS_ITEM_ID]);

        // Idź do więzienia na 2-4 godziny
        $jail_hours = rand(2, 4);
        $jail_until = new DateTime();
        $jail_until->modify("+{$jail_hours} hours");
        
        $character->setJailUntil($jail_until->format('Y-m-d H:i:s'));
        $character->setInJail(true);

        // Zapisz log więzienia
        $stmt = $db->prepare("
            INSERT INTO jail_logs (
                character_id, reason, sentence_hours,
                created_at, jail_until
            ) VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $character->getId(),
            "Nielegalna produkcja narkotyków",
            $jail_hours,
            $jail_until->format('Y-m-d H:i:s')
        ]);

        $db->commit();

        echo json_encode([
            'success' => false,
            'message' => "Nalot policji! Trafiłeś do więzienia na {$jail_hours} godzin!"
        ]);
        exit;
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'amount' => $produced_amount
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