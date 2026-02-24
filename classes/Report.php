<?php
class Report {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Método para criar uma nova denúncia
    public function createReport($userId, $productName, $brand, $expirationDate, $storeName, $storeAddress, $description, $imagePath) {
        try {
            // Extrair metadados de geolocalização da imagem
            $locationData = $this->extractImageMetadata($imagePath);
            $latitude = $locationData['latitude'] ?? null;
            $longitude = $locationData['longitude'] ?? null;
            
            $stmt = $this->pdo->prepare("INSERT INTO reports (user_id, product_name, brand, expiration_date, store_name, store_address, description, image_path, latitude, longitude, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $userId, 
                $productName, 
                $brand, 
                $expirationDate, 
                $storeName, 
                $storeAddress, 
                $description, 
                $imagePath, 
                $latitude, 
                $longitude
            ]);
            
            if ($result) {
                return ['status' => 'success', 'message' => 'Denúncia registrada com sucesso!', 'id' => $this->pdo->lastInsertId()];
            } else {
                return ['status' => 'error', 'message' => 'Erro ao registrar denúncia!'];
            }
        } catch(PDOException $e) {
            return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    // Método para extrair metadados de geolocalização da imagem
    private function extractImageMetadata($imagePath) {
        $metadata = [];
        
        if (file_exists($imagePath)) {
            $imageInfo = exif_read_data($imagePath);
            
            if ($imageInfo !== false) {
                // Extrair coordenadas GPS se disponíveis
                if (isset($imageInfo['GPSLatitude']) && isset($imageInfo['GPSLongitude'])) {
                    $lat = $this->convertGpsToDecimal($imageInfo['GPSLatitude'], $imageInfo['GPSLatitudeRef']);
                    $lng = $this->convertGpsToDecimal($imageInfo['GPSLongitude'], $imageInfo['GPSLongitudeRef']);
                    
                    $metadata['latitude'] = $lat;
                    $metadata['longitude'] = $lng;
                }
            }
        }
        
        return $metadata;
    }
    
    // Converter coordenadas GPS para formato decimal
    private function convertGpsToDecimal($coordinate, $hemisphere) {
        if (is_array($coordinate)) {
            $degrees = $this->parseCoordinate($coordinate[0]);
            $minutes = $this->parseCoordinate($coordinate[1]);
            $seconds = $this->parseCoordinate($coordinate[2]);
            
            $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
            
            // Aplicar sinal negativo para sul ou oeste
            if ($hemisphere == 'S' || $hemisphere == 'W') {
                $decimal = -$decimal;
            }
            
            return $decimal;
        }
        
        return null;
    }
    
    // Parsear coordenada GPS
    private function parseCoordinate($coordinate) {
        if (strpos($coordinate, '/') !== false) {
            list($numerator, $denominator) = explode('/', $coordinate);
            return $numerator / $denominator;
        }
        return $coordinate;
    }
    
    // Método para obter denúncias do usuário
    public function getUserReports($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, 
                       CASE 
                           WHEN r.status = 'pending' THEN 'Pendente'
                           WHEN r.status = 'in_progress' THEN 'Em Análise'
                           WHEN r.status = 'resolved' THEN 'Resolvida'
                           ELSE 'Desconhecido'
                       END as status_text
                FROM reports r 
                WHERE r.user_id = ? 
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Método para obter todas as denúncias (para admin)
    public function getAllReports() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, u.name as user_name,
                       CASE 
                           WHEN r.status = 'pending' THEN 'Pendente'
                           WHEN r.status = 'in_progress' THEN 'Em Análise'
                           WHEN r.status = 'resolved' THEN 'Resolvida'
                           ELSE 'Desconhecido'
                       END as status_text
                FROM reports r 
                JOIN users u ON r.user_id = u.id
                ORDER BY r.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Método para obter uma denúncia específica
    public function getReportById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, u.name as user_name,
                       CASE 
                           WHEN r.status = 'pending' THEN 'Pendente'
                           WHEN r.status = 'in_progress' THEN 'Em Análise'
                           WHEN r.status = 'resolved' THEN 'Resolvida'
                           ELSE 'Desconhecido'
                       END as status_text
                FROM reports r 
                JOIN users u ON r.user_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Método para atualizar o status de uma denúncia (para admin)
    public function updateReportStatus($reportId, $status, $adminNote = '') {
        try {
            $stmt = $this->pdo->prepare("UPDATE reports SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $adminNote, $reportId]);
            
            if ($result) {
                return ['status' => 'success', 'message' => 'Status da denúncia atualizado com sucesso!'];
            } else {
                return ['status' => 'error', 'message' => 'Erro ao atualizar status da denúncia!'];
            }
        } catch(PDOException $e) {
            return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
}