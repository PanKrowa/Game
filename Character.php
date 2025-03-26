<?php
// Data utworzenia: 2025-03-23 10:28:10
// Autor: PanKrowa

if (!class_exists('Character')) {
    class Character {
        private $id;
        private $data;
        private $stats;
        private $db;
        private $created_at;
        private $updated_by;

        public function __construct($id) {
            $this->id = $id;
            $this->db = Database::getInstance();
            $this->created_at = '2025-03-23 10:28:10'; // Current UTC time
            $this->updated_by = 'PanKrowa'; // Current user
            $this->loadCharacter();
            $this->updateEnergy();
        }

        private function loadCharacter() {
            // Pobierz dane postaci
            $stmt = $this->db->prepare("
                SELECT * FROM characters 
                WHERE id = ?
            ");
            $stmt->execute([$this->id]);
            $this->data = $stmt->fetch();

            if (!$this->data) {
                throw new Exception('Postać nie istnieje.');
            }

            // Pobierz statystyki
            $stmt = $this->db->prepare("
                SELECT * FROM character_stats 
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            $this->stats = $stmt->fetch();

            // Log the character load
            $this->logAction('load_character');
        }

        // Gettery podstawowych danych
        public function getId() { return $this->id; }
        public function getName() { return $this->data['name']; }
        public function getLevel() { return $this->data['level']; }
        public function getExperience() { return $this->data['experience']; }
        public function getCash() { return $this->data['cash']; }
        public function getCurrentHealth() { return $this->data['current_health']; }
        public function getMaxHealth() { return $this->data['max_health']; }
        public function getCurrentEnergy() { return $this->data['current_energy']; }
        public function getMaxEnergy() { return $this->data['max_energy']; }
        public function getLastEnergyUpdate() { return $this->data['last_energy_update']; }
        public function getRespectPoints() { return isset($this->data['respect_points']) ? $this->data['respect_points'] : 0; }
        public function getCreatedAt() { return $this->created_at; }
        public function getUpdatedBy() { return $this->updated_by; }

        // Gettery statystyk
        public function getStrength() { return $this->stats['strength']; }
        public function getAgility() { return $this->stats['agility']; }
        public function getEndurance() { return $this->stats['endurance']; }
        public function getIntelligence() { return $this->stats['intelligence']; }
        public function getTolerance() { return $this->stats['tolerance']; }

        // Gettery statusów
        public function isInJail() { return $this->data['in_jail']; }
        public function isInHospital() { return $this->data['in_hospital']; }
        public function getJailUntil() { return $this->data['jail_until']; }
        public function getHospitalUntil() { return $this->data['hospital_until']; }

        // Settery z automatycznym zapisem do bazy
        public function setName($name) {
            $stmt = $this->db->prepare("
                UPDATE characters 
                SET name = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $this->created_at, $this->updated_by, $this->id]);
            $this->data['name'] = $name;
            $this->logAction('name_change');
        }

        public function setCash($amount) {
            $stmt = $this->db->prepare("
                UPDATE characters 
                SET cash = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $this->created_at, $this->updated_by, $this->id]);
            $this->data['cash'] = $amount;
            $this->logAction('cash_update', ['amount' => $amount]);
        }

        public function addCash($amount) {
            $this->setCash($this->getCash() + $amount);
        }

        public function subtractCash($amount) {
            if ($this->getCash() < $amount) {
                throw new Exception('Niewystarczająca ilość gotówki.');
            }
            $this->setCash($this->getCash() - $amount);
        }

        public function setCurrentHealth($health) {
            $health = max(0, min($health, $this->getMaxHealth()));
            $stmt = $this->db->prepare("
                UPDATE characters 
                SET current_health = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$health, $this->created_at, $this->updated_by, $this->id]);
            $this->data['current_health'] = $health;
            $this->logAction('health_update', ['health' => $health]);
        }

        public function setCurrentEnergy($energy) {
            $energy = max(0, min($energy, $this->getMaxEnergy()));
            $stmt = $this->db->prepare("
                UPDATE characters 
                SET current_energy = ?,
                    last_energy_update = NOW(),
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$energy, $this->created_at, $this->updated_by, $this->id]);
            $this->data['current_energy'] = $energy;
            $this->logAction('energy_update', ['energy' => $energy]);
        }

        public function setInJail($status, $hours = null) {
            $jail_until = $hours ? date('Y-m-d H:i:s', strtotime("+{$hours} hours")) : null;
            $stmt = $this->db->prepare("
                UPDATE characters 
                SET in_jail = ?,
                    jail_until = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $jail_until, $this->created_at, $this->updated_by, $this->id]);
            $this->data['in_jail'] = $status;
            $this->data['jail_until'] = $jail_until;
            $this->logAction('jail_status_change', ['status' => $status, 'hours' => $hours]);
        }

        public function setInHospital($status, $hours = null) {
            $hospital_until = $hours ? date('Y-m-d H:i:s', strtotime("+{$hours} hours")) : null;
            $stmt = $this->db->prepare("
                UPDATE characters 
                SET in_hospital = ?,
                    hospital_until = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $hospital_until, $this->created_at, $this->updated_by, $this->id]);
            $this->data['in_hospital'] = $status;
            $this->data['hospital_until'] = $hospital_until;
            $this->logAction('hospital_status_change', ['status' => $status, 'hours' => $hours]);
        }

        // System doświadczenia i levelowania
        public function addExperience($amount) {
            $new_exp = $this->getExperience() + $amount;
            $current_level = $this->getLevel();
            
            while ($new_exp >= $this->getRequiredExperience($current_level + 1)) {
                $current_level++;
                $this->setMaxHealth($this->getMaxHealth() + 5);
                $this->setMaxEnergy($this->getMaxEnergy() + 2);
                $this->logAction('level_up', ['new_level' => $current_level]);
            }

            $stmt = $this->db->prepare("
                UPDATE characters 
                SET experience = ?,
                    level = ?,
                    updated_at = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_exp, $current_level, $this->created_at, $this->updated_by, $this->id]);
            
            $this->data['experience'] = $new_exp;
            $this->data['level'] = $current_level;
            $this->logAction('experience_gain', ['amount' => $amount]);
        }

        private function getRequiredExperience($level) {
            return floor(Config::BASE_EXPERIENCE * pow(Config::EXPERIENCE_MULTIPLIER, $level - 1));
        }

        // System energii i regeneracji
        private function updateEnergy() {
            $last_update = new DateTime($this->data['last_energy_update']);
            $now = new DateTime();
            $diff = $last_update->diff($now);
            $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            
            if ($minutes >= Config::ENERGY_REGEN_MINUTES) {
                $energy_gained = floor($minutes / Config::ENERGY_REGEN_MINUTES) * Config::ENERGY_REGEN_AMOUNT;
                $new_energy = min($this->getMaxEnergy(), $this->getCurrentEnergy() + $energy_gained);
                
                if ($new_energy > $this->getCurrentEnergy()) {
                    $this->setCurrentEnergy($new_energy);
                }
            }
        }

        // System obrony
