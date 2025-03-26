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

if (!$item_id || $quantity < 1) {
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

    // Pobierz dane przedmiotu z rynku
    $stmt = $db->prepare("
        SELECT 
            m.*,
            i.name as item_name
        FROM market_listings m
        JOIN items i ON m.item_id = i.id
        WHERE m.id = ? AND m.quantity >= ?
    ");
    $stmt->execute([$item_id, $quantity]);
    $listing = $stmt->fetch();

    if (!$listing) {
        throw new Exception('Ten przedmiot nie jest dostępny w wybranej ilości.');
    }

    // Sprawdź czy to nie własna oferta
    if ($listing['seller_id'] == $character->getId()) {
        throw new Exception('Nie możesz kupić własnego przedmiotu.');
    }

    $total_cost = $listing['price'] * $quantity;
    if ($character->getCash() < $total_cost) {
        throw new Exception('Nie masz wystarczająco pieniędzy.');
    }

    // Wykonaj transakcję
    $character->subtractCash($total_cost);

    // Dodaj pieniądze sprzedającemu
    $stmt = $db->prepare("
        UPDATE characters 
        SET cash = cash + ? 
        WHERE id = ?
    ");
    $stmt->execute([$total_cost, $listing['seller_id']]);

    // Zaktualizuj ilość w ofercie
    if ($quantity == $listing['quantity']) {
        $stmt = $db->prepare("
            DELETE FROM market_listings 
            WHERE id = ?
        ");
        $stmt->execute([$item_id]);
    } else {
        $stmt = $db->prepare("
            UPDATE market_listings 
            SET quantity = quantity - ? 
            WHERE id = ?
        ");
        $stmt->execute([$quantity, $item_id]);
    }

    // Dodaj przedmiot do ekwipunku kupującego
    $stmt = $db->prepare("
        INSERT INTO character_items (
            character_id, item_id, quantity
        ) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        quantity = quantity + ?
    ");
    $stmt->execute([
        $character->getId(),
        $listing['item_id'],
        $quantity,
        $quantity
    ]);

    // Zapisz log transakcji
    $stmt = $db->prepare("
        INSERT INTO market_transactions (
            item_id, seller_id, buyer_id,
            quantity, price, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $listing['item_id'],
        $listing['seller_id'],
        $character->getId(),
        $quantity,
        $listing['price']
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Kupiłeś {$quantity} szt. {$listing['item_name']} za \${$total_cost}!"
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