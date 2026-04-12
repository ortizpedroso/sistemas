<?php
class SurveyAnalytics {
    private $pdo;

    public function __construct($database) {
        $this->pdo = $database;
    }

    // Save a response to a survey
    public function saveResponse($surveyId, $answers) {
        try {
            $this->pdo->beginTransaction();

            // Insert response metadata
            $stmt = $this->pdo->prepare("INSERT INTO survey_responses (survey_id, created_at) VALUES (?, NOW())");
            $stmt->execute([$surveyId]);
            $responseId = $this->pdo->lastInsertId();

            // Insert individual answers
            $stmtAnswer = $this->pdo->prepare("INSERT INTO survey_answers (response_id, field_id, answer_text, created_at) VALUES (?, ?, ?, NOW())");

            foreach ($answers as $fieldId => $answer) {
                // Handle array answers (like checkboxes)
                if (is_array($answer)) {
                    $answerText = json_encode($answer);
                } else {
                    $answerText = $answer;
                }

                $stmtAnswer->execute([$responseId, $fieldId, $answerText]);
            }

            $this->pdo->commit();
            return ['status' => 'success', 'message' => 'Resposta enviada com sucesso!'];
        } catch(PDOException $e) {
            $this->pdo->rollBack();
            return ['status' => 'error', 'message' => 'Erro ao salvar resposta: ' . $e->getMessage()];
        }
    }

    // Get total number of responses for a survey
    public function getResponsesCount($surveyId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM survey_responses WHERE survey_id = ?");
            $stmt->execute([$surveyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch(PDOException $e) {
            return 0;
        }
    }

    // Get aggregated answers for chart generation
    public function getAggregatedAnswers($surveyId, $fieldId, $fieldType) {
        try {
            if ($fieldType == 'text' || $fieldType == 'textarea') {
                // For text, just return the recent answers
                $stmt = $this->pdo->prepare("
                    SELECT a.answer_text, r.created_at
                    FROM survey_answers a
                    JOIN survey_responses r ON a.response_id = r.id
                    WHERE r.survey_id = ? AND a.field_id = ? AND a.answer_text IS NOT NULL AND a.answer_text != ''
                    ORDER BY r.created_at DESC
                    LIMIT 20
                ");
                $stmt->execute([$surveyId, $fieldId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else if ($fieldType == 'radio' || $fieldType == 'select') {
                // For choice fields, aggregate the counts
                $stmt = $this->pdo->prepare("
                    SELECT answer_text as label, COUNT(*) as count
                    FROM survey_answers a
                    JOIN survey_responses r ON a.response_id = r.id
                    WHERE r.survey_id = ? AND a.field_id = ? AND a.answer_text IS NOT NULL AND a.answer_text != ''
                    GROUP BY answer_text
                    ORDER BY count DESC
                ");
                $stmt->execute([$surveyId, $fieldId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else if ($fieldType == 'checkbox') {
                // Checkboxes are stored as JSON arrays, so we need to fetch all and aggregate in PHP
                $stmt = $this->pdo->prepare("
                    SELECT answer_text
                    FROM survey_answers a
                    JOIN survey_responses r ON a.response_id = r.id
                    WHERE r.survey_id = ? AND a.field_id = ? AND a.answer_text IS NOT NULL AND a.answer_text != ''
                ");
                $stmt->execute([$surveyId, $fieldId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $counts = [];
                foreach ($results as $row) {
                    $choices = json_decode($row['answer_text'], true);
                    if (is_array($choices)) {
                        foreach ($choices as $choice) {
                            if (!isset($counts[$choice])) {
                                $counts[$choice] = 0;
                            }
                            $counts[$choice]++;
                        }
                    }
                }

                // Format for chart
                $formatted = [];
                foreach ($counts as $label => $count) {
                    $formatted[] = ['label' => $label, 'count' => $count];
                }

                // Sort by count descending
                usort($formatted, function($a, $b) {
                    return $b['count'] - $a['count'];
                });

                return $formatted;
            }
            return [];
        } catch(PDOException $e) {
            return [];
        }
    }
}