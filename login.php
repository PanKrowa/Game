<?php
// Data utworzenia: 2025-03-23 17:47:15
// Autor: PanKrowa

// Ustawienia sesji MUSZĄ być przed session_start()
ini_set('session.cookie_lifetime', '0');
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

// Teraz możemy rozpocząć sesję
session_start();

require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'Authentication.php';

// Debug info
error_log("=== Login.php start ===");
error_log("Session ID: " . session_id());
error_log("POST data: " . print_r($_POST, true));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Wypełnij wszystkie pola.';
    } else {
        try {
            $db = Database::getInstance();
            
            // Debug database connection
            error_log("Database connection established");

            $stmt = $db->prepare("
                SELECT u.id as user_id, u.password, c.id as character_id 
                FROM users u 
                LEFT JOIN characters c ON c.user_id = u.id 
                WHERE u.username = ?
            ");
            
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Query result: " . print_r($user, true));

            if ($user && password_verify($password, $user['password'])) {
                // Ustaw sesję
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['character_id'] = $user['character_id'];
                $_SESSION['username'] = $username;
                $_SESSION['last_activity'] = time();

                error_log("Session set: " . print_r($_SESSION, true));

                // Aktualizuj last_login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['user_id']]);

                // Upewnij się, że dane sesji zostały zapisane
                session_write_close();

                error_log("Redirecting to index.php");
                header("Location: index.php");
                exit();
            } else {
                $error = 'Nieprawidłowy login lub hasło.';
                error_log("Login failed for user: $username");
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $error = 'Błąd serwera: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrimCity - Logowanie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card bg-dark text-light border">
                    <div class="card-header">
                        <h1 class="text-center">Logowanie</h1>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">Login</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Hasło</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Zaloguj się</button>
                                <a href="register.php" class="btn btn-secondary">Zarejestruj się</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>