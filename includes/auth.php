<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    private $table_name = "users";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($username, $email, $password, $first_name, $last_name) {
        try {
            // Check if username or email already exists
            $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                return ["success" => false, "message" => "Username or email already exists"];
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $this->conn->prepare(
                "INSERT INTO users (username, email, password_hash, first_name, last_name) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            
            $stmt->execute([$username, $email, $password_hash, $first_name, $last_name]);
            
            return ["success" => true, "message" => "Registration successful"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Registration failed: " . $e->getMessage()];
        }
    }

    public function login($username, $password) {
        try {
            // Get user data
            $stmt = $this->conn->prepare(
                "SELECT user_id, username, password_hash, failed_login_attempts, last_failed_login 
                 FROM users WHERE username = ?"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                return ["success" => false, "message" => "Invalid username or password"];
            }

            // Check if account is locked
            if ($user['failed_login_attempts'] >= 3) {
                $lockout_time = strtotime($user['last_failed_login']) + (3 * 60); // 3 minutes lockout
                if (time() < $lockout_time) {
                    $remaining_time = ceil(($lockout_time - time()) / 60);
                    return [
                        "success" => false, 
                        "message" => "Account is locked. Try again in {$remaining_time} minutes"
                    ];
                }
                
                // Reset failed attempts after lockout period
                $this->resetFailedAttempts($user['user_id']);
            }

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Reset failed attempts on successful login
                $this->resetFailedAttempts($user['user_id']);
                
                // Update last login time
                $stmt = $this->conn->prepare(
                    "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?"
                );
                $stmt->execute([$user['user_id']]);

                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];

                return ["success" => true, "message" => "Login successful"];
            } else {
                // Increment failed attempts
                $this->incrementFailedAttempts($user['user_id']);
                return ["success" => false, "message" => "Invalid username or password"];
            }
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Login failed: " . $e->getMessage()];
        }
    }

    private function incrementFailedAttempts($user_id) {
        $stmt = $this->conn->prepare(
            "UPDATE users 
             SET failed_login_attempts = failed_login_attempts + 1,
                 last_failed_login = CURRENT_TIMESTAMP
             WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
    }

    private function resetFailedAttempts($user_id) {
        $stmt = $this->conn->prepare(
            "UPDATE users 
             SET failed_login_attempts = 0,
                 last_failed_login = NULL
             WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ["success" => true, "message" => "Logout successful"];
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->conn->prepare(
            "SELECT user_id, username, email, first_name, last_name, profile_picture, bio, role 
             FROM users WHERE user_id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    public function updateProfile($user_id, $data) {
        try {
            $allowed_fields = ['first_name', 'last_name', 'bio', 'profile_picture'];
            $updates = [];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                return ["success" => false, "message" => "No valid fields to update"];
            }

            $values[] = $user_id;
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);

            return ["success" => true, "message" => "Profile updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Update failed: " . $e->getMessage()];
        }
    }
} 