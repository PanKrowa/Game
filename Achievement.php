<?php
declare(strict_types=1);

class Achievement {
    private int $id;
    private string $name;
    private string $description;
    private string $type;
    private int $requirement;
    private float $reward_cash;
    private int $reward_respect;
    
    public function __construct(int $id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achievements WHERE id = ?");
        $stmt->execute([$id]);
        
        $data = $stmt->fetch();
        if (!$data) {
            throw new Exception("Achievement not found");
        }
        
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->type = $data['type'];
        $this->requirement = $data['requirement'];
        $this->reward_cash = $data['reward_cash'];
        $this->reward_respect = $data['reward_respect'];
    }
    
    public static function checkAchievements(Character $character): array {
        $db = Database::getInstance();
        $completed = [];
        
        // Get uncompleted achievements
        $stmt = $db->prepare("
            SELECT a.* 
            FROM achievements a
            LEFT JOIN character_achievements ca 
                ON a.id = ca.achievement_id 
                AND ca.character_id = ?
            WHERE ca.id IS NULL
        ");
        $stmt->execute([$character->getId()]);
        $achievements = $stmt->fetchAll();
        
        foreach ($achievements as $achievement) {
            $progress = self::getProgress($character, $achievement['type']);
            
            if ($progress >= $achievement['requirement']) {
                self::award($character, $achievement['id']);
                $completed[] = [
                    'name' => $achievement['name'],
                    'description' => $achievement['description'],
                    'reward_cash' => $achievement['reward_cash'],
                    'reward_respect' => $achievement['reward_respect']
                ];
            }
        }
        
        return $completed;
    }
    
    private static function getProgress(Character $character, string $type): int {
        $db = Database::getInstance();
        
        switch ($type) {
            case 'robberies_completed':
                $stmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM robbery_logs 
                    WHERE character_id = ? AND success = 1
                ");
                break;
                
            case 'fights_won':
                $stmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM combat_logs 
                    WHERE attacker_id = ? AND result = 'win'
                ");
                break;
                
            case 'respect_reached':
                return $character->getRespect();
                
            case 'level_reached':
                return $character->getLevel();
                
            case 'cash_earned':
                return (int)$character->getCash();
                
            default:
                return 0;
        }
        
        $stmt->execute([$character->getId()]);
        return (int)$stmt->fetchColumn();
    }
    
    private static function award(Character $character, int $achievement_id): void {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM achievements WHERE id = ?");
        $stmt->execute([$achievement_id]);
        $achievement = $stmt->fetch();
        
        Database::beginTransaction();
        try {
            // Mark achievement as completed
            $stmt = $db->prepare("
                INSERT INTO character_achievements 
                (character_id, achievement_id, completed_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$character->getId(), $achievement_id]);
            
            // Award rewards
            $character->addCash($achievement['reward_cash']);
            
            Database::commit();
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}