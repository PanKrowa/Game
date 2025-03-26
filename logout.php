<?php
// Data utworzenia: 2025-03-23 16:26:45
// Autor: PanKrowa

// Rozpocznij sesję jeśli nie jest rozpoczęta
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zapisz username przed wyczyszczeniem sesji (dla komunikatu)
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Zniszcz wszystkie dane sesji
$_SESSION = array();

// Zniszcz ciasteczko sesji
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Zniszcz sesję
session_destroy();

// Przekieruj do strony logowania z komunikatem
header('Location: login.php?logout=1&user=' . urlencode($username));
exit();