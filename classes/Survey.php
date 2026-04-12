<?php
class Survey {
    private $pdo;

    public function __construct($database) {
        $this->pdo = $database;
    }

    // Method to create a new survey with its fields
    public function createSurvey($userId, $title, $description, $fields) {
        try {
            $this->pdo->beginTransaction();

            // Generate UUID for public sharing
            $uuid = bin2hex(random_bytes(18));

            // Insert survey
            $stmt = $this->pdo->prepare("INSERT INTO surveys (user_id, title, description, uuid, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $title, $description, $uuid]);
            $surveyId = $this->pdo->lastInsertId();

            // Insert fields
            if (!empty($fields) && is_array($fields)) {
                $order = 0;
                $stmtField = $this->pdo->prepare("INSERT INTO survey_fields (survey_id, field_type, label, options, field_order, is_required, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

                foreach ($fields as $field) {
                    $type = $field['type'] ?? 'text';
                    $label = $field['label'] ?? 'Untitled';
                    $options = isset($field['options']) ? json_encode($field['options']) : null;
                    $isRequired = isset($field['required']) && $field['required'] ? 1 : 0;

                    $stmtField->execute([$surveyId, $type, $label, $options, $order, $isRequired]);
                    $order++;
                }
            }

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Pesquisa criada com sucesso!', 'id' => $surveyId, 'uuid' => $uuid];
        } catch(PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Erro ao criar pesquisa: ' . $e->getMessage()];
        } catch(Exception $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    // Get all surveys by a specific user
    public function getSurveysByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM surveys WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Get a survey by UUID (for public access)
    public function getSurveyByUuid($uuid) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM surveys WHERE uuid = ?");
            $stmt->execute([$uuid]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Get a survey by ID
    public function getSurveyById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM surveys WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // Get fields for a survey
    public function getSurveyFields($surveyId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY field_order ASC");
            $stmt->execute([$surveyId]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Decode JSON options
            foreach ($fields as &$field) {
                if ($field['options']) {
                    $field['options'] = json_decode($field['options'], true);
                }
            }

            return $fields;
        } catch(PDOException $e) {
            return [];
        }
    }
}