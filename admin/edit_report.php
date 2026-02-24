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
$reportClass = new Report($pdo);

// Verificar se o ID da denúncia foi fornecido
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$reportId = (int)$_GET['id'];
$report = $reportClass->getReportById($reportId);

// Verificar se a denúncia existe
if (!$report) {
    header('Location: dashboard.php');
    exit;
}

// Processar atualização do status
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = sanitize($_POST['status']);
    $adminNote = sanitize($_POST['admin_note']);
    
    $result = $reportClass->updateReportStatus($reportId, $status, $adminNote);
    
    if ($result['status'] === 'success') {
        $success = $result['message'];
        // Atualizar informações locais
        $report['status'] = $status;
        $report['admin_note'] = $adminNote;
    } else {
        $error = $result['message'];
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h2>Editar Status da Denúncia #<?php echo $report['id']; ?></h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>Informações da Denúncia</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Produto:</strong> <?php echo htmlspecialchars($report['product_name']); ?></p>
                            <p><strong>Estabelecimento:</strong> <?php echo htmlspecialchars($report['store_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status Atual:</strong> 
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
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="status" class="form-label">Novo Status:</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" <?php echo $report['status'] === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="in_progress" <?php echo $report['status'] === 'in_progress' ? 'selected' : ''; ?>>Em Análise</option>
                                <option value="resolved" <?php echo $report['status'] === 'resolved' ? 'selected' : ''; ?>>Resolvida</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_note" class="form-label">Observação do Administrador:</label>
                            <textarea class="form-control" id="admin_note" name="admin_note" rows="4"><?php echo htmlspecialchars($report['admin_note']); ?></textarea>
                            <div class="form-text">Adicione observações sobre o tratamento desta denúncia.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">Atualizar Status</button>
                        <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>