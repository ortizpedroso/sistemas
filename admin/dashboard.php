<?php
require_once '../config/config.php';
require_once '../classes/User.php';
require_once '../classes/Report.php';

session_start();

// Verificar se usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user = new User($pdo);
$report = new Report($pdo);

// Obter todas as denúncias
$allReports = $report->getAllReports();

// Obter estatísticas
$totalReports = count($allReports);
$pendingReports = 0;
$inProgressReports = 0;
$resolvedReports = 0;

foreach ($allReports as $rep) {
    switch ($rep['status']) {
        case 'pending':
            $pendingReports++;
            break;
        case 'in_progress':
            $inProgressReports++;
            break;
        case 'resolved':
            $resolvedReports++;
            break;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Painel Administrativo</h2>
            <p class="lead">Bem-vindo ao painel administrativo do sistema de denúncias de produtos vencidos.</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Denúncias</h5>
                    <h2><?php echo $totalReports; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pendentes</h5>
                    <h2><?php echo $pendingReports; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Em Análise</h5>
                    <h2><?php echo $inProgressReports; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Resolvidas</h5>
                    <h2><?php echo $resolvedReports; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <h3>Últimas Denúncias Recebidas</h3>
            
            <?php if (empty($allReports)): ?>
                <div class="alert alert-info">
                    Nenhuma denúncia registrada até o momento.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Produto</th>
                                <th>Estabelecimento</th>
                                <th>Denunciante</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Pegar apenas as 10 mais recentes
                            $recentReports = array_slice($allReports, 0, 10);
                            foreach ($recentReports as $rep): 
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rep['id']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rep['product_name']); ?></strong><br>
                                        <small class="text-muted">Marca: <?php echo htmlspecialchars($rep['brand']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($rep['store_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($rep['store_address']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($rep['user_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($rep['created_at'])); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                switch($rep['status']) {
                                                    case 'pending': echo 'bg-warning'; break;
                                                    case 'in_progress': echo 'bg-info'; break;
                                                    case 'resolved': echo 'bg-success'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                            ?>
                                        ">
                                            <?php echo $rep['status_text']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_report.php?id=<?php echo $rep['id']; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        <a href="edit_report.php?id=<?php echo $rep['id']; ?>" class="btn btn-sm btn-outline-warning">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <a href="all_reports.php" class="btn btn-primary">Ver Todas as Denúncias</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>