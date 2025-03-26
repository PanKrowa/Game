<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Drug Dealer</title>
    <link rel="stylesheet" href="path/to/bootstrap.css">
</head>
<body>
    <div class="container py-4">
        <h1>Drug Dealer</h1>
        <p>Yo rastamon! I got some fresh stuff directly from nature! By the way, it will cost you 5% to sell your stash. Every attempt to do business will cost you one transaction.</p>
        <p>I can do business with you & more time(s) today.</p>
        
        <h2>Drugs</h2>
        <div class="row">
            <?php foreach ($drugs as $drug): ?>
                <div class="col-md-4 mb-4">
                    <div class="card bg-dark text-light">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($drug['name']); ?></h5>
                            <p>Original price: $<?php echo number_format($drug['dealer_price'], 2); ?></p>
                            <form method="POST" action="actions/buy_drug.php">
                                <div class="mb-3">
                                    <label for="quantity-<?php echo $drug['id']; ?>" class="form-label">Quantity:</label>
                                    <input type="number" class="form-control" id="quantity-<?php echo $drug['id']; ?>" name="quantity" min="1" required>
                                    <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Buy</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="path/to/bootstrap.bundle.js"></script>
</body>
</html>