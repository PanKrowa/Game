<?php
session_start();
require_once '../config.php';
require_once '../Database.php';
require_once '../Character.php';
require_once '../Nightclub.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    header('Location: ../login.php');
    exit;
}

// Sprawdź czy metoda żądania to POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=nightclubs');
    exit;
}

try {
    $character = new Character($_SESSION['character_id']);
    
    // Pobierz i zwaliduj dane
    $club_id = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
    $bet_amount = filter_input(INPUT_POST, 'bet_amount', FILTER_VALIDATE_FLOAT);
    
    if (!$club_id || !$bet_amount) {
        throw new Exception("Nieprawidłowe dane.");
    }
    
    // Pobierz klub
    $club = Nightclub::getById($club_id);
    if (!$club) {
        throw new Exception("Klub nie istnieje.");
    }
    
    // Sprawdź czy gracz może wejść do klubu
    if (!$club->canEnter($character)) {
        throw new Exception("Nie spełniasz wymagań tego klubu.");
    }
    
    // Wykonaj grę
    $result = $club->play($character, $bet_amount);
    
    // Przekieruj z komunikatem
    header("Location: ../index.php?page=nightclubs&status=success&message=" . urlencode($result['message']));
    
} catch (Exception $e) {
    header("Location: ../index.php?page=nightclubs&status=error&message=" . urlencode($e->getMessage()));
}