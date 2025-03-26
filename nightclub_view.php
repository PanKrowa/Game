<?php
// Data utworzenia: 2025-03-23 14:10:22
// Autor: PanKrowa

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}

if (!$clubId) {
    echo '<div class="alert alert-danger">Nieprawidłowy identyfikator klubu.</div>';
    return;
}

$db = Database::getInstance();

// Pobierz informacje o klubie
$stmt = $db->prepare("
    SELECT 
        c.*,
        ch.name as owner_name,
        (
            SELECT GROUP_CONCAT(DISTINCT ch2.name)
            FROM club_visits cv2
            JOIN characters ch2 ON cv2.character_id = ch2.id
            WHERE cv2.club_id = c.id
            AND cv2.visited_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ) as current_visitors
    FROM clubs c
    JOIN characters ch ON c.character_id = ch.id
    WHERE c.id = ?
");
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
    echo '<div class="alert alert-danger">Ten klub nie istnieje.</div>';
    return;
}

// Pobierz dostępne narkotyki
$stmt = $db->prepare("
    SELECT 
        cd.*,
        d.name as drug_name,
        d.energy_boost,
        d.addiction_rate
    FROM club_drugs cd
    JOIN drugs d ON cd.drug_id = d.id
    WHERE cd.club_id = ?
    AND cd.quantity > 0
");
$stmt->execute([$clubId]);
$available_drugs = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="card bg-dark text-light">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><?php echo htmlspecialchars($club['name']); ?></h4>
            <a href="nightclubs.php" class="btn btn-secondary">Powrót</a>
        </div>
        <div class="card-body">
            <h5>Właściciel: <?php echo htmlspecialchars($club['owner_name']); ?></h5>
            <p class="text-muted"><?php echo htmlspecialchars($club['description']); ?></p>

            <?php if (!empty($club['current_visitors'])): ?>
                <div class="mb-4">
                    <h5>Obecni goście:</h5>
                    <p><?php echo htmlspecialchars($club['current_visitors']); ?></p>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <h5>Dostępne narkotyki:</h5>
                <?php if (empty($available_drugs)): ?>
                    <p>Brak dostępnych narkotyków.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($available_drugs as $drug): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark