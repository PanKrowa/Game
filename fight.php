<?php
// Data utworzenia: 2025-03-23 09:44:46
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

$opponent_id = $_POST['opponent_id'] ?? 0;
if (!$opponent_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie wybrano przeciwnika.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane atakującego
    $attacker = new Character($_SESSION['character_id']);
    
    // Sprawdź czy atakujący może walczyć
    if ($attacker->isInJail()) {
        throw new Exception('Nie możesz walczyć będąc w więzieniu.');
    }
    
    if ($attacker->isInHospital()) {
        throw new Exception('Nie możesz walczyć będąc w szpitalu.');
    }
    
    if ($attacker->getCurrentEnergy() < Config::MIN_FIGHT_ENERGY) {
        throw new Exception('Nie masz wystarczająco energii.');
    }

    // Pobierz dane przeciwnika
    $stmt = $db->prepare("
        SELECT 
            c.*, 
            COALESCE(g.name, '') as gang_name
        FROM characters c
        LEFT JOIN gang_members gm ON c.id = gm.character_id
        LEFT JOIN gangs g ON gm.gang_id = g.id
        WHERE c.id = ?
    ");
    $stmt->execute([$opponent_id]);
    $opponent_data = $stmt->fetch();

    if (!$opponent_data) {
        throw new Exception('Przeciwnik nie istnieje.');
    }

    if ($opponent_data['in_jail']) {
        throw new Exception('Przeciwnik jest w więzieniu.');
    }

    if ($opponent_data['in_hospital']) {
        throw new Exception('Przeciwnik jest w szpitalu.');
    }

    // Oblicz statystyki walki
    $attacker_damage = calculateDamage($attacker);
    $opponent_defense = calculateDefense($opponent_data);
    $final_damage = max(1, $attacker_damage - $opponent_defense);

    // Oblicz obrażenia zwrotne (30% obrażeń atakującego)
    $return_damage = ceil($final_damage * 0.3);

    // Aktualizuj zdrowie przeciwnika
    $stmt = $db->prepare("
        UPDATE characters 
        SET current_health = GREATEST(0, current_health - ?) 
        WHERE id = ?
    ");
    $stmt->execute([$final_damage, $opponent_id]);

    // Aktualizuj zdrowie atakującego
    $attacker->setCurrentHealth($attacker->getCurrentHealth() - $return_damage);
    $attacker->setCurrentEnergy($attacker->getCurrentEnergy() - Config::MIN_FIGHT_ENERGY);

    // Sprawdź czy przeciwnik został pokonany
    $money_stolen = 0;
    if ($opponent_data['current_health'] - $final_damage <= 0) {
        // Ukradnij 10% gotówki przeciwnika
        $money_stolen = ceil($opponent_data['cash'] * 0.1);
        
        $stmt = $db->prepare("
            UPDATE characters 
            SET cash = cash - ?
            WHERE id = ?
        ");
        $stmt->execute([$money_stolen, $opponent_id]);

        $attacker->addCash($money_stolen);

        // Wyślij przeciwnika do szpitala
        $hospital_time = new DateTime();
        $hospital_time->modify('+1 hour');
        
        $stmt = $db->prepare("
            UPDATE characters 
            SET in_hospital = 1, hospital_until = ?
            WHERE id = ?
        ");
        $stmt->execute([$hospital_time->format('Y-m-d H:i:s'), $opponent_id]);
    }

    // Zapisz log walki
    $stmt = $db->prepare("
        INSERT INTO combat_logs (
            attacker_id, defender_id, damage_dealt, 
            money_stolen, winner_id, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $attacker->getId(),
        $opponent_id,
        $final_damage,
        $money_stolen,
        ($opponent_data['current_health'] - $final_damage <= 0) ? $attacker->getId() : $opponent_id
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $opponent_data['current_health'] - $final_damage <= 0 ? 
            'Wygrałeś walkę!' : 'Przeciwnik przeżył atak!',
        'damage' => $final_damage,
        'received_damage' => $return_damage,
        'money_stolen' => $money_stolen
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

function calculateDamage($character) {
    $base_damage = $character->getStrength() * 2;
    
    // Pobierz bonusy z ekwipunku
    $stmt = Database::getInstance()->prepare("
        SELECT SUM(i.damage) as total_damage
        FROM character_items ci
        JOIN items i ON ci.item_id = i.id
        WHERE ci.character_id = ? AND ci.equipped = 1
    ");
    $stmt->execute([$character->getId()]);
    $equipment = $stmt->fetch();
    
    return $base_damage + ($equipment['total_damage'] ?? 0);
}

function calculateDefense($character_data) {
    $base_defense = $character_data['defense'] * 1.5;
    
    // Pobierz bonusy z ekwipunku
    $stmt = Database::getInstance()->prepare("
        SELECT SUM(i.defense) as total_defense
        FROM character_items ci
        JOIN items i ON ci.item_id = i.id
        WHERE ci.character_id = ? AND ci.equipped = 1
    ");
    $stmt->execute([$character_data['id']]);
    $equipment = $stmt->fetch();
    
    return $base_defense + ($equipment['total_defense'] ?? 0);
}