public function getDefense() {
    try {
        // Pobierz podstawową wartość obrony
        $base_defense = isset($this->data['defense']) ? (int)$this->data['defense'] : 0;
        
        // Pobierz bonus z ekwipunku
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(i.defense * ci.quantity), 0) as total_defense
            FROM character_items ci
            JOIN items i ON ci.item_id = i.id
            WHERE ci.character_id = ?
        ");
        $stmt->execute([$this->id]);
        $equipment_defense = (int)$stmt->fetchColumn();
        
        // Pobierz bonus z poziomu postaci
        $level_defense = $this->getLevel() * 2;
        
        // Pobierz bonus ze statystyk
        $stats_defense = isset($this->stats['endurance']) ? floor($this->stats['endurance'] / 2) : 0;
        
        // Oblicz całkowitą obronę
        $total_defense = $base_defense + $equipment_defense + $level_defense + $stats_defense;
        
        // Zapisz aktualną wartość obrony do bazy
        $stmt = $this->db->prepare("
            UPDATE characters 
            SET defense = ?,
                updated_at = ?,
                updated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_defense, $this->created_at, $this->updated_by, $this->id]);
        
        // Zaloguj aktualizację obrony
        $this->logAction('defense_update', [
            'base' => $base_defense,
            'equipment' => $equipment_defense,
            'level' => $level_defense,
            'stats' => $stats_defense,
            'total' => $total_defense
        ]);
        
        return $total_defense;
    } catch (PDOException $e) {
        error_log("Error calculating defense for character {$this->id}: " . $e->getMessage());
        return isset($this->data['defense']) ? (int)$this->data['defense'] : 0;
    }
}

        // Dodaj też pomocniczą metodę do aktualizacji podstawowej obrony
        public function setBaseDefense($value) {
            try {
                $stmt = $this->db->prepare("
                    UPDATE characters 
                    SET defense = ?,
                        updated_at = ?,
                        updated_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([(int)$value, $this->created_at, $this->updated_by, $this->id]);
                $this->data['defense'] = (int)$value;
                $this->logAction('base_defense_update', ['value' => $value]);
            } catch (PDOException $e) {
                error_log("Error updating base defense for character {$this->id}: " . $e->getMessage());
                throw new Exception('Nie udało się zaktualizować wartości obrony.');
            }
        }

        // System ekwipunku
        public function getInventory() {
            $stmt = $this->db->prepare("
                SELECT i.*, ci.quantity 
                FROM character_items ci
                JOIN items i ON ci.item_id = i.id
                WHERE ci.character_id = ?
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll();
        }

        public function addItem($item_id, $quantity = 1) {
            $stmt = $this->db->prepare("
                INSERT INTO character_items (character_id, item_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ");
            $stmt->execute([$this->id, $item_id, $quantity, $quantity]);
            $this->logAction('item_add', ['item_id' => $item_id, 'quantity' => $quantity]);
        }

        public function removeItem($item_id, $quantity = 1) {
            $stmt = $this->db->prepare("
                UPDATE character_items 
                SET quantity = quantity - ?
                WHERE character_id = ? AND item_id = ? AND quantity >= ?
            ");
            if (!$stmt->execute([$quantity, $this->id, $item_id, $quantity])) {
                throw new Exception('Nie masz wystarczającej ilości tego przedmiotu.');
            }

            $stmt = $this->db->prepare("
                DELETE FROM character_items 
                WHERE character_id = ? AND item_id = ? AND quantity <= 0
            ");
            $stmt->execute([$this->id, $item_id]);
            $this->logAction('item_remove', ['item_id' => $item_id, 'quantity' => $quantity]);
        }

        // System gangów
        public function getGang() {
            $stmt = $this->db->prepare("
                SELECT g.*, gm.role
                FROM gangs g
                JOIN gang_members gm ON g.id = gm.gang_id
                WHERE gm.character_id = ?
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetch();
        }

        public function isInGang() {
            return (bool)$this->getGang();
        }

        // System logowania akcji
        private function logAction($action, $details = []) {
            $stmt = $this->db->prepare("
                INSERT INTO character_logs 
                (character_id, action, details, created_at, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->id,
                $action,
                json_encode($details),
                $this->created_at,
                $this->updated_by
            ]);
        }

        // Aktualizacja statystyk
        public function updateStats() {
            $this->updateHealth();
            $this->checkStatusEffects();
        }

        private function updateHealth() {
            if (!$this->isInHospital() && !$this->isInJail()) {
                $last_update = new DateTime($this->data['last_health_update'] ?? $this->data['last_energy_update']);
                $now = new DateTime();
                $diff = $last_update->diff($now);
                $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                
                if ($minutes >= Config::HEALTH_REGEN_MINUTES) {
                    $health_gained = floor($minutes / Config::HEALTH_REGEN_MINUTES);
                    $new_health = min($this->getMaxHealth(), $this->getCurrentHealth() + $health_gained);
                    
                    if ($new_health > $this->getCurrentHealth()) {
                        $this->setCurrentHealth($new_health);
                        $stmt = $this->db->prepare("
                            UPDATE characters 
                            SET last_health_update = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$this->id]);
                        $this->data['last_health_update'] = date('Y-m-d H:i:s');
                    }
                }
            }
        }

        private function checkStatusEffects() {
            $now = new DateTime();

            if ($this->isInJail() && $this->getJailUntil()) {
                $jail_until = new DateTime($this->getJailUntil());
                if ($now >= $jail_until) {
                    $this->setInJail(false);
                }
            }

            if ($this->isInHospital() && $this->getHospitalUntil()) {
                $hospital_until = new DateTime($this->getHospitalUntil());
                if ($now >= $hospital_until) {
                    $this->setInHospital(false);
                }
            }
        }

        // Aktualizacja bazy danych
        public static function updateDatabase() {
            $db = Database::getInstance();
            
            $db->query("
                ALTER TABLE characters 
                ADD COLUMN IF NOT EXISTS respect_points INT NOT NULL DEFAULT 0,
                ADD COLUMN IF NOT EXISTS last_health_update DATETIME DEFAULT CURRENT_TIMESTAMP,
                ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                ADD COLUMN IF NOT EXISTS created_by VARCHAR(50),
                ADD COLUMN IF NOT EXISTS updated_by VARCHAR(50),
                MODIFY COLUMN current_health INT NOT NULL DEFAULT 100,
                MODIFY COLUMN max_health INT NOT NULL DEFAULT 100,
                MODIFY COLUMN current_energy INT NOT NULL DEFAULT 100,
                MODIFY COLUMN max_energy INT NOT NULL DEFAULT 100
            ");

            $db->query("
                CREATE TABLE IF NOT EXISTS character_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    character_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    details JSON,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    created_by VARCHAR(50),
                    FOREIGN KEY (character_id) REFERENCES characters(id)
                )
            ");
        }

        // System narkotyków i uzależnień
        public function getDrugInventory() {
            $stmt = $this->db->prepare("
                SELECT d.*, cd.quantity 
                FROM character_drugs cd
                JOIN drugs d ON cd.drug_id = d.id
                WHERE cd.character_id = ?
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll();
        }

        public function addDrug($drugId, $quantity = 1) {
            $stmt = $this->db->prepare("
                INSERT INTO character_drugs (character_id, drug_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ");
            $stmt->execute([$this->id, $drugId, $quantity, $quantity]);
            $this->logAction('drug_add', ['drug_id' => $drugId, 'quantity' => $quantity]);
        }

        public function removeDrug($drugId, $quantity = 1) {
            $stmt = $this->db->prepare("
                UPDATE character_drugs 
                SET quantity = quantity - ?
                WHERE character_id = ? AND drug_id = ? AND quantity >= ?
            ");
            if (!$stmt->execute([$quantity, $this->id, $drugId, $quantity])) {
                throw new Exception('Nie masz wystarczającej ilości tego narkotyku.');
            }

            $stmt = $this->db->prepare("
                DELETE FROM character_drugs 
                WHERE character_id = ? AND drug_id = ? AND quantity <= 0
            ");
            $stmt->execute([$this->id, $drugId]);
            $this->logAction('drug_remove', ['drug_id' => $drugId, 'quantity' => $quantity]);
        }

        // System klubów
        public function getOwnedClubs() {
            $stmt = $this->db->prepare("
                SELECT * FROM clubs 
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll();
        }

        public function buyClub($clubId) {
            $stmt = $this->db->prepare("
                SELECT * FROM clubs 
                WHERE id = ? AND character_id IS NULL
            ");
            $stmt->execute([$clubId]);
            $club = $stmt->fetch();

            if (!$club) {
                throw new Exception('Ten klub nie jest dostępny do kupienia.');
            }

            if ($this->getCash() < $club['price']) {
                throw new Exception('Nie stać cię na ten klub.');
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM clubs 
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            if ($stmt->fetch()['count'] >= 3) {
                throw new Exception('Osiągnąłeś limit posiadanych klubów.');
            }

            $this->db->beginTransaction();
            try {
                // Przypisz klub do gracza
                $stmt = $this->db->prepare("
                    UPDATE clubs 
                    SET character_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$this->id, $clubId]);

                // Odejmij pieniądze
                $this->subtractCash($club['price']);

                $this->db->commit();
                $this->logAction('club_buy', ['club_id' => $clubId, 'price' => $club['price']]);
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        public function sellClub($clubId) {
            $stmt = $this->db->prepare("
                SELECT * FROM clubs 
                WHERE id = ? AND character_id = ?
            ");
            $stmt->execute([$clubId, $this->id]);
            $club = $stmt->fetch();

            if (!$club) {
                throw new Exception('Ten klub nie należy do ciebie.');
            }

            $sellPrice = floor($club['price'] * 0.7); // 70% wartości

            $this->db->beginTransaction();
            try {
                // Usuń narkotyki z klubu
                $stmt = $this->db->prepare("
                    DELETE FROM club_drugs 
                    WHERE club_id = ?
                ");
                $stmt->execute([$clubId]);

                // Zwolnij klub
                $stmt = $this->db->prepare("
                    UPDATE clubs 
                    SET character_id = NULL 
                    WHERE id = ?
                ");
                $stmt->execute([$clubId]);

                // Dodaj pieniądze
                $this->addCash($sellPrice);

                $this->db->commit();
                $this->logAction('club_sell', ['club_id' => $clubId, 'price' => $sellPrice]);
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        // System prostytutek
        public function getProstitutes() {
            $stmt = $this->db->prepare("
                SELECT p.*, cp.last_collection, cp.total_earned
                FROM character_prostitutes cp
                JOIN prostitutes p ON cp.prostitute_id = p.id
                WHERE cp.character_id = ?
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll();
        }

        public function buyProstitute($prostituteId) {
            $stmt = $this->db->prepare("
                SELECT * FROM prostitutes 
                WHERE id = ? AND level_required <= ?
            ");
            $stmt->execute([$prostituteId, $this->getLevel()]);
            $prostitute = $stmt->fetch();

            if (!$prostitute) {
                throw new Exception('Ta prostytutka nie jest dostępna.');
            }

            if ($this->getCash() < $prostitute['price']) {
                throw new Exception('Nie stać cię na tę prostytutkę.');
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM character_prostitutes 
                WHERE character_id = ?
            ");
            $stmt->execute([$this->id]);
            if ($stmt->fetch()['count'] >= 10) {
                throw new Exception('Osiągnąłeś limit prostytutek.');
            }

            $this->db->beginTransaction();
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO character_prostitutes 
                    (character_id, prostitute_id, last_collection)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$this->id, $prostituteId]);

                $this->subtractCash($prostitute['price']);

                $this->db->commit();
                $this->logAction('prostitute_buy', [
                    'prostitute_id' => $prostituteId, 
                    'price' => $prostitute['price']
                ]);
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        public function collectProstitutesIncome() {
            $this->db->beginTransaction();
            try {
                $stmt = $this->db->prepare("
                    SELECT p.*, cp.last_collection
                    FROM character_prostitutes cp
                    JOIN prostitutes p ON cp.prostitute_id = p.id
                    WHERE cp.character_id = ?
                ");
                $stmt->execute([$this->id]);
                $prostitutes = $stmt->fetchAll();

                $totalIncome = 0;
                foreach ($prostitutes as $prostitute) {
                    $lastCollection = new DateTime($prostitute['last_collection']);
                    $now = new DateTime();
                    $hours = $lastCollection->diff($now)->h + ($lastCollection->diff($now)->days * 24);
                    
                    if ($hours > 0) {
                        $income = $prostitute['hourly_income'] * $hours;
                        $totalIncome += $income;

                        $stmt = $this->db->prepare("
                            UPDATE character_prostitutes 
                            SET last_collection = NOW(),
                                total_earned = total_earned + ?
                            WHERE character_id = ? AND prostitute_id = ?
                        ");
                        $stmt->execute([$income, $this->id, $prostitute['id']]);
                    }
                }

                if ($totalIncome > 0) {
                    $this->addCash($totalIncome);
                    $this->logAction('prostitutes_income', ['amount' => $totalIncome]);
                }

                $this->db->commit();
                return $totalIncome;
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        // System uzależnień
        public function getAddictions() {
            $stmt = $this->db->prepare("
                SELECT ca.*, d.name as drug_name
                FROM character_addictions ca
                JOIN drugs d ON ca.drug_id = d.id
                WHERE ca.character_id = ?
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetchAll();
        }

        public function addAddiction($drugId, $amount = 1) {
            $stmt = $this->db->prepare("
                INSERT INTO character_addictions 
                (character_id, drug_id, level, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    level = level + ?,
                    updated_at = NOW()
            ");
            $stmt->execute([$this->id, $drugId, $amount, $amount]);
            $this->logAction('addiction_increase', ['drug_id' => $drugId, 'amount' => $amount]);
        }

        public function reduceAddiction($drugId, $amount = 1) {
            $stmt = $this->db->prepare("
                UPDATE character_addictions 
                SET level = GREATEST(0, level - ?),
                    updated_at = NOW()
                WHERE character_id = ? AND drug_id = ?
            ");
            $stmt->execute([$amount, $this->id, $drugId]);

            $stmt = $this->db->prepare("
                DELETE FROM character_addictions 
                WHERE character_id = ? AND drug_id = ? AND level <= 0
            ");
            $stmt->execute([$this->id, $drugId]);
            
            $this->logAction('addiction_decrease', ['drug_id' => $drugId, 'amount' => $amount]);
        }
    }
}