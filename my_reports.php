<?php
require_once 'config/config.php';
require_once 'classes/User.php';
require_once 'classes/Report.php';

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = new User($pdo);
$report = new Report($pdo);

// Obter as denúncias do usuário
$userReports = $report->getUserReports($_SESSION['user_id']);
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Minhas Denúncias</h2>
            
            <?php if (empty($userReports)): ?>
                <div class="alert alert-info">
                    Você ainda não registrou nenhuma denúncia.
                    <a href="report.php" class="btn btn-primary ms-2">Fazer Nova Denúncia</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Produto</th>
                                <th>Estabelecimento</th>
                                <th>Data da Denúncia</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userReports as $rep): ?>
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
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($rep['created_at'])); ?></td>
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
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <a href="report.php" class="btn btn-primary">Fazer Nova Denúncia</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>