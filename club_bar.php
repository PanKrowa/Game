<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Club/Bar</title>
    <link rel="stylesheet" href="path/to/bootstrap.css">
</head>
<body>
    <div class="container py-4">
        <h1>Club/Bar</h1>
        
        <h2>Available Drugs</h2>
        <div class="row">
            <?php foreach ($drugs as $drug): ?>
                <div class="col-md-4 mb-4">
                    <div class="card bg-dark text-light">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($drug['name']); ?></h5>
                            <p>Price: $<?php echo number_format($drug['price'], 2); ?></p>
                            <p>Available: <?php echo number_format($drug['quantity']); ?>g</p>
                            <form method="POST" action="actions/consume_drug.php">
                                <div class="mb-3">
                                    <label for="quantity-<?php echo $drug['id']; ?>" class="form-label">Quantity:</label>
                                    <input type="number" class="form-control" id="quantity-<?php echo $drug['id']; ?>" name="quantity" min="1" required>
                                    <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Consume</button>
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