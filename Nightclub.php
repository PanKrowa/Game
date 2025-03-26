<?php
declare(strict_types=1);

class Nightclub {
    // Stałe dla typów narkotyków
    const DRUG_TYPES = [
        'weed' => [
            'name' => 'Marihuana',
            'unit' => 'gram',
            'energy_boost' => 3,
            'tolerance_increase' => 1
        ],
        'speed' => [
            'name' => 'Amfetamina',
            'unit' => 'gram',
            'energy_boost' => 10,
            'tolerance_increase' => 2
        ],
        'ecstasy' => [
            'name' => 'Ecstasy',
            'unit' => 'tabletka',
            'energy_boost' => 8,
            'tolerance_increase' => 1
        ],
        'cocaine' => [
            'name' => 'Kokaina',
            'unit' => 'gram',
            'energy_boost' => 20,
            'tolerance_increase' => 3
        ],
        'heroin' => [
            'name' => 'Heroina',
            'unit' => 'gram',
            'energy_boost' => 15,
            'tolerance_increase' => 2
        ],
        'lsd' => [
            'name' => 'LSD',
            'unit' => 'znaczek',
            'energy_boost' => 12,
            'tolerance_increase' => 2
        ]
    ];

    private int $id;
    private string $name;
    private int $level_required;
    private float $entry_fee;
    private int $min_intelligence;
    private string $drug_type;
    private float $drug_price;
    private int $max_drugs;
    private int $energy_per_gram;
    
    public function __construct(array $data) {
        $this->id = (int)$data['id'];
        $this->name = $data['name'];
        $this->level_required = (int)$data['level_required'];
        $this->entry_fee = (float)$data['entry_fee'];
        $this->min_intelligence = (int)$data['min_intelligence'];
        $this->drug_type = $data['drug_type'];
        $this->drug_price = (float)$data['drug_price'];
        $this->max_drugs = (int)$data['max_drugs'];
        $this->energy_per_gram = (int)$data['energy_per_gram'];
    }
    
    public static function getAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM nightclubs ORDER BY level_required ASC, entry_fee ASC");
        $clubs = [];
        
        while ($row = $stmt->fetch()) {
            $clubs[] = new Nightclub($row);
        }
        
        return $clubs;
    }
    
    public static function getById(int $id): ?Nightclub {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM nightclubs WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        return $data ? new Nightclub($data) : null;
    }
    
    public function canEnter(Character $character): bool {
        return $character->getLevel() >= $this->level_required &&
               $character->getIntelligence() >= $this->min_intelligence &&
               $character->getCash() >= $this->entry_fee;
    }
    
    public function buyDrugs(Character $character, int $quantity): array {
        if ($quantity <= 0 || $quantity > $this->max_drugs) {
            throw new Exception("Nieprawidłowa ilość narkotyków.");
        }
        
        $total_cost = $this->entry_fee + ($this->drug_price * $quantity);
        
        if ($character->getCash() < $total_cost) {
            throw new Exception("Nie masz wystarczająco pieniędzy.");
        }
        
        $db = Database::getInstance();
        
        // Sprawdź tolerancję na narkotyki
        $stmt = $db->prepare("
            SELECT tolerance_level, TIMESTAMPDIFF(MINUTE, last_use, NOW()) as minutes_since_last_use
            FROM character_drug_tolerances 
            WHERE character_id = ? AND drug_type = ?
        ");
        $stmt->execute([$character->getId(), $this->drug_type]);
        $tolerance_data = $stmt->fetch();
        
        $tolerance_level = $tolerance_data ? (int)$tolerance_data['tolerance_level'] : 0;
        $minutes_since_last_use = $tolerance_data ? (int)$tolerance_data['minutes_since_last_use'] : PHP_INT_MAX;
        
        // Zmniejsz tolerancję jeśli minęło wystarczająco dużo czasu
        if ($minutes_since_last_use >= 60) { // 1 godzina
            $tolerance_level = max(0, $tolerance_level - floor($minutes_since_last_use / 60));
        }
        
        // Sprawdź czy nie będzie przedawkowania
        if ($tolerance_level >= Config::MAX_TOLERANCE && $quantity > 1) {
            throw new Exception("Twoja tolerancja jest zbyt wysoka! Musisz poczekać zanim weźmiesz więcej.");
        }
        
        // Rozpocznij transakcję
        $db->beginTransaction();
        
        try {
            // Pobierz opłatę
            $character->reduceCash($total_cost);
            
            // Dodaj energię
            $energy_gain = $quantity * $this->energy_per_gram;
            $new_energy = min(
                Config::MAX_ENERGY,
                $character->getCurrentEnergy() + $energy_gain
            );
            
            $stmt = $db->prepare("
                UPDATE characters 
                SET current_energy = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_energy, $character->getId()]);
            
            // Aktualizuj tolerancję
            $tolerance_increase = $quantity * self::DRUG_TYPES[$this->drug_type]['tolerance_increase'];
            $new_tolerance = min(Config::MAX_TOLERANCE, $tolerance_level + $tolerance_increase);
            
            $stmt = $db->prepare("
                INSERT INTO character_drug_tolerances 
                (character_id, drug_type, tolerance_level, last_use)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    tolerance_level = ?,
                    last_use = NOW()
            ");
            $stmt->execute([
                $character->getId(), 
                $this->drug_type, 
                $new_tolerance,
                $new_tolerance
            ]);
            
            // Zapisz log
            $stmt = $db->prepare("
                INSERT INTO drug_purchase_logs 
                (character_id, nightclub_id, drug_type, quantity, total_cost)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $character->getId(),
                $this->id,
                $this->drug_type,
                $quantity,
                $total_cost
            ]);
            
            $db->commit();
            
            // Przygotuj wiadomość o efektach
            $effects = [];
            $effects[] = "Energia +" . $energy_gain;
            if ($new_tolerance > $tolerance_level) {
                $effects[] = "Tolerancja wzrosła do $new_tolerance/10";
            }
            
            return [
                'success' => true,
                'message' => "Kupiłeś {$quantity} {$this->getDrugUnit()} {$this->getDrugName()} za $" . number_format($total_cost, 2),
                'effects' => $effects
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    // Gettery
    public function getId(): int {
        return $this->id;
    }
    
    public function getName(): string {
        return $this->name;
    }
    
    public function getLevelRequired(): int {
        return $this->level_required;
    }
    
    public function getEntryFee(): float {
        return $this->entry_fee;
    }
    
    public function getMinIntelligence(): int {
        return $this->min_intelligence;
    }
    
    public function getDrugType(): string {
        return $this->drug_type;
    }
    
    public function getDrugName(): string {
        return self::DRUG_TYPES[$this->drug_type]['name'];
    }
    
    public function getDrugUnit(): string {
        return self::DRUG_TYPES[$this->drug_type]['unit'];
    }
    
    public function getDrugPrice(): float {
        return $this->drug_price;
    }
    
    public function getMaxDrugs(): int {
        return $this->max_drugs;
    }
    
    public function getEnergyPerGram(): int {
        return $this->energy_per_gram;
    }
    
    public function getToleranceIncrease(): int {
        return self::DRUG_TYPES[$this->drug_type]['tolerance_increase'];
    }
}