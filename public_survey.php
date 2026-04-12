<?php
require_once 'config/config.php';
require_once 'classes/Survey.php';
require_once 'classes/SurveyAnalytics.php';

$survey = new Survey($pdo);
$analytics = new SurveyAnalytics($pdo);

if (!isset($_GET['u']) || empty($_GET['u'])) {
    die("Link de pesquisa inválido.");
}

$uuid = $_GET['u'];
$surveyData = $survey->getSurveyByUuid($uuid);

if (!$surveyData) {
    die("Pesquisa não encontrada ou não está mais disponível.");
}

$surveyId = $surveyData['id'];
$fields = $survey->getSurveyFields($surveyId);

$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answers'])) {
    $answers = $_POST['answers'];
    $result = $analytics->saveResponse($surveyId, $answers);

    if ($result['status'] === 'success') {
        $success = true;
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($surveyData['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .survey-container { max-width: 800px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .required-star { color: red; }
    </style>
</head>
<body>

<div class="container">
    <div class="survey-container">
        <?php if ($success): ?>
            <div class="text-center py-5">
                <h1 class="text-success mb-4"><i class="fas fa-check-circle"></i></h1>
                <h2>Obrigado por participar!</h2>
                <p class="lead">Sua resposta foi registrada com sucesso.</p>
            </div>
        <?php else: ?>
            <div class="mb-4 pb-3 border-bottom">
                <h2 class="mb-2"><?php echo htmlspecialchars($surveyData['title']); ?></h2>
                <?php if (!empty($surveyData['description'])): ?>
                    <p class="lead text-muted"><?php echo nl2br(htmlspecialchars($surveyData['description'])); ?></p>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php foreach ($fields as $field): ?>
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <?php echo htmlspecialchars($field['label']); ?>
                            <?php if ($field['is_required']): ?><span class="required-star">*</span><?php endif; ?>
                        </label>

                        <?php
                        $reqAttr = $field['is_required'] ? 'required' : '';
                        $nameAttr = "answers[" . $field['id'] . "]";
                        ?>

                        <?php if ($field['field_type'] == 'text'): ?>
                            <input type="text" class="form-control" name="<?php echo $nameAttr; ?>" <?php echo $reqAttr; ?>>

                        <?php elseif ($field['field_type'] == 'textarea'): ?>
                            <textarea class="form-control" name="<?php echo $nameAttr; ?>" rows="3" <?php echo $reqAttr; ?>></textarea>

                        <?php elseif ($field['field_type'] == 'radio'): ?>
                            <?php if (!empty($field['options'])): ?>
                                <?php foreach ($field['options'] as $idx => $opt): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="<?php echo $nameAttr; ?>" id="radio_<?php echo $field['id'].'_'.$idx; ?>" value="<?php echo htmlspecialchars($opt); ?>" <?php echo $reqAttr; ?>>
                                        <label class="form-check-label" for="radio_<?php echo $field['id'].'_'.$idx; ?>">
                                            <?php echo htmlspecialchars($opt); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        <?php elseif ($field['field_type'] == 'checkbox'): ?>
                            <?php if (!empty($field['options'])): ?>
                                <?php foreach ($field['options'] as $idx => $opt): ?>
                                    <div class="form-check">
                                        <!-- Note the [] for array submission -->
                                        <input class="form-check-input" type="checkbox" name="<?php echo $nameAttr; ?>[]" id="chk_<?php echo $field['id'].'_'.$idx; ?>" value="<?php echo htmlspecialchars($opt); ?>">
                                        <label class="form-check-label" for="chk_<?php echo $field['id'].'_'.$idx; ?>">
                                            <?php echo htmlspecialchars($opt); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($field['is_required']): ?>
                                    <small class="text-danger">Seleção obrigatória.</small>
                                <?php endif; ?>
                            <?php endif; ?>

                        <?php elseif ($field['field_type'] == 'select'): ?>
                            <select class="form-select" name="<?php echo $nameAttr; ?>" <?php echo $reqAttr; ?>>
                                <option value="">Selecione uma opção...</option>
                                <?php if (!empty($field['options'])): ?>
                                    <?php foreach ($field['options'] as $opt): ?>
                                        <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="d-grid mt-5">
                    <button type="submit" class="btn btn-primary btn-lg">Enviar Resposta</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>