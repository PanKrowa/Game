<?php
// Data utworzenia: 2025-03-23 09:27:19
// Autor: PanKrowa
// Current Date and Time (UTC): 2025-03-23 16:52:27
// Current User's Login: PanKrowa

session_start();
require_once 'includes/config.php';
require_once 'includes/Database.php';

// Przekieruj jeśli już zalogowany
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Dodaj funkcję do logowania debugowania
function logDebug($message) {
    $currentTime = date('Y-m-d H:i:s');
    $currentUser = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    error_log("[{$currentTime}] [{$currentUser}] {$message}");
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Walidacja
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Wypełnij wszystkie pola.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = 'Login musi mieć od 3 do 20 znaków.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Podaj prawidłowy adres email.';
    } elseif (strlen($password) < 6) {
        $error = 'Hasło musi mieć minimum 6 znaków.';
    } elseif ($password !== $password_confirm) {
        $error = 'Hasła nie są identyczne.';
    } else {
try {
    $db = Database::getInstance();
    
    // Sprawdź czy login jest zajęty
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = 'Ten login jest już zajęty.';
    } else {
        // Sprawdź czy email jest zajęty
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Ten email jest już używany.';
        } else {
            // Wszystko OK, można zarejestrować
            $db->beginTransaction();
            
            try {
                // Utwórz użytkownika
                $stmt = $db->prepare("
                    INSERT INTO users (
                        username, 
                        email, 
                        password, 
                        created_at, 
                        created_by
                    ) VALUES (
                        ?, 
                        ?, 
                        ?, 
                        NOW(),
                        'System'
                    )
                ");
                
                $stmt->execute([
                    $username, 
                    $email, 
                    password_hash($password, PASSWORD_DEFAULT)
                ]);
                
                $user_id = $db->lastInsertId();
                
                // Utwórz postać
                $stmt = $db->prepare("
                    INSERT INTO characters (
                        user_id,
                        name,
                        cash,
                        level,
                        experience,
                        max_health,
                        current_health,
                        max_energy,
                        current_energy,
                        respect_points,
                        defense,
                        attack,
                        base_health,
                        base_energy,
                        created_at,
                        created_by
                    ) VALUES (
                        ?, -- user_id
                        ?, -- name
                        1000, -- cash
                        1, -- level
                        0, -- experience
                        100, -- max_health
                        100, -- current_health
                        100, -- max_energy
                        100, -- current_energy
                        0, -- respect_points
                        0, -- defense
                        0, -- attack
                        100, -- base_health
                        100, -- base_energy
                        NOW(),
                        'System'
                    )
                ");
                
                $stmt->execute([$user_id, $username]);
                
                $db->commit();
                $success = true;
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
    }
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
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
    <title>CrimCity - Rejestracja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card bg-dark text-light border">
                    <div class="card-header">
                        <h1 class="text-center">Rejestracja</h1>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Konto zostało utworzone! Możesz się teraz zalogować.
                                <br>
                                <a href="login.php" class="btn btn-primary mt-3">Przejdź do logowania</a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Login</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Hasło</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Powtórz hasło</label>
                                    <input type="password" class="form-control" id="password_confirm" 
                                           name="password_confirm" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Zarejestruj się</button>
                                    <a href="login.php" class="btn btn-secondary">Powrót do logowania</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>