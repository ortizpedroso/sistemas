<?php
require_once 'config/config.php';
require_once 'classes/User.php';

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = new User($pdo);
$currentUser = $user->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $cpf = sanitize($_POST['cpf']);
        $phone = sanitize($_POST['phone']);
        
        $result = $user->updateProfile($_SESSION['user_id'], $name, $email, $cpf, $phone);
        
        if ($result['status'] === 'success') {
            $success = $result['message'];
            // Atualizar informações na sessão
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $currentUser = $user->getUserById($_SESSION['user_id']);
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmNewPassword = $_POST['confirm_new_password'];
        
        if ($newPassword !== $confirmNewPassword) {
            $error = "As novas senhas não coincidem!";
        } elseif (strlen($newPassword) < 6) {
            $error = "A nova senha deve ter pelo menos 6 caracteres!";
        } else {
            $result = $user->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
            
            if ($result['status'] === 'success') {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h2>Meu Perfil</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile">Informações do Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="password-tab" data-bs-toggle="tab" href="#password">Alterar Senha</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Aba de Informações do Perfil -->
                        <div class="tab-pane fade show active" id="profile">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Nome Completo:</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email:</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cpf" class="form-label">CPF:</label>
                                        <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo htmlspecialchars($currentUser['cpf']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Telefone:</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($currentUser['phone']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="created_at" class="form-label">Data de Cadastro:</label>
                                    <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i:s', strtotime($currentUser['created_at'])); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="last_login" class="form-label">Último Login:</label>
                                    <input type="text" class="form-control" value="<?php echo $currentUser['last_login'] ? date('d/m/Y H:i:s', strtotime($currentUser['last_login'])) : 'Nunca'; ?>" readonly>
                                </div>
                                
                                <input type="hidden" name="update_profile" value="1">
                                <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
                            </form>
                        </div>
                        
                        <!-- Aba de Alteração de Senha -->
                        <div class="tab-pane fade" id="password">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Senha Atual:</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nova Senha:</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_new_password" class="form-label">Confirmar Nova Senha:</label>
                                    <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                                </div>
                                
                                <input type="hidden" name="change_password" value="1">
                                <button type="submit" class="btn btn-warning">Alterar Senha</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>