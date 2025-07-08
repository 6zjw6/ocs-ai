<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 记录开始时间
$startTime = microtime(true);

// 加载配置和日志类
$config = require_once 'config.php';
require_once 'logger.php';

// 初始化日志记录器
$logger = new Logger();

/**
 * 调用AI API
 */
function callAiApi($question, $config) {
    $url = $config['ai_api']['base_url'];
    $apiKey = $config['ai_api']['api_key'];
    $model = $config['ai_api']['model'];
    
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $config['request_config']['system_prompt']
            ],
            [
                'role' => 'user',
                'content' => $question
            ]
        ],
        'max_tokens' => $config['request_config']['max_tokens'],
        'temperature' => $config['request_config']['temperature']
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => $config['ai_api']['timeout'],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL错误: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('API请求失败，HTTP状态码: ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON解析错误: ' . json_last_error_msg());
    }
    
    return $result;
}

/**
 * 格式化响应
 */
function formatResponse($question, $answer, $config) {
    $mapping = $config['response_mapping']['success'];
    
    return [
        $mapping['question_field'] => $question,
        $mapping['answer_field'] => $answer,
        'code' => $mapping['code']
    ];
}

/**
 * 格式化错误响应
 */
function formatErrorResponse($message, $config) {
    $mapping = $config['response_mapping']['error'];
    
    return [
        'code' => $mapping['code'],
        $mapping['message_field'] => $message
    ];
}

// 初始化请求和响应数据
$requestData = [];
$responseData = [];

try {
    // 获取请求参数
    $method = $_SERVER['REQUEST_METHOD'];
    $question = '';
    
    if ($method === 'GET') {
        $question = $_GET['title'] ?? $_GET['question'] ?? '';
        $requestData = $_GET;
    } elseif ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($data) {
            $question = $data['title'] ?? $data['question'] ?? '';
            $requestData = $data;
        } else {
            $question = $_POST['title'] ?? $_POST['question'] ?? '';
            $requestData = $_POST;
        }
    }
    
    // 验证问题参数
    if (empty($question)) {
        $responseData = formatErrorResponse('缺少问题参数', $config);
        echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
        
        // 记录日志
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        $logger->logAccess($requestData, $responseData, $processingTime);
        exit();
    }
    
    // 调用AI API
    $aiResponse = callAiApi($question, $config);
    
    // 提取答案
    if (isset($aiResponse['choices'][0]['message']['content'])) {
        $answer = trim($aiResponse['choices'][0]['message']['content']);
        
        // 返回格式化响应
        $responseData = formatResponse($question, $answer, $config);
        echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
    } else {
        $responseData = formatErrorResponse('AI响应格式错误', $config);
        echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    $responseData = formatErrorResponse($e->getMessage(), $config);
    echo json_encode($responseData, JSON_UNESCAPED_UNICODE);
} finally {
    // 记录访问日志
    $processingTime = round((microtime(true) - $startTime) * 1000, 2);
    $logger->logAccess($requestData, $responseData, $processingTime);
}

