<?php
// Data utworzenia: 2025-03-23 17:43:15
// Autor: PanKrowa

// 1. Najpierw definiujemy stałą IN_GAME
if (!defined('IN_GAME')) {
    define('IN_GAME', true);
}

// 2. Ładujemy config
require_once 'includes/config.php';

// 3. Ładujemy pozostałe zależności
require_once 'includes/Database.php';
require_once 'includes/Character.php'; // Upewnij się, że ta ścieżka jest prawidłowa!

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Inicjalizacja postaci
    $character = new Character($_SESSION['character_id']);
    
    // Aktualizacja statystyk postaci
    $character->updateStats();
    
    // Pobierz czas od ostatniej aktualizacji energii
    $last_energy = new DateTime($character->getLastEnergyUpdate());
    $now = new DateTime();
    $energy_time = $now->diff($last_energy);
    
    // Sprawdź czy postać jest w więzieniu/szpitalu
    $location = 'Na mieście';
    $status_class = 'text-success';
    
    if ($character->isInJail()) {
        $location = 'W więzieniu';
        $status_class = 'text-danger';
    } elseif ($character->isInHospital()) {
        $location = 'W szpitalu';
        $status_class = 'text-warning';
    }
} catch (Exception $e) {
    die('Błąd: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrimCity - <?php echo htmlspecialchars($character->getName()); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
        <div class="container">
            <a class="navbar-brand" href="index.php">CrimCity</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=dashboard">Panel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=robberies">Napady</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=nightclubs">Kluby</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=buildings">Budynki</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=fights">Walki</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=gang">Gang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=market">Rynek</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=hospital">Szpital</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=jail">Więzienie</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($character->getName()); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="index.php?page=profile">Profil</a></li>
                            <li><a class="dropdown-item" href="index.php?page=settings">Ustawienia</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Wyloguj</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Status bar -->
    <div class="bg-dark border-bottom py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <span class="<?php echo $status_class; ?>">
                        <?php echo $location; ?>
                    </span>
                </div>
                <div class="col-md-9">
                    <div class="d-flex justify-content-end gap-4">
                        <div>Poziom: <?php echo $character->getLevel(); ?></div>
                        <div>Kasa: $<?php echo number_format($character->getCash(), 2); ?></div>
                        <div>Respekt: <?php echo number_format($character->getRespectPoints()); ?></div>
                        <div>
                            Energia: <?php echo $character->getCurrentEnergy(); ?>/<?php echo $character->getMaxEnergy(); ?>
                            (<?php echo $energy_time->format('%H:%I:%S'); ?>)
                        </div>
                        <div>
                            Zdrowie: <?php echo $character->getCurrentHealth(); ?>/<?php echo $character->getMaxHealth(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <main class="py-4">
        <?php
        $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
        $allowed_pages = [
            'dashboard', 'robberies', 'nightclubs', 'buildings', 
            'fights', 'gang', 'market', 'hospital', 'jail', 
            'profile', 'settings'
        ];
        
        if (in_array($page, $allowed_pages)) {
            $file = "pages/{$page}.php";
            if (file_exists($file)) {
                include $file;
            } else {
                echo '<div class="container"><div class="alert alert-danger">Strona nie istnieje.</div></div>';
            }
        } else {
            echo '<div class="container"><div class="alert alert-danger">Niedozwolona strona.</div></div>';
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 CrimCity. Wszystkie prawa zastrzeżone.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">
                        Serwer: UTC 
                        <span id="serverTime">
                            <?php echo date('Y-m-d H:i:s'); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Aktualizacja czasu serwera
    function updateServerTime() {
        const timeElement = document.getElementById('serverTime');
        const currentTime = new Date(timeElement.textContent);
        
        setInterval(() => {
            currentTime.setSeconds(currentTime.getSeconds() + 1);
            timeElement.textContent = currentTime.toISOString().slice(0, 19).replace('T', ' ');
        }, 1000);
    }

    // Inicjalizacja
    document.addEventListener('DOMContentLoaded', () => {
        updateServerTime();
    });
    </script>
</body>
</html>