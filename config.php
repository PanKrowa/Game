<?php
// Data utworzenia: 2025-03-23 14:53:15
// Autor: PanKrowa

// Sprawdź czy sesja już nie jest wystartowana
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sprawdź czy stała IN_GAME już nie jest zdefiniowana
if (!defined('IN_GAME')) {
    define('IN_GAME', true);
}

// Dołączenie wymaganych plików
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Character.php';

// Twoja istniejąca klasa Config
if (!class_exists('Config')) {
    class Config {
        // Metadane
        const CREATED_AT = '2025-03-23 10:31:12';
        const CREATED_BY = 'PanKrowa';
        const GAME_VERSION = '1.0.0';
        
        // Ustawienia bazy danych
        const DB_HOST = 'localhost';
        const DB_NAME = 'crimcity';
        const DB_USER = 'emilbajsarowicz';
        const DB_PASS = 'Gizmo2015@';
        const DB_CHARSET = 'utf8mb4';
        
        // Limity postaci
        const MAX_HEALTH = 100;
        const MAX_ENERGY = 100;
        const ENERGY_REGEN_MINUTES = 5;     // Co ile minut regeneruje się energia
        const ENERGY_REGEN_AMOUNT = 1;      // Ile energii regeneruje się za 1 raz
        const HEALTH_REGEN_MINUTES = 30;    // Co ile minut regeneruje się zdrowie
        const HEALTH_REGEN_AMOUNT = 1;      // Ile zdrowia regeneruje się za 1 raz
        
        // Walka
        const MIN_FIGHT_LEVEL = 3;          // Minimalny poziom do PVP
        const FIGHT_ENERGY_COST = 5;        // Koszt energii za walkę
        const MIN_CASH_STEAL_PERCENT = 5;   // Minimalny % gotówki do kradzieży
        const MAX_CASH_STEAL_PERCENT = 20;  // Maksymalny % gotówki do kradzieży
        const MAX_DAILY_FIGHTS = 50;        // Dzienny limit walk
        const FIGHT_COOLDOWN_MINUTES = 5;   // Przerwa między walkami
        
        // Doświadczenie
        const BASE_EXPERIENCE = 100;         // Podstawowe XP za poziom
        const EXPERIENCE_MULTIPLIER = 1.5;   // Mnożnik XP na kolejny poziom
        const MAX_LEVEL = 100;              // Maksymalny poziom postaci
        const LEVEL_UP_HEALTH_BONUS = 5;    // Bonus zdrowia za level up
        const LEVEL_UP_ENERGY_BONUS = 2;    // Bonus energii za level up
        
        // Trening
        const MAX_SKILL_LEVEL = 100;        // Maksymalny poziom umiejętności
        const TRAINING_ENERGY_COST = 3;     // Koszt energii za trening
        const TRAINING_COST_MULTIPLIER = 100; // Mnożnik kosztu treningu
        const MAX_DAILY_TRAINING = 100;     // Dzienny limit treningów
        
        // Szpital
        const HOSPITAL_HEAL_COST_PER_HP = 10;    // Koszt leczenia za 1 HP
        const HOSPITAL_DETOX_COST_PER_POINT = 500; // Koszt detoksu za 1 punkt tolerancji
        const HOSPITAL_NAME_CHANGE_COST = 5000;   // Koszt zmiany nazwy
        const MIN_HOSPITAL_STAY = 1;             // Minimalny czas pobytu (h)
        const MAX_HOSPITAL_STAY = 24;            // Maksymalny czas pobytu (h)
        
        // Więzienie
        const JAIL_BRIBE_COST_PER_HOUR = 1000;  // Koszt łapówki za godzinę
        const JAIL_ESCAPE_ENERGY_COST = 10;     // Koszt energii za próbę ucieczki
        const MAX_JAIL_ESCAPE_ATTEMPTS = 3;      // Limit prób ucieczki na godzinę
        const MIN_JAIL_TIME = 1;                // Minimalny czas odsiadki (h)
        const MAX_JAIL_TIME = 48;               // Maksymalny czas odsiadki (h)
        
        // Gangi
        const MAX_GANG_MEMBERS = 20;        // Maksymalna liczba członków gangu
        const GANG_CREATE_COST = 50000;     // Koszt założenia gangu
        const MIN_GANG_LEVEL = 10;          // Minimalny poziom do założenia gangu
        const GANG_NAME_MIN_LENGTH = 3;     // Minimalna długość nazwy gangu
        const GANG_NAME_MAX_LENGTH = 20;    // Maksymalna długość nazwy gangu
        
        // Budynki
        const MAX_BUILDING_LEVEL = 10;      // Maksymalny poziom budynku
        const MAX_BUILDINGS_PER_TYPE = 3;   // Maksymalna liczba budynków danego typu
        const BUILDING_UPGRADE_MULTIPLIER = 1.5; // Mnożnik kosztu ulepszenia
        const MIN_BUILDING_COLLECTION_INTERVAL = 1; // Minimalny czas między zbieraniem (h)
        
        // Przedmioty
        const MAX_INVENTORY_SLOTS = 100;    // Maksymalna pojemność ekwipunku
        const MAX_MARKET_LISTINGS = 10;     // Maksymalna liczba ofert na rynku
        const MARKET_LISTING_DURATION = 72; // Czas trwania oferty na rynku (h)
        const MARKET_FEE_PERCENT = 5;       // Opłata za wystawienie przedmiotu (%)
        
        // Napady
        const ROBBERY_MAX_DAILY = 50;       // Dzienny limit napadów
        const ROBBERY_FAIL_JAIL_CHANCE = 25;    // Szansa na więzienie przy nieudanym napadzie (%)
        const ROBBERY_FAIL_HOSPITAL_CHANCE = 25; // Szansa na szpital przy nieudanym napadzie (%)
        const ROBBERY_COOLDOWN_MINUTES = 5;     // Przerwa między napadami
        const MIN_ROBBERY_LEVEL = 2;            // Minimalny poziom do napadów
        
        // Limity czasowe
        const SESSION_LIFETIME = 3600;      // Czas trwania sesji (s)
        const RESET_HOUR = 0;               // Godzina resetu dziennych limitów (UTC)
        const MAINTENANCE_MODE = false;      // Tryb konserwacji
        
        // Walidacja
        const MIN_PASSWORD_LENGTH = 8;      // Minimalna długość hasła
        const MAX_PASSWORD_LENGTH = 72;     // Maksymalna długość hasła
        const MIN_USERNAME_LENGTH = 3;      // Minimalna długość nazwy użytkownika
        const MAX_USERNAME_LENGTH = 20;     // Maksymalna długość nazwy użytkownika
    }
}
// Inicjalizacja obiektu postaci jeśli użytkownik jest zalogowany
if (isset($_SESSION['character_id'])) {
    try {
        $character = new Character($_SESSION['character_id']);
    } catch (Exception $e) {
        error_log("Error creating character: " . $e->getMessage());
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

// Funkcja debugowania
function debug($data) {
    if (true) { // Zmień na false na produkcji
        error_log(print_r($data, true));
    }
}

// Ustawienie strefy czasowej
date_default_timezone_set('UTC');

// Ustawienie obsługi błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Funkcja zabezpieczająca dane
function secure($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
// Funkcja sprawdzająca czy użytkownik jest zalogowany
function check_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
        debug("Użytkownik niezalogowany - przekierowanie do login.php");
        header('Location: login.php');
        exit();
    }
}

// Funkcja sprawdzająca czy użytkownik NIE jest zalogowany (dla stron logowania/rejestracji)
function check_not_logged() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['character_id'])) {
        debug("Użytkownik już zalogowany - przekierowanie do index.php");
        header('Location: index.php');
        exit();
    }
}

// Funkcja logująca użytkownika
function login_user($user_id, $character_id, $username) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['character_id'] = $character_id;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    
    // Aktualizuj last_login w bazie danych
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
        debug("Użytkownik $username zalogowany pomyślnie");
    } catch (Exception $e) {
        debug("Błąd podczas aktualizacji last_login: " . $e->getMessage());
    }
}

// Funkcja wylogowująca użytkownika
function logout_user() {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Funkcja sprawdzająca aktywność sesji
function check_session_timeout() {
    $timeout = Config::SESSION_LIFETIME;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        debug("Sesja wygasła - wylogowanie użytkownika");
        logout_user();
    }
    $_SESSION['last_activity'] = time();
}