<?php
session_start();
require_once '../includes/Database.php';
require_once '../includes/Character.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Połączenie z bazą danych
$db = Database::getInstance();

// Pobierz dane użytkownika
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Aktualizacja e-maila
    if (!empty($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Nieprawidłowy adres e-mail.';
        } else {
            $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            $_SESSION['success'] = 'Adres e-mail został zaktualizowany.';
        }
    }

    // Aktualizacja hasła
    if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $errors[] = 'Hasła nie są zgodne.';
        } else {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$password, $_SESSION['user_id']]);
            $_SESSION['success'] = 'Hasło zostało zaktualizowane.';
        }
    }

    if (empty($errors)) {
        header('Location: settings.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Ustawienia</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <p class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= $error; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="settings.php" method="post">
        <div>
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>">
        </div>
        <div>
            <label for="password">Nowe hasło:</label>
            <input type="password" id="password" name="password">
        </div>
        <div>
            <label for="confirm_password">Potwierdź nowe hasło:</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        <div>
            <button type="submit">Zaktualizuj</button>
        </div>
    </form>

    <p><a href="dashboard.php">Powrót do panelu</a></p>
</body>
</html>