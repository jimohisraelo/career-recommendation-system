<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Db\Sql\Sql;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel(); // Loads index.phtml
    }

    public function testAction()
    {
        return new ViewModel(); // Loads test.phtml
    }

    public function submitAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return new JsonModel(['error' => 'Invalid request method']);
        }

        $rawBody = file_get_contents('php://input');
        file_put_contents('data/debug.log', "RAW BODY: $rawBody\n");

        $data = json_decode($rawBody, true);
        if (!$data || !is_array($data)) {
            return new JsonModel(['error' => 'Invalid JSON payload']);
        }

        // Required fields
        $requiredFields = [
            'openness', 'conscientiousness', 'extraversion',
            'agreeableness', 'neuroticism',
            'numerical', 'spatiall', 'perceptual',
            'abstract_reasoning', 'verbal'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || !is_numeric($data[$field])) {
                return new JsonModel(['error' => "Missing or invalid field: $field"]);
            }
        }

        try {
            $adapter = $this->getEvent()->getApplication()->getServiceManager()->get(\Laminas\Db\Adapter\Adapter::class);
            $sql = new Sql($adapter);

            // Insert data into DB (multiply OCEAN scores by 2)
            $insert = $sql->insert('user_scores')->values([
                'openness' => $data['openness'] * 2,
                'conscientiousness' => $data['conscientiousness'] * 2,
                'extraversion' => $data['extraversion'] * 2,
                'agreeableness' => $data['agreeableness'] * 2,
                'neuroticism' => $data['neuroticism'] * 2,
                'numerical' => $data['numerical'],
                'spatiall' => $data['spatiall'],
                'perceptual' => $data['perceptual'],
                'abstract_reasoning' => $data['abstract_reasoning'],
                'verbal' => $data['verbal'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $statement = $sql->prepareStatementForSqlObject($insert);
            $statement->execute();

            // Prepare payload for Flask
            $payload = [
                'O_score' => (float)$data['openness'] * 2,
                'C_score' => (float)$data['conscientiousness'] * 2,
                'E_score' => (float)$data['extraversion'] * 2,
                'A_score' => (float)$data['agreeableness'] * 2,
                'N_score' => (float)$data['neuroticism'] * 2,
                'Numerical Aptitude' => (float)$data['numerical'],
                'Spatial Aptitude' => (float)$data['spatiall'],
                'Perceptual Aptitude' => (float)$data['perceptual'],
                'Abstract Reasoning' => (float)$data['abstract_reasoning'],
                'Verbal Reasoning' => (float)$data['verbal']
            ];

            // Send to Flask
            $ch = curl_init('http://127.0.0.1:5000/predict');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload),
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $result = json_decode($response, true); // ✅ parse before using

            header('Content-Type: application/json');
echo json_encode($result);
exit;

            // Log request/response
            file_put_contents('data/flask_debug.log',
                "📤 Sent to Flask:\n" . json_encode($payload, JSON_PRETTY_PRINT) .
                "\n\n📥 Response:\n$response\n\nHTTP Code: $httpCode\nError: $error\n\n",
                FILE_APPEND
            );

            if (!$response || $httpCode !== 200) {
                return new JsonModel([
                    'error' => 'Flask call failed',
                    'http_code' => $httpCode,
                    'curl_error' => $error
                ]);
            }

            $result = json_decode($response, true);
            if (!is_array($result)) {
                return new JsonModel([
                    'error' => 'Invalid JSON from Flask',
                    'raw_response' => $response
                ]);
            }

            return new JsonModel($result);

        } catch (\Throwable $e) {
            return new JsonModel([
                'error' => 'Server error',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function analyzeDatasetAction()
    {
        $filePath = getcwd() . '/data/career_dataset.csv';

        if (!file_exists($filePath)) {
            return new JsonModel(['error' => 'CSV file not found']);
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);
        $rows = [];
        $careerList = [];
        $missingCount = 0;

        while (($row = fgetcsv($file)) !== false) {
            $rowAssoc = array_combine($headers, $row);
            $rows[] = $rowAssoc;

            foreach ($rowAssoc as $value) {
                if ($value === '' || $value === null) {
                    $missingCount++;
                }
            }

            $careerList[] = $rowAssoc['Career'] ?? '';
        }

        fclose($file);

        return new JsonModel([
            'total_rows' => count($rows),
            'unique_careers' => count(array_filter(array_unique($careerList))),
            'columns' => $headers,
            'missing_values' => $missingCount,
            'sample' => array_slice($rows, 0, 5)
        ]);
    }

    public function resultAction()
    {
        return new ViewModel(); // Loads result.phtml if needed
    }
}