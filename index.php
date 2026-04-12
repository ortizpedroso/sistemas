<?php
// Página principal do sistema
require_once 'config/config.php';
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1>Bem-vindo ao Sistema de Denúncias de Produtos Vencidos</h1>
            <p class="lead">Este sistema permite registrar e gerenciar denúncias de produtos vencidos em estabelecimentos comerciais.</p>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Ações Disponíveis - Denúncias</h5>
                    <div class="d-grid gap-2 d-md-block">
                        <a href="report.php" class="btn btn-primary mb-2">Fazer Nova Denúncia</a>
                        <a href="my_reports.php" class="btn btn-info mb-2">Minhas Denúncias</a>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Ações Disponíveis - Pesquisas</h5>
                    <div class="d-grid gap-2 d-md-block">
                        <a href="create_survey.php" class="btn btn-success mb-2">Criar Nova Pesquisa</a>
                        <a href="my_surveys.php" class="btn btn-info mb-2 text-white">Minhas Pesquisas</a>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Conta</h5>
                    <div class="d-grid gap-2 d-md-block">
                        <a href="profile.php" class="btn btn-secondary mb-2">Meu Perfil</a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="btn btn-warning mb-2">Painel Admin</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-danger mb-2">Sair</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>