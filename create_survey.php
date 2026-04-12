<?php
require_once 'config/config.php';
require_once 'classes/Survey.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$survey = new Survey($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);

    // Process dynamic fields
    $fields = [];
    if (isset($_POST['fields']) && is_array($_POST['fields'])) {
        foreach ($_POST['fields'] as $f) {
            $field = [
                'type' => htmlspecialchars($f['type']),
                'label' => htmlspecialchars($f['label']),
                'required' => isset($f['required']) ? true : false
            ];

            // Extract options for choice fields
            if (in_array($field['type'], ['radio', 'checkbox', 'select']) && !empty($f['options'])) {
                $optionsArray = array_map('trim', explode(',', $f['options']));
                $optionsArray = array_filter($optionsArray); // Remove empty
                $field['options'] = $optionsArray;
            }

            $fields[] = $field;
        }
    }

    if (empty($title) || empty($fields)) {
        $error = "Título e pelo menos uma pergunta são obrigatórios.";
    } else {
        $result = $survey->createSurvey($_SESSION['user_id'], $title, $description, $fields);
        if ($result['status'] === 'success') {
            $success = $result['message'];
            header("Refresh: 2; URL=my_surveys.php");
        } else {
            $error = $result['message'];
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4 mb-5">
    <h2>Criar Nova Pesquisa Personalizada</h2>
    <p>Crie um formulário de pesquisa customizado para compartilhar.</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" id="surveyForm">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Título da Pesquisa *</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Descrição (Opcional)</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
            </div>
        </div>

        <h4>Perguntas</h4>
        <div id="fieldsContainer">
            <!-- Fields will be added here dynamically -->
        </div>

        <div class="mb-4">
            <button type="button" class="btn btn-outline-primary" onclick="addField()">+ Adicionar Pergunta</button>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg">Salvar Pesquisa</button>
        </div>
    </form>
</div>

<script>
let fieldCount = 0;

function addField() {
    const container = document.getElementById('fieldsContainer');
    const index = fieldCount;

    const fieldHtml = `
    <div class="card mb-3 field-card" id="field_${index}">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-8">
                    <label class="form-label">Pergunta *</label>
                    <input type="text" class="form-control" name="fields[${index}][label]" required placeholder="Ex: Qual sua idade?">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo de Resposta</label>
                    <select class="form-select" name="fields[${index}][type]" onchange="toggleOptions(${index}, this.value)">
                        <option value="text">Texto Curto</option>
                        <option value="textarea">Texto Longo</option>
                        <option value="radio">Múltipla Escolha (Uma opção)</option>
                        <option value="checkbox">Caixas de Seleção (Várias opções)</option>
                        <option value="select">Menu Suspenso</option>
                    </select>
                </div>
            </div>

            <div class="mb-2" id="options_container_${index}" style="display: none;">
                <label class="form-label">Opções (separadas por vírgula) *</label>
                <input type="text" class="form-control" id="options_input_${index}" name="fields[${index}][options]" placeholder="Ex: Opção A, Opção B, Opção C">
                <small class="text-muted">Apenas para Múltipla Escolha, Caixas de Seleção e Menu Suspenso.</small>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="fields[${index}][required]" id="req_${index}" value="1">
                    <label class="form-check-label" for="req_${index}">Obrigatória</label>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeField(${index})">Remover</button>
            </div>
        </div>
    </div>
    `;

    container.insertAdjacentHTML('beforeend', fieldHtml);
    fieldCount++;
}

function removeField(index) {
    const field = document.getElementById(`field_${index}`);
    if (field) {
        field.remove();
    }
}

function toggleOptions(index, type) {
    const container = document.getElementById(`options_container_${index}`);
    const input = document.getElementById(`options_input_${index}`);

    if (type === 'radio' || type === 'checkbox' || type === 'select') {
        container.style.display = 'block';
        input.required = true;
    } else {
        container.style.display = 'none';
        input.required = false;
    }
}

// Add one field by default
window.onload = function() {
    addField();
};
</script>

<?php include 'includes/footer.php'; ?>