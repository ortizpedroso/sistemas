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
$reportClass = new Report($pdo);

// Verificar se o ID da denúncia foi fornecido
if (!isset($_GET['id'])) {
    header('Location: my_reports.php');
    exit;
}

$reportId = (int)$_GET['id'];
$report = $reportClass->getReportById($reportId);

// Verificar se a denúncia pertence ao usuário (ou se é admin)
if (!$report || ($_SESSION['user_id'] != $report['user_id'] && $_SESSION['role'] !== 'admin')) {
    header('Location: my_reports.php');
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <h2>Detalhes da Denúncia #<?php echo $report['id']; ?></h2>
            
            <div class="card">
                <div class="card-header">
                    <h5>Informações da Denúncia</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Produto:</strong> <?php echo htmlspecialchars($report['product_name']); ?></p>
                            <p><strong>Marca:</strong> <?php echo htmlspecialchars($report['brand']); ?></p>
                            <p><strong>Data de Validade:</strong> <?php echo date('d/m/Y', strtotime($report['expiration_date'])); ?></p>
                            <p><strong>Nome do Estabelecimento:</strong> <?php echo htmlspecialchars($report['store_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <span class="badge 
                                    <?php 
                                        switch($report['status']) {
                                            case 'pending': echo 'bg-warning'; break;
                                            case 'in_progress': echo 'bg-info'; break;
                                            case 'resolved': echo 'bg-success'; break;
                                            default: echo 'bg-secondary';
                                        }
                                    ?>
                                ">
                                    <?php echo $report['status_text']; ?>
                                </span>
                            </p>
                            <p><strong>Data da Denúncia:</strong> <?php echo date('d/m/Y H:i:s', strtotime($report['created_at'])); ?></p>
                            <p><strong>Última Atualização:</strong> <?php echo $report['updated_at'] ? date('d/m/Y H:i:s', strtotime($report['updated_at'])) : 'Nunca'; ?></p>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <p><strong>Denunciante:</strong> <?php echo htmlspecialchars($report['user_name']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Endereço do Estabelecimento:</strong><br>
                        <?php echo htmlspecialchars($report['store_address']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Descrição:</strong><br>
                        <?php echo htmlspecialchars($report['description']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Foto do Produto:</strong><br>
                        <?php if ($report['image_path'] && file_exists($report['image_path'])): ?>
                            <img src="<?php echo $report['image_path']; ?>" alt="Foto do produto vencido" class="img-fluid rounded" style="max-height: 400px;">
                        <?php else: ?>
                            <p class="text-muted">Imagem não disponível</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($report['latitude'] && $report['longitude']): ?>
                        <div class="mb-3">
                            <strong>Localização (extraída da imagem):</strong><br>
                            Latitude: <?php echo $report['latitude']; ?>, Longitude: <?php echo $report['longitude']; ?>
                            <br>
                            <small class="text-muted">Essas coordenadas foram extraídas automaticamente dos metadados da imagem para verificação de veracidade.</small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($report['admin_note']): ?>
                        <div class="alert alert-info">
                            <strong>Observação do Administrador:</strong><br>
                            <?php echo htmlspecialchars($report['admin_note']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="my_reports.php" class="btn btn-secondary">Voltar para Minhas Denúncias</a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/edit_report.php?id=<?php echo $report['id']; ?>" class="btn btn-warning">Editar Status</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>