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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Processar o upload da imagem
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $imageName = uniqid() . "." . $imageFileType;
    $targetFilePath = $targetDir . $imageName;
    
    // Validar arquivo de imagem
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $error = "O arquivo enviado não é uma imagem válida.";
    } elseif ($_FILES["image"]["size"] > 5000000) { // 5MB
        $error = "Desculpe, sua imagem é muito grande (máximo 5MB).";
    } elseif (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        $error = "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
    } else {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            // Processar os dados do formulário
            $productName = sanitize($_POST['product_name']);
            $brand = sanitize($_POST['brand']);
            $expirationDate = $_POST['expiration_date'];
            $storeName = sanitize($_POST['store_name']);
            $storeAddress = sanitize($_POST['store_address']);
            $description = sanitize($_POST['description']);
            
            // Validar campos obrigatórios
            if (empty($productName) || empty($brand) || empty($expirationDate) || empty($storeName) || empty($storeAddress)) {
                $error = "Todos os campos obrigatórios devem ser preenchidos!";
            } else {
                $result = $report->createReport(
                    $_SESSION['user_id'],
                    $productName,
                    $brand,
                    $expirationDate,
                    $storeName,
                    $storeAddress,
                    $description,
                    $targetFilePath
                );
                
                if ($result['status'] === 'success') {
                    $success = $result['message'];
                    // Limpar os campos do formulário após envio bem-sucedido
                    unset($_POST);
                } else {
                    $error = $result['message'];
                }
            }
        } else {
            $error = "Desculpe, ocorreu um erro ao fazer o upload da sua imagem.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <h2>Fazer Nova Denúncia</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_name" class="form-label">Nome do Produto *</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Marca *</label>
                                <input type="text" class="form-control" id="brand" name="brand" value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiration_date" class="form-label">Data de Validade *</label>
                                <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?php echo isset($_POST['expiration_date']) ? $_POST['expiration_date'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="store_name" class="form-label">Nome do Estabelecimento *</label>
                                <input type="text" class="form-control" id="store_name" name="store_name" value="<?php echo isset($_POST['store_name']) ? htmlspecialchars($_POST['store_name']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="store_address" class="form-label">Endereço do Estabelecimento *</label>
                            <input type="text" class="form-control" id="store_address" name="store_address" value="<?php echo isset($_POST['store_address']) ? htmlspecialchars($_POST['store_address']) : ''; ?>" required>
                            <div class="form-text">Forneça o endereço completo do estabelecimento comercial.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição da Situação</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">Descreva detalhadamente a situação encontrada.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Foto do Produto Vencido *</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" capture="environment" required>
                            <div class="form-text">Tire uma foto clara do produto vencido, incluindo o rótulo com a data de validade visível. A geolocalização será extraída automaticamente da imagem para verificação.</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Importante:</strong> As informações fornecidas serão utilizadas para fins de verificação e fiscalização. Certifique-se de que todos os dados estejam corretos antes de enviar.
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Enviar Denúncia</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>