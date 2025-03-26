<?php
// Data utworzenia: 2025-03-23 16:08:50
// Autor: PanKrowa
// Zależności:
// - Bootstrap 5.3.0+
// - nightclubs.js
// - Database.php
// - Character.php

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

$db = Database::getInstance();
$view = $_GET['view'] ?? 'list';
$clubId = (int)($_GET['id'] ?? 0);

try {
    // Inicjalizacja zmiennych
    $owned_clubs = [];
    $available_clubs = [];
    $other_clubs = [];
    $owned_drugs = [];

    // Pobierz kluby gracza wraz z narkotykami
    $stmt = $db->prepare("
        SELECT 
            c.*,
            (
                SELECT COUNT(DISTINCT character_id)
                FROM club_visits
                WHERE club_id = c.id
                AND visited_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ) as daily_visitors,
            (
                SELECT SUM(quantity * price_per_unit)
                FROM club_drug_sales
                WHERE club_id = c.id
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ) as daily_income,
            GROUP_CONCAT(
                DISTINCT JSON_OBJECT(
                    'id', d.id,
                    'name', d.name,
                    'quantity', cd.quantity,
                    'price', cd.price
                )
            ) as drugs_json
        FROM clubs c
        LEFT JOIN club_drugs cd ON c.id = cd.club_id
        LEFT JOIN drugs d ON cd.drug_id = d.id
        WHERE c.character_id = ?
        GROUP BY c.id
    ");
    
    if (!$stmt) {
        throw new Exception("Błąd przygotowania zapytania: " . $db->errorInfo()[2]);
    }

    if (!$stmt->execute([$character->getId()])) {
        throw new Exception("Błąd wykonania zapytania: " . $stmt->errorInfo()[2]);
    }

    $owned_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Przetwórz dane o narkotykach dla klubów gracza
    foreach ($owned_clubs as &$club) {
        if (!empty($club['drugs_json'])) {
            $drugs_array = explode(',', $club['drugs_json']);
            $club['drugs'] = array_map(function($drug_json) {
                $drug = json_decode($drug_json, true);
                return $drug ?: null;
            }, $drugs_array);
        } else {
            $club['drugs'] = [];
        }
        unset($club['drugs_json']);
    }
    unset($club);

    // Pobierz dostępne kluby do kupienia
    $stmt = $db->prepare("
        SELECT c.*
        FROM clubs c
        WHERE c.character_id = 0
    ");
    
    if (!$stmt->execute()) {
        throw new Exception("Błąd pobierania dostępnych klubów");
    }
    
    $available_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pobierz kluby innych graczy
    $stmt = $db->prepare("
        SELECT 
            c.*,
            ch.name as owner_name,
            GROUP_CONCAT(
                DISTINCT JSON_OBJECT(
                    'id', d.id,
                    'name', d.name,
                    'quantity', cd.quantity,
                    'price', cd.price,
                    'energy_boost', d.energy_boost,
                    'addiction_rate', d.addiction_rate
                )
            ) as drugs_json,
            (
                SELECT COUNT(*) 
                FROM club_visits cv 
                WHERE cv.club_id = c.id 
                AND cv.character_id = ? 
                AND cv.visited_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ) as visits_last_hour,
            (
                SELECT GROUP_CONCAT(DISTINCT ch2.name)
                FROM club_visits cv2
                JOIN characters ch2 ON cv2.character_id = ch2.id
                WHERE cv2.club_id = c.id
                AND cv2.visited_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ) as current_visitors
        FROM clubs c
        JOIN characters ch ON c.character_id = ch.id
        LEFT JOIN club_drugs cd ON c.id = cd.club_id
        LEFT JOIN drugs d ON cd.drug_id = d.id
        WHERE c.character_id IS NOT NULL
        GROUP BY c.id
    ");
    
    if (!$stmt->execute([$character->getId()])) {
        throw new Exception("Błąd pobierania klubów innych graczy");
    }
    
    $other_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Przetwórz dane o narkotykach dla klubów innych graczy
    foreach ($other_clubs as &$club) {
        if (!empty($club['drugs_json'])) {
            $drugs_array = explode(',', $club['drugs_json']);
            $club['drugs'] = array_map(function($drug_json) {
                $drug = json_decode($drug_json, true);
                return $drug ?: null;
            }, $drugs_array);
        } else {
            $club['drugs'] = [];
        }
        unset($club['drugs_json']);
    }
    unset($club);

    // Pobierz narkotyki gracza do sprzedaży w klubie
    $stmt = $db->prepare("
        SELECT d.*, cd.quantity
        FROM character_drugs cd
        JOIN drugs d ON cd.drug_id = d.id
        WHERE cd.character_id = ?
        AND cd.quantity > 0
    ");
    
    if (!$stmt->execute([$character->getId()])) {
        throw new Exception("Błąd pobierania narkotyków gracza");
    }
    
    $owned_drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugowanie
    error_log("Owned clubs count: " . count($owned_clubs));
    error_log("Available clubs count: " . count($available_clubs));
    error_log("Other clubs count: " . count($other_clubs));
    error_log("Owned drugs count: " . count($owned_drugs));

} catch (Exception $e) {
    error_log("Error in nightclubs.php: " . $e->getMessage());
    die('<div class="alert alert-danger">Wystąpił błąd podczas ładowania danych. Spróbuj ponownie później.</div>');
}

// Ładowanie odpowiedniego widoku
if ($view === 'club') {
    include('views/nightclub_view.php');
} else {
    include('views/nightclub_list.php');
}