<?php
class User {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Método para registrar um novo usuário
    public function register($name, $email, $password, $cpf, $phone) {
        try {
            // Verificar se email já existe
            $checkStmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                return ['status' => 'error', 'message' => 'Email já cadastrado!'];
            }
            
            // Criptografar senha
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserir novo usuário
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, cpf, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$name, $email, $hashedPassword, $cpf, $phone]);
            
            if ($result) {
                return ['status' => 'success', 'message' => 'Usuário registrado com sucesso!'];
            } else {
                return ['status' => 'error', 'message' => 'Erro ao registrar usuário!'];
            }
        } catch(PDOException $e) {
            return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    // Método para fazer login
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Atualizar último login
                $updateStmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                return [
                    'status' => 'success',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ];
            } else {
                return ['status' => 'error', 'message' => 'Credenciais inválidas!'];
            }
        } catch(PDOException $e) {
            return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    // Método para obter informações do usuário
    public function getUserById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, email, cpf, phone, created_at, last_login, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Método para atualizar perfil do usuário
    public function updateProfile($id, $name, $email, $cpf, $phone) {
        try {
            // Verificar se email já existe para outro usuário
            $checkStmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $id]);
            
            if ($checkStmt->rowCount() > 0) {
                return ['status' => 'error', 'message' => 'Email já está em uso por outro usuário!'];
            }
            
            $stmt = $this->pdo->prepare("UPDATE users SET name = ?, email = ?, cpf = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$name, $email, $cpf, $phone, $id]);
            
            if ($result) {
                return ['status' => 'success', 'message' => 'Perfil atualizado com sucesso!'];
            } else {
                return ['status' => 'error', 'message' => 'Erro ao atualizar perfil!'];
            }
        } catch(PDOException $e) {
            return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    // Método para alterar senha
    public function changePassword($id, $currentPassword, $newPassword) {
        try {
            // Primeiro verificar a senha atual
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['status' => 'error', 'message' => 'Senha atual incorreta!'];
            }
            
            // Atualizar com nova senha
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $updateStmt->execute([$hashedNewPassword, $id]);
            
            if ($result) {
                return ['status' => 'success', 'message' => 'Senha alterada com sucesso!'];
            } else {
                return ['status' => 'error', 'message' => 'Erro ao alterar senha!'];
            }
        } catch(PDOException $e) {
            return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
}