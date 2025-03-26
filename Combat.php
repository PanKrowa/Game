<?php
declare(strict_types=1);

class Combat {
    private Character $attacker;
    private Character $defender;
    
    public function __construct(Character $attacker, Character $defender) {
        $this->attacker = $attacker;
        $this->defender = $defender;
    }
    
    public function initiateCombat(): array {
        if ($this->attacker->isInHospital() || $this->attacker->isInJail()) {
            throw new Exception("Cannot initiate combat while in hospital or jail");
        }
        
        if ($this->defender->isInHospital() || $this->defender->isInJail()) {
            throw new Exception("Cannot attack player who is in hospital or jail");
        }
        
        if ($this->attacker->getCurrentEnergy() < Config::MIN_COMBAT_ENERGY) {
            throw new Exception("Not enough energy for combat");
        }
        
        // Calculate combat scores
        $attacker_score = $this->calculateCombatScore($this->attacker);
        $defender_score = $this->calculateCombatScore($this->defender);
        
        // Add random factor (+-10%)
        $attacker_score *= (mt_rand(90, 110) / 100);
        $defender_score *= (mt_rand(90, 110) / 100);
        
        $attacker_won = $attacker_score > $defender_score;
        
        Database::beginTransaction();
        try {
            // Process combat results
            if ($attacker_won) {
                $cash_stolen = $this->processCombatWin();
            } else {
                $this->processCombatLoss();
                $cash_stolen = 0;
            }
            
            // Log combat
            $this->logCombat($attacker_won, $cash_stolen);
            
            Database::commit();
            
            return [
                'success' => $attacker_won,
                'cash_stolen' => $cash_stolen,
                'attacker_score' => $attacker_score,
                'defender_score' => $defender_score
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
    
    private function calculateCombatScore(Character $character): float {
        $base_score = $character->getStrength() * 2;
        $base_score += $character->getEndurance();
        
        // Add equipment bonuses
        $equipment_bonus = $this->calculateEquipmentBonus($character);
        
        // Add gang bonus
        $gang_bonus = $this->calculateGangBonus($character);
        
        return $base_score + $equipment_bonus + $gang_bonus;
    }
    
    private function processCombatWin(): float {
        $cash_stolen = min(
            $this->defender->getCash() * Config::COMBAT_REWARD_PERCENTAGE,
            mt_rand(100, 1000)
        );
        
        $this->defender->reduceCash($cash_stolen);
        $this->attacker->addCash($cash_stolen);
        
        // Send defender to hospital
        $hospital_hours = mt_rand(1, 3);
        $this->defender->sendToHospital($hospital_hours);
        
        // Award experience and respect
        $level_difference = $this->defender->getLevel() - $this->attacker->getLevel();
        $experience_gained = max(10, 20 + $level_difference * 5);
        $respect_gained = max(1, 5 + $level_difference);
        
        $this->attacker->addExperience($experience_gained);
        
        return $cash_stolen;
    }
    
    private function processCombatLoss(): void {
        // Send attacker to hospital
        $hospital_hours = mt_rand(1, 2);
        $this->attacker->sendToHospital($hospital_hours);
        
        // Defender gains some experience
        $experience_gained = 10;
        $this->defender->addExperience($experience_gained);
    }
    
    private function calculateEquipmentBonus(Character $character): float {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT SUM(e.combat_bonus)
            FROM character_equipment ce
            JOIN equipment e ON ce.equipment_id = e.id
            WHERE ce.character_id = ?
            AND ce.equipped = 1
        ");
        $stmt->execute([$character->getId()]);
        return (float)$stmt->fetchColumn() ?? 0;
    }
    
    private function calculateGangBonus(Character $character): float {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM gang_members 
            WHERE gang_id = (
                SELECT gang_id 
                FROM gang_members 
                WHERE character_id = ?
            )
        ");
        $stmt->execute([$character->getId()]);
        $gang_size = (int)$stmt->fetchColumn();
        
        // Small bonus based on gang size (max 10%)
        return min(10, $gang_size) / 100;
    }
    
    private function logCombat(bool $attacker_won, float $cash_stolen): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO combat_logs 
            (attacker_id, defender_id, result, cash_stolen, timestamp)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $this->attacker->getId(),
            $this->defender->getId(),
            $attacker_won ? 'win' : 'loss',
            $cash_stolen
        ]);
    }
}