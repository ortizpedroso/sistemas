<?php
require_once 'config/config.php';
require_once 'classes/Survey.php';
require_once 'classes/SurveyAnalytics.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$survey = new Survey($pdo);
$analytics = new SurveyAnalytics($pdo);

$mySurveys = $survey->getSurveysByUser($_SESSION['user_id']);

// Get current base URL for sharing links
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$baseDir = dirname($_SERVER['PHP_SELF']);
if ($baseDir == '/' || $baseDir == '\\') { $baseDir = ''; }
$baseUrl = $protocol . "://" . $host . $baseDir;

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Minhas Pesquisas</h2>
            <p>Gerencie seus formulários e visualize os relatórios de resultados.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="create_survey.php" class="btn btn-success">+ Nova Pesquisa</a>
        </div>
    </div>

    <?php if (empty($mySurveys)): ?>
        <div class="alert alert-info">Você ainda não criou nenhuma pesquisa.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($mySurveys as $s): ?>
                <?php
                    $responseCount = $analytics->getResponsesCount($s['id']);
                    $shareLink = $baseUrl . "/public_survey.php?u=" . $s['uuid'];
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($s['title']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">Criado em: <?php echo date('d/m/Y H:i', strtotime($s['created_at'])); ?></h6>
                            <p class="card-text text-truncate"><?php echo htmlspecialchars($s['description'] ?? 'Sem descrição.'); ?></p>

                            <div class="mb-3">
                                <span class="badge bg-primary rounded-pill"><?php echo $responseCount; ?> Respostas</span>
                            </div>

                            <div class="input-group mb-3">
                                <input type="text" class="form-control form-control-sm" value="<?php echo $shareLink; ?>" id="link_<?php echo $s['id']; ?>" readonly>
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="copyLink(<?php echo $s['id']; ?>)">Copiar Link</button>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="survey_report.php?id=<?php echo $s['id']; ?>" class="btn btn-info btn-sm text-white"><i class="fas fa-chart-pie"></i> Ver Relatório Gráfico</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function copyLink(id) {
    var copyText = document.getElementById("link_" + id);
    copyText.select();
    copyText.setSelectionRange(0, 99999); /* For mobile devices */
    document.execCommand("copy");
    alert("Link copiado para a área de transferência!");
}
</script>

<?php include 'includes/footer.php'; ?>