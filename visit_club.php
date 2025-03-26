<?php
// Data utworzenia: 2025-03-23 15:27:56
// Autor: PanKrowa

define('IN_GAME', true);
require_once('../includes/config.php');

if (!isset($_SESSION['character_id'])) {
    die(json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany.']));
}

$character = new Character($_SESSION['character_id']);
$clubId = (int)$_POST['club_id'];
$drugId = !empty($_POST['drug_id']) ? (int)$_POST['drug_id'] : null;

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Sprawdź czy można odwiedzić klub
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM club_visits 
        WHERE character_id = ? 
        AND club_id = ? 
        AND visited_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$character->getId(), $clubId]);
    if ($stmt->fetch()['count'] >= 5) {
        throw new Exception('Osiągnąłeś limit wizyt w tym klubie.');
    }

    // Pobierz informacje o klubie i narkotyku
    $stmt = $db->prepare("
        SELECT c.*, cd.quantity, cd.price, d.name as drug_name, 
               d.energy_boost, d.addiction_rate
        FROM clubs c
        LEFT JOIN club_drugs cd ON c.id = cd.club_id AND cd.drug_id = ?
        LEFT JOIN drugs d ON cd.drug_id = d.id
        WHERE c.id = ?
    ");
    $stmt->execute([$drugId, $clubId]);
    $data = $stmt->fetch();

    if (!$data) {
        throw new Exception('Ten klub nie istnieje.');
    }

    $totalCost = $data['entry_fee'];
    
    // Jeśli wybrano narkotyk
    if ($drugId) {
        if (!$data['quantity']) {
            throw new Exception('Ten narkotyk nie jest dostępny.');
        }
        $totalCost += $data['price'];
    }

    // Sprawdź czy gracza stać
    if ($character->getCash() < $totalCost) {
        throw new Exception('Nie stać cię na wejście' . 
            ($drugId ? ' i narkotyki' : '') . '.');
    }

    // Dodaj wizytę
    $stmt = $db->prepare("
        INSERT INTO club_visits 
        (club_id, character_id, entry_fee_paid, visited_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$clubId, $character->getId(), $data['entry_fee']]);

    if ($drugId) {
        // Zmniejsz ilość narkotyku w klubie
        $stmt = $db->prepare("
            UPDATE club_drugs 
            SET quantity = quantity - 1
            WHERE club_id = ? AND drug_id = ?
        ");
        $stmt->execute([$clubId, $drugId]);

        // Dodaj sprzedaż narkotyku
        $stmt = $db->prepare("
            INSERT INTO club_drug_sales 
            (club_id, drug_id, character_id, quantity, price_per_unit, 
             energy_gained, created_at)
            VALUES (?, ?, ?, 1, ?, ?, NOW())
        ");
        $stmt->execute([
            $clubId, 
            $drugId, 
            $character->getId(), 
            $data['price'],
            $data['energy_boost']
        ]);

        // Dodaj energię
        $character->addEnergy($data['energy_boost']);

        // Szansa na uzależnienie
        if (rand(1, 100) <= $data['addiction_rate']) {
            $character->addAddiction($drugId, 1);
        }
    }

    // Odejmij pieniądze
    $character->subtractCash($totalCost);

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => "Odwiedziłeś klub" . 
            ($drugId ? " i zażyłeś {$data['drug_name']}!" : "!"),
        'effects' => [
            'energy_gained' => $drugId ? $data['energy_boost'] : 0,
            'money_spent' => $totalCost
        ]
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function calculateEnergyBoost($base_boost, $tolerance) {
    // Im wyższa tolerancja, tym mniejszy boost energii
    $effectiveness = 1 - ($tolerance / 200); // 100 tolerancji = 50% efektywności
    return max(1, floor($base_boost * $effectiveness));
}