<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$character = new Character($_SESSION['character_id']);

// Obsługa zakupu/sprzedaży/użycia narkotyków
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['buy_drug'])) {
            $result = $character->buyDrug((int)$_POST['drug_id'], (int)$_POST['quantity']);
            $success_message = "Zakupiono narkotyk za $" . number_format($result['total_cost'], 2);
        } elseif (isset($_POST['sell_drug'])) {
            $result = $character->sellDrug((int)$_POST['drug_id'], (int)$_POST['quantity']);
            $success_message = "Sprzedano narkotyk za $" . number_format($result['total_earned'], 2);
        } elseif (isset($_POST['use_drug'])) {
            $result = $character->useDrug((int)$_POST['drug_id']);
            if ($result['overdosed']) {
                $error_message = "Przedawkowałeś! Trafiłeś do szpitala na " . $result['hospital_time'] . " godzin!";
            } else {
                $success_message = "Użyto narkotyku. Odzyskano " . $result['energy_restored'] . " energii.";
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Pobierz aktualne ceny narkotyków
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT d.*, m.current_price, 
    (SELECT quantity FROM character_drugs WHERE character_id = ? AND drug_id = d.id) as owned_quantity
    FROM drugs d
    JOIN drug_market m ON d.id = m.drug_id
    ORDER BY d.addiction_risk ASC
");
$stmt->execute([$_SESSION['character_id']]);
$drugs = $stmt->fetchAll();

// Pobierz tolerancję i uzależnienie
$stmt = $db->prepare("
    SELECT drug_id, tolerance_level, addiction_level 
    FROM character_drug_tolerances 
    WHERE character_id = ?
");
$stmt->execute([$_SESSION['character_id']]);
$tolerances = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="card bg-dark border-light">
    <div class="card-header border-light">
        <h4>Rynek narkotyków</h4>
    </div>
    <div class="card-body">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($drugs as $drug): ?>
                <div class="col-md-4 mb-4">
                    <div class="card bg-dark border-light">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($drug['name']); ?></h5>
                            <p class="card-text">
                                <strong>Cena:</strong> $<?php echo number_format($drug['current_price'], 2); ?><br>
                                <strong>Regeneracja energii:</strong> <?php echo $drug['energy_restore']; ?><br>
                                <strong>Ryzyko uzależnienia:</strong> <?php echo $drug['addiction_risk']; ?>%<br>
                                <strong>Ryzyko przedawkowania:</strong> <?php echo $drug['overdose_risk']; ?>%<br>
                                <strong>Posiadana ilość:</strong> <?php echo $drug['owned_quantity'] ?? 0; ?><br>
                                <?php if (isset($tolerances[$drug['id']])): ?>
                                    <strong>Tolerancja:</strong> <?php echo $tolerances[$drug['id']]; ?>%<br>
                                <?php endif; ?>
                            </p>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <form method="post" action="" class="mb-2">
                                        <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm mb-2">
                                        <button type="submit" name="buy_drug" class="btn btn-primary btn-sm w-100" 
                                            <?php echo ($character->isInJail() || $character->isInHospital()) ? 'disabled' : ''; ?>>
                                            Kup
                                        </button>
                                    </form>
                                </div>
                                <div class="col-6">
                                    <form method="post" action="" class="mb-2">
                                        <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $drug['owned_quantity'] ?? 0; ?>" class="form-control form-control-sm mb-2">
                                        <button type="submit" name="sell_drug" class="btn btn-warning btn-sm w-100" 
                                            <?php echo ($character->isInJail() || $character->isInHospital() || !($drug['owned_quantity'] ?? 0)) ? 'disabled' : ''; ?>>
                                            Sprzedaj
                                        </button>
                                    </form>
                                </div>
                                <div class="col-12">
                                    <form method="post" action="">
                                        <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                                        <button type="submit" name="use_drug" class="btn btn-danger btn-sm w-100" 
                                            <?php echo ($character->isInJail() || $character->isInHospital() || !($drug['owned_quantity'] ?? 0)) ? 'disabled' : ''; ?>>
                                            Użyj
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>