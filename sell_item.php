<?php
// Data utworzenia: 2025-03-23 09:49:23
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

$item_id = $_POST['item_id'] ?? 0;
$quantity = intval($_POST['quantity'] ?? 1);
$price = intval($_POST['price'] ?? 0);

if (!$item_id || $quantity < 1 || $price < 1) {
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

    // Sprawdź czy gracz posiada przedmiot
    $stmt = $db->prepare("
        SELECT 
            ci.*,
            i.name as item_name,
            i.tradeable
        FROM character_items ci
        JOIN items i ON ci.item_id = i.id
        WHERE ci.character_id = ? AND ci.item_id = ?
    ");
    $stmt->execute([$character->getId(), $item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new Exception('Nie posiadasz tego przedmiotu.');
    }

    if ($item['quantity'] < $quantity) {
        throw new Exception('Nie masz wystarczającej ilości tego przedmiotu.');
    }

    if (!$item['tradeable']) {
        throw new Exception('Ten przedmiot nie może być sprzedany.');
    }

    // Sprawdź czy gracz nie ma już wystawionej oferty na ten przedmiot
    $stmt = $db->prepare("
        SELECT COUNT(*) as listings
        FROM market_listings
        WHERE seller_id = ? AND item_id = ?
    ");
    $stmt->execute([$character->getId(), $item_id]);
    $existing_listings = $stmt->fetch();

    if ($existing_listings['listings'] > 0) {
        throw new Exception('Masz już wystawioną ofertę na ten przedmiot.');
    }

    // Wystaw przedmiot na rynku
    $stmt = $db->prepare("
        INSERT INTO market_listings (
            seller_id, item_id, quantity,
            price, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $item_id,
        $quantity,
        $price
    ]);

    // Usuń przedmioty z ekwipunku
    if ($quantity == $item['quantity']) {
        $stmt = $db->prepare("
            DELETE FROM character_items 
            WHERE character_id = ? AND item_id = ?
        ");
        $stmt->execute([$character->getId(), $item_id]);
    } else {
        $stmt = $db->prepare("
            UPDATE character_items 
            SET quantity = quantity - ? 
            WHERE character_id = ? AND item_id = ?
        ");
        $stmt->execute([$quantity, $character->getId(), $item_id]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Wystawiono {$quantity} szt. {$item['item_name']} na sprzedaż za \${$price} za sztukę!"
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