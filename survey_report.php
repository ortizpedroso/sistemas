<?php
require_once 'config/config.php';
require_once 'classes/Survey.php';
require_once 'classes/SurveyAnalytics.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$survey = new Survey($pdo);
$analytics = new SurveyAnalytics($pdo);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: my_surveys.php');
    exit;
}

$surveyId = $_GET['id'];
$surveyData = $survey->getSurveyById($surveyId);

// Ensure the survey belongs to the logged-in user or user is admin
if (!$surveyData || ($surveyData['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    die("Acesso negado.");
}

$fields = $survey->getSurveyFields($surveyId);
$totalResponses = $analytics->getResponsesCount($surveyId);

?>

<?php include 'includes/header.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container mt-4 mb-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2>Relatório: <?php echo htmlspecialchars($surveyData['title']); ?></h2>
            <p class="text-muted">Total de Respostas: <strong><?php echo $totalResponses; ?></strong></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="my_surveys.php" class="btn btn-outline-secondary">Voltar</a>
        </div>
    </div>

    <?php if ($totalResponses == 0): ?>
        <div class="alert alert-info">Ainda não há respostas para esta pesquisa. Compartilhe o link para começar a coletar dados.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($fields as $index => $field): ?>
                <?php $chartData = $analytics->getAggregatedAnswers($surveyId, $field['id'], $field['field_type']); ?>

                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><?php echo ($index + 1) . '. ' . htmlspecialchars($field['label']); ?></h5>
                        </div>
                        <div class="card-body">

                            <?php if (empty($chartData)): ?>
                                <p class="text-muted">Nenhuma resposta registrada para esta pergunta.</p>
                            <?php else: ?>

                                <?php if ($field['field_type'] == 'text' || $field['field_type'] == 'textarea'): ?>
                                    <ul class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                                        <?php foreach ($chartData as $ans): ?>
                                            <li class="list-group-item">
                                                <small class="text-muted d-block"><?php echo date('d/m/Y H:i', strtotime($ans['created_at'])); ?></small>
                                                <?php echo htmlspecialchars($ans['answer_text']); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <!-- Canvas for Chart.js -->
                                    <div style="position: relative; height:250px; width:100%">
                                        <canvas id="chart_<?php echo $field['id']; ?>"></canvas>
                                    </div>

                                    <?php
                                    // Prepare data for JS
                                    $labels = [];
                                    $data = [];
                                    foreach ($chartData as $d) {
                                        $labels[] = $d['label'];
                                        $data[] = (int)$d['count'];
                                    }

                                    // Use Pie chart for Radio/Select, Bar for Checkbox
                                    $chartType = ($field['field_type'] == 'checkbox') ? 'bar' : 'pie';
                                    ?>

                                    <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const ctx_<?php echo $field['id']; ?> = document.getElementById('chart_<?php echo $field['id']; ?>').getContext('2d');
                                        new Chart(ctx_<?php echo $field['id']; ?>, {
                                            type: '<?php echo $chartType; ?>',
                                            data: {
                                                labels: <?php echo json_encode($labels); ?>,
                                                datasets: [{
                                                    label: 'Número de Respostas',
                                                    data: <?php echo json_encode($data); ?>,
                                                    backgroundColor: [
                                                        'rgba(54, 162, 235, 0.7)',
                                                        'rgba(255, 99, 132, 0.7)',
                                                        'rgba(255, 206, 86, 0.7)',
                                                        'rgba(75, 192, 192, 0.7)',
                                                        'rgba(153, 102, 255, 0.7)',
                                                        'rgba(255, 159, 64, 0.7)'
                                                    ],
                                                    borderWidth: 1
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                <?php if($chartType == 'bar'): ?>
                                                scales: {
                                                    y: {
                                                        beginAtZero: true,
                                                        ticks: { precision: 0 }
                                                    }
                                                },
                                                plugins: {
                                                    legend: { display: false }
                                                }
                                                <?php endif; ?>
                                            }
                                        });
                                    });
                                    </script>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>