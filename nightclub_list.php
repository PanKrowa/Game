<?php
// Data utworzenia: 2025-03-23 16:00:52
// Autor: PanKrowa
// Zależności:
// - Bootstrap 5.3.0+
// - nightclubs.js
// - Database.php
// - Character.php

if (!defined('IN_GAME')) {
    exit('Nie można wywołać tego pliku bezpośrednio.');
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Nightclubs</title>
    <script src="js/nightclubs.js"></script>
</head>
<body>
    <!-- Zakładki -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#my-clubs">Twoje kluby</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#available-clubs">Kluby do kupienia</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#other-clubs">Kluby graczy</a></li>
    </ul>

    <div class="tab-content">
        <!-- Twoje kluby -->
        <div class="tab-pane fade show active" id="my-clubs">
            <?php if (empty($owned_clubs)): ?>
                <p>Nie posiadasz żadnych klubów.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($owned_clubs as $club): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card bg-dark text-light">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($club['name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($club['description'] ?? 'Brak opisu'); ?></p>

                                    <div class="mb-3">
                                        <h6>Statystyki (24h):</h6>
                                        <p>Odwiedziny: <?php echo $club['daily_visitors'] ?? 0; ?></p>
                                        <p>Przychód: $<?php echo number_format($club['daily_income'] ?? 0, 2); ?></p>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Dostępne narkotyki:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-dark">
                                                <thead>
                                                    <tr>
                                                        <th>Nazwa</th>
                                                        <th>Ilość</th>
                                                        <th>Cena</th>
                                                        <th>Akcje</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($club['drugs'])): ?>
                                                        <?php foreach ($club['drugs'] as $drug): ?>
                                                            <?php if ($drug !== null): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($drug['name']); ?></td>
                                                                <td><?php echo $drug['quantity'] ?? 0; ?></td>
                                                                <td>$<?php echo number_format($drug['price'] ?? 0, 2); ?></td>
                                                                <td>
                                                                    <button class="btn btn-primary btn-sm"
                                                                            onclick="setDrugPrice(<?php echo $club['id']; ?>, <?php echo $drug['id']; ?>)"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Zmień cenę narkotyku">
                                                                        Cena
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center">Brak narkotyków w klubie</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <button class="btn btn-success btn-sm w-100 mb-2"
                                                onclick="showAddDrugsModal(<?php echo $club['id']; ?>)"
                                                <?php echo empty($owned_drugs) ? 'disabled' : ''; ?>
                                                data-bs-toggle="tooltip"
                                                title="<?php echo empty($owned_drugs) ? 'Nie masz żadnych narkotyków do dodania' : 'Dodaj narkotyki do klubu'; ?>">
                                            Dodaj narkotyki do klubu
                                        </button>
                                    </div>

                                    <button class="btn btn-danger w-100"
                                            onclick="sellClub(<?php echo $club['id']; ?>)"
                                            data-bs-toggle="tooltip"
                                            title="Sprzedaj klub za <?php echo number_format(($club['price'] ?? 0) * 0.7, 2); ?> $">
                                        Sprzedaj klub ($<?php echo number_format(($club['price'] ?? 0) * 0.7, 2); ?>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kluby do kupienia -->
        <div class="tab-pane fade" id="available-clubs">
            <div class="row">
                <?php foreach ($available_clubs as $club): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($club['name']); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($club['description'] ?? 'Brak opisu'); ?></p>

                                <p>Pojemność: <?php echo $club['max_capacity'] ?? 0; ?> osób</p>
                                <p>Wejściówka: $<?php echo number_format($club['entry_fee'] ?? 0, 2); ?></p>
                                <p>Cena: $<?php echo number_format($club['price'] ?? 0, 2); ?></p>

                                <button class="btn btn-success w-100" 
                                        onclick="buyClub(<?php echo $club['id']; ?>)"
                                        <?php echo $character->getCash() < ($club['price'] ?? 0) ? 'disabled' : ''; ?>
                                        data-bs-toggle="tooltip"
                                        title="<?php echo $character->getCash() < ($club['price'] ?? 0) ? 'Nie masz wystarczająco pieniędzy' : 'Kup ten klub'; ?>">
                                    Kup klub
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Kluby innych graczy -->
        <div class="tab-pane fade" id="other-clubs">
            <div class="row">
                <?php foreach ($other_clubs as $club): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($club['name']); ?>
                                    <small class="text-muted">
                                        <?php if ($club['character_id'] == $character->getId()): ?>
                                            (Twój klub)
                                        <?php else: ?>
                                            (Właściciel: <?php echo htmlspecialchars($club['owner_name'] ?? ''); ?>)
                                        <?php endif; ?>
                                    </small>
                                </h5>
                                <p class="text-muted"><?php echo htmlspecialchars($club['description'] ?? 'Brak opisu'); ?></p>

                                <div class="mb-3">
                                    <h6>Dostępne narkotyki:</h6>
                                    <?php if (!empty($club['drugs'])): ?>
                                        <?php foreach ($club['drugs'] as $drug): ?>
                                            <?php if ($drug !== null && $drug['quantity'] > 0): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                                    <div>
                                                        <span class="fw-bold"><?php echo htmlspecialchars($drug['name']); ?></span>
                                                        <br>
                                                        <small class="text-muted">
                                                            Energia: +<?php echo $drug['energy_boost'] ?? 0; ?>
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="d-block">$<?php echo number_format($drug['price'] ?? 0, 2); ?></span>
                                                        <small class="text-muted">
                                                            Dostępne: <?php echo $drug['quantity'] ?? 0; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>Brak dostępnych narkotyków.</p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($club['current_visitors'])): ?>
                                    <div class="mb-3">
                                        <h6>Obecni goście:</h6>
                                        <p><?php echo htmlspecialchars($club['current_visitors']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <p>Wejściówka: $<?php echo number_format($club['entry_fee'] ?? 0, 2); ?></p>

                                    <?php if ($club['character_id'] != $character->getId()): ?>
                                        <?php if (($club['visits_last_hour'] ?? 0) < 5): ?>
                                            <button class="btn btn-primary w-100 mb-2" 
                                                    onclick="showVisitClubModal(<?php echo $club['id']; ?>)"
                                                    <?php echo $character->getCash() < ($club['entry_fee'] ?? 0) ? 'disabled' : ''; ?>
                                                    data-bs-toggle="tooltip"
                                                    title="<?php echo $character->getCash() < ($club['entry_fee'] ?? 0) ? 'Nie masz wystarczająco pieniędzy' : 'Wejdź do klubu'; ?>">
                                                Wejdź do klubu
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100 mb-2" disabled
                                                    data-bs-toggle="tooltip"
                                                    title="Możesz odwiedzić klub maksymalnie 5 razy na godzinę">
                                                Limit wizyt osiągnięty (5/h)
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            To twój klub - możesz nim zarządzać w zakładce "Twoje kluby"
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Modal dodawania narkotyków -->
        <div class="modal fade" id="addDrugsModal" tabindex="-1" data-bs-theme="dark">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title">Dodaj narkotyki do klubu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addDrugsForm">
                            <div class="mb-3">
                                <label for="drugSelect" class="form-label">Wybierz narkotyk</label>
                                <select class="form-select" id="drugSelect" name="drug_id" required>
                                    <?php foreach ($owned_drugs as $drug): ?>
                                        <option value="<?php echo $drug['id']; ?>" data-quantity="<?php echo $drug['quantity']; ?>">
                                            <?php echo htmlspecialchars($drug['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="drugQuantity" class="form-label">Ilość</label>
                                <input type="number" class="form-control" id="drugQuantity" name="quantity" min="1" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="button" class="btn btn-primary" onclick="addDrugsToClub()">Dodaj</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal odwiedzania klubu -->
        <div class="modal fade" id="visitClubModal" tabindex="-1" data-bs-theme="dark">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title">Odwiedź klub</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Opłata wejściowa: $<span id="entryFee"></span></h6>
                        <div class="mb-3">
                            <label for="visitDrugSelect" class="form-label">Wybierz narkotyk (opcjonalne)</label>
                            <select class="form-select" id="visitDrugSelect">
                                <option value="">Bez narkotyku (tylko wejście)</option>
                            </select>
                        </div>
                        <div id="drugInfo" class="d-none">
                            <h6>Informacje o narkotyku:</h6>
                            <p>Energia: +<span id="energyBoost"></span></p>
                            <p>Uzależnienie: <span id="addictionRate"></span>%</p>
                            <p>Cena: $<span id="drugPrice"></span></p>
                        </div>
                        <h6>Całkowity koszt: $<span id="totalCost"></span></h6>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="button" class="btn btn-primary" onclick="visitClub()">Odwiedź</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>