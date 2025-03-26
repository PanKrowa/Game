<?php
declare(strict_types=1);

class Authentication {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login(string $username, string $password): array {
        // Check login attempts
        $this->checkLoginAttempts($username);
        
        // Get user
        $stmt = $this->db->prepare("
            SELECT u.*, c.id as character_id 
            FROM users u
            LEFT JOIN characters c ON u.id = c.user_id
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->logFailedAttempt($username);
            throw new Exception("Nieprawidłowa nazwa użytkownika lub hasło");
        }
        
        if ($user['is_banned']) {
            throw new Exception("Konto zostało zablokowane");
        }
        
        // Update last login
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login = NOW(),
                login_attempts = 0
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        // Update character last activity
        if ($user['character_id']) {
            $stmt = $this->db->prepare("
                UPDATE characters 
                SET last_activity = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$user['character_id']]);
        }
        
        return [
            'user_id' => $user['id'],
            'character_id' => $user['character_id'],
            'username' => $user['username'],
            'is_admin' => $user['is_admin']
        ];
    }
    
    public function register(string $username, string $password, string $email): array {
        // Validate input
        $this->validateRegistrationInput($username, $password, $email);
        
        // Check if username exists
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM users WHERE username = ?
        ");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ta nazwa użytkownika jest już zajęta");
        }
        
        // Check if email exists
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM users WHERE email = ?
        ");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ten email jest już używany");
        }
        
        Database::beginTransaction();
        try {
            // Create user
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    username, 
                    password_hash, 
                    email, 
                    created_at,
                    last_login
                ) VALUES (?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $email
            ]);
            
            $user_id = (int)$this->db->lastInsertId();
            
            // Create character
            $stmt = $this->db->prepare("
                INSERT INTO characters (
                    user_id,
                    name,
                    cash,
                    current_energy,
                    max_energy,
                    strength,
                    charisma,
                    intelligence,
                    endurance,
                    health,
                    created_at,
                    last_activity
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $username,
                Config::STARTING_CASH,
                Config::STARTING_ENERGY,
                Config::MAX_ENERGY,
                10, // starting strength
                10, // starting charisma
                10, // starting intelligence
                10, // starting endurance
                100 // starting health
            ]);
            
            $character_id = (int)$this->db->lastInsertId();
            
            Database::commit();
            
            return [
                'user_id' => $user_id,
                'character_id' => $character_id,
                'username' => $username
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
    
    private function checkLoginAttempts(string $username): void {
        $stmt = $this->db->prepare("
            SELECT login_attempts, last_attempt 
            FROM users 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $data = $stmt->fetch();
        
        if ($data) {
            if ($data['login_attempts'] >= Config::MAX_LOGIN_ATTEMPTS) {
                $timeout = new DateTime($data['last_attempt']);
                $timeout->modify('+' . Config::LOGIN_TIMEOUT . ' seconds');
                
                if (new DateTime() < $timeout) {
                    throw new Exception("Za dużo nieudanych prób logowania. Spróbuj ponownie za kilka minut.");
                }
                
                // Reset attempts if timeout passed
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET login_attempts = 0 
                    WHERE username = ?
                ");
                $stmt->execute([$username]);
            }
        }
    }
    
    private function logFailedAttempt(string $username): void {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = login_attempts + 1,
                last_attempt = NOW()
            WHERE username = ?
        ");
        $stmt->execute([$username]);
    }
    
    private function validateRegistrationInput(string $username, string $password, string $email): void {
        if (strlen($username) < 3 || strlen($username) > 20) {
            throw new Exception("Nazwa użytkownika musi mieć od 3 do 20 znaków");
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception("Nazwa użytkownika może zawierać tylko litery, cyfry i podkreślenie");
        }
        
        if (strlen($password) < Config::PASSWORD_MIN_LENGTH) {
            throw new Exception("Hasło musi mieć minimum " . Config::PASSWORD_MIN_LENGTH . " znaków");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Nieprawidłowy adres email");
        }
    }
    
    public function logout(): void {
        session_destroy();
    }
}