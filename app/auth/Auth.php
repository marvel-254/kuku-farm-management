<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, password, email, role, farmer_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session data first
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['farmer_id'] = $user['farmer_id'];
                $_SESSION['login_time'] = time();
                
                // Regenerate session ID to prevent session fixation
                // Only regenerate if no output has been sent
                if (!headers_sent()) {
                    session_regenerate_id(true);
                }
                
                // Log the login
                $this->logActivity($user['id'], 'login', 'users', $user['id'], null, json_encode($user));
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id'], null, null);
        }
        
        // Destroy all session data
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] < SESSION_LIFETIME);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            redirect('/login.php');
        }
    }
    
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    public function hasPermission($requiredRole) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $roleHierarchy = [
            'user' => 1,
            'manager' => 2,
            'admin' => 3
        ];
        
        $userRole = $_SESSION['role'];
        return $roleHierarchy[$userRole] >= $roleHierarchy[$requiredRole];
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role'],
                'farmer_id' => $_SESSION['farmer_id']
            ];
        }
        return null;
    }
    
    public function createUser($username, $password, $email, $role = 'user', $farmerId = null) {
        try {
            // Check if username or email already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                return false; // User already exists
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, email, role, farmer_id) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$username, $hashedPassword, $email, $role, $farmerId]);
            
            if ($result) {
                $userId = $this->conn->lastInsertId();
                $this->logActivity($userId, 'create', 'users', $userId, null, json_encode(['username' => $username, 'email' => $email, 'role' => $role]));
                return $userId;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return false;
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$hashedPassword, $userId]);
            
            if ($result) {
                $this->logActivity($userId, 'password_change', 'users', $userId, null, null);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logActivity($userId, $action, $tableName, $recordId, $oldValues, $newValues) {
        try {
            // Use a separate connection for audit logging to avoid locking issues
            $auditConn = new PDO('sqlite:' . DB_PATH);
            $auditConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $auditConn->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 second timeout
            
            $stmt = $auditConn->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $action,
                $tableName,
                $recordId,
                $oldValues,
                $newValues,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            $auditConn = null; // Close connection
        } catch (PDOException $e) {
            // Only log critical errors, ignore database locking issues
            if (strpos($e->getMessage(), 'database is locked') === false) {
                error_log("Audit log error: " . $e->getMessage());
            }
        }
    }
    
    public function getAuditLogs($limit = 100, $userId = null) {
        try {
            $sql = "SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id";
            $params = [];
            
            if ($userId) {
                $sql .= " WHERE al.user_id = ?";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY al.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get audit logs error: " . $e->getMessage());
            return [];
        }
    }
}
?>