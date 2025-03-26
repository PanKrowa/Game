<?php
// Data utworzenia: 2025-03-23 09:45:39
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

$robbery_id = $_POST['robbery_id'] ?? 0;
if (!$robbery_id) {
    die(json_encode([
        'success' => false,
        'message' => 'Nie wybrano napadu.'
    ]));
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // Pobierz dane gracza
    $character = new Character($_SESSION['character_id']);
    
    // Sprawdź czy gracz może wykonać napad
    if ($character->isInJail()) {
        throw new Exception('Nie możesz wykonać napadu będąc w więzieniu.');
    }
    
    if ($character->isInHospital()) {
        throw new Exception('Nie możesz wykonać napadu będąc w szpitalu.');
    }

    // Pobierz dane napadu
    $stmt = $db->prepare("
        SELECT * FROM robberies WHERE id = ?
    ");
    $stmt->execute([$robbery_id]);
    $robbery = $stmt->fetch();

    if (!$robbery) {
        throw new Exception('Napad nie istnieje.');
    }

    // Sprawdź wymagania
    if ($character->getLevel() < $robbery['level_required']) {
        throw new Exception('Masz za niski poziom na ten napad.');
    }

    if ($character->getCurrentEnergy() < $robbery['energy_cost']) {
        throw new Exception('Nie masz wystarczająco energii.');
    }

    // Sprawdź limit napadów w ostatniej godzinie
    $stmt = $db->prepare("
        SELECT COUNT(*) as attempts
        FROM robbery_logs
        WHERE character_id = ?
        AND robbery_id = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$character->getId(), $robbery_id]);
    $attempts = $stmt->fetch();

    if ($attempts['attempts'] >= Config::MAX_ROBBERY_ATTEMPTS) {
        throw new Exception('Osiągnąłeś limit prób tego napadu w ostatniej godzinie.');
    }

    // Wykonaj napad
    $success = rand(1, 100) <= $robbery['success_rate'];
    $reward = 0;
    $experience = 0;

    if ($success) {
        $reward = rand($robbery['min_reward'], $robbery['max_reward']);
        $experience = $robbery['experience_reward'];
        
        // Dodaj nagrodę i doświadczenie
        $character->addCash($reward);
        $character->addExperience($experience);
    } else {
        // 20% szans na złapanie przez policję
        $caught = rand(1, 100) <= 20;
        if ($caught) {
            // Idź do więzienia na 1-3 godziny
            $jail_hours = rand(1, 3);
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
                "Nieudany napad na " . $robbery['name'],
                $jail_hours,
                $jail_until->format('Y-m-d H:i:s')
            ]);
        }
    }

    // Odejmij energię
    $character->setCurrentEnergy($character->getCurrentEnergy() - $robbery['energy_cost']);

    // Zapisz log napadu
    $stmt = $db->prepare("
        INSERT INTO robbery_logs (
            character_id, robbery_id, success, 
            reward, created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $character->getId(),
        $robbery_id,
        $success,
        $reward
    ]);

    $db->commit();

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Napad udany!',
            'reward' => $reward,
            'experience' => $experience
        ]);
    } else {
        if ($caught) {
            echo json_encode([
                'success' => false,
                'message' => "Napad nieudany! Zostałeś złapany i trafiłeś do więzienia na {$jail_hours} godzin!"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Napad nieudany! Na szczęście udało ci się uciec!'
            ]);
        }
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