<?php
session_start();
require_once '../config.php';
require_once '../Database.php';
require_once '../Character.php';
require_once '../Nightclub.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    die("Unauthorized");
}

$club_id = filter_input(INPUT_GET, 'club_id', FILTER_VALIDATE_INT);
if (!$club_id) {
    die("Invalid club ID");
}

$club = Nightclub::getById($club_id);
if (!$club) {
    die("Club not found");
}

$drugs = $club->getAvailableDrugs();
?>

<div class="table-responsive">
    <table class="table table-dark table-bordered">
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Cena</th>
                <th>Dostępne</th>
                <th>Ilość</th>
                <th>Akcja</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($drugs as $drug): ?>
                <tr>
                    <td><?php echo htmlspecialchars($drug['name']); ?></td>
                    <td>$<?php echo number_format($drug['price'], 2); ?></td>
                    <td><?php echo $drug['quantity_available']; ?>g</td>
                    <td>
                        <input type="number" class="form-control form-control-sm bg-dark text-light" 
                               id="quantity_<?php echo $drug['id']; ?>"
                               min="1" max="<?php echo $drug['quantity_available']; ?>" value="1">
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="buyDrug(<?php echo $club_id; ?>, <?php echo $drug['id']; ?>)">
                            Kup
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>