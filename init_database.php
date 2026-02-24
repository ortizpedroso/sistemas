<?php
// Script para inicializar o banco de dados com tabelas necessárias

require_once 'config/config.php';

try {
    // SQL para criar tabela de usuários
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        cpf VARCHAR(20) UNIQUE NOT NULL,
        phone VARCHAR(20) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )";
    
    $pdo->exec($sqlUsers);
    echo "Tabela 'users' criada com sucesso!<br>";
    
    // SQL para criar tabela de denúncias
    $sqlReports = "CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        brand VARCHAR(255) NOT NULL,
        expiration_date DATE NOT NULL,
        store_name VARCHAR(255) NOT NULL,
        store_address TEXT NOT NULL,
        description TEXT,
        image_path VARCHAR(500),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        status ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
        admin_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sqlReports);
    echo "Tabela 'reports' criada com sucesso!<br>";
    
    // Criar usuário admin padrão se não existir
    $checkAdmin = $pdo->query("SELECT id FROM users WHERE email = 'admin@admin.com'");
    if ($checkAdmin->rowCount() == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sqlInsertAdmin = "INSERT INTO users (name, email, password, cpf, phone, role) VALUES 
                          ('Administrador', 'admin@admin.com', '$defaultPassword', '000.000.000-00', '(00) 00000-0000', 'admin')";
        
        $pdo->exec($sqlInsertAdmin);
        echo "Usuário admin padrão criado:<br>";
        echo "Email: admin@admin.com<br>";
        echo "Senha: admin123<br>";
    } else {
        echo "Usuário admin já existe.<br>";
    }
    
    echo "<br>Banco de dados inicializado com sucesso!";
    
} catch(PDOException $e) {
    echo "Erro ao inicializar o banco de dados: " . $e->getMessage();
}