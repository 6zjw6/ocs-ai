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

// 加载日志类
require_once 'logger.php';

// 初始化日志记录器
$logger = new Logger();

/**
 * 返回JSON响应
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 获取请求参数
 */
function getParam($key, $default = null) {
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}

try {
    $action = getParam('action');
    
    switch ($action) {
        case 'stats':
            // 获取统计数据
            $days = (int)getParam('days', 7);
            $stats = $logger->getStats($days);
            jsonResponse($stats);
            break;
            
        case 'logs':
            // 获取日志数据
            $limit = (int)getParam('limit', 50);
            $offset = (int)getParam('offset', 0);
            $search = getParam('search', '');
            
            // 限制每次查询的最大数量
            $limit = min($limit, 200);
            
            $result = $logger->getLogs($limit, $offset);
            
            // 如果有搜索条件，进行过滤
            if (!empty($search)) {
                $filteredLogs = [];
                foreach ($result['logs'] as $log) {
                    $searchText = strtolower($search);
                    $logText = strtolower(json_encode($log, JSON_UNESCAPED_UNICODE));
                    
                    if (strpos($logText, $searchText) !== false) {
                        $filteredLogs[] = $log;
                    }
                }
                $result['logs'] = $filteredLogs;
                $result['total'] = count($filteredLogs);
            }
            
            jsonResponse($result);
            break;
            
        case 'recent':
            // 获取最近的日志
            $limit = (int)getParam('limit', 10);
            $result = $logger->getLogs($limit, 0);
            jsonResponse($result['logs']);
            break;
            
        case 'ip_stats':
            // 获取IP统计
            $days = (int)getParam('days', 7);
            $stats = $logger->getStats($days);
            jsonResponse([
                'top_ips' => $stats['top_ips'],
                'unique_ips' => $stats['unique_ips']
            ]);
            break;
            
        case 'daily_stats':
            // 获取每日统计
            $days = (int)getParam('days', 30);
            $stats = $logger->getStats($days);
            jsonResponse($stats['daily_stats']);
            break;
            
        case 'system_info':
            // 获取系统信息
            $logFile = 'logs/access.log';
            $systemInfo = [
                'php_version' => PHP_VERSION,
                'server_time' => date('Y-m-d H:i:s'),
                'log_file_exists' => file_exists($logFile),
                'log_file_size' => file_exists($logFile) ? filesize($logFile) : 0,
                'log_file_readable' => file_exists($logFile) && is_readable($logFile),
                'log_file_writable' => file_exists($logFile) && is_writable($logFile),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ];
            jsonResponse($systemInfo);
            break;
            
        case 'clear_logs':
            // 清空日志（需要管理员权限）
            $password = getParam('password');
            $adminPassword = 'admin123'; // 在实际使用中应该从配置文件读取
            
            if ($password !== $adminPassword) {
                jsonResponse(['error' => '权限不足'], 403);
            }
            
            $logFile = 'logs/access.log';
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                jsonResponse(['success' => true, 'message' => '日志已清空']);
            } else {
                jsonResponse(['error' => '日志文件不存在'], 404);
            }
            break;
            
        case 'export_logs':
            // 导出日志
            $format = getParam('format', 'json');
            $days = (int)getParam('days', 7);
            
            $result = $logger->getLogs(1000, 0); // 最多导出1000条
            
            if ($format === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="access_logs_' . date('Y-m-d') . '.csv"');
                
                echo "\xEF\xBB\xBF"; // UTF-8 BOM
                echo "时间,IP地址,请求方法,请求URI,请求数据,响应代码,处理时间\n";
                
                foreach ($result['logs'] as $log) {
                    $requestData = isset($log['request_data']) ? json_encode($log['request_data'], JSON_UNESCAPED_UNICODE) : '';
                    $responseCode = isset($log['response_data']['code']) ? $log['response_data']['code'] : '';
                    
                    echo sprintf('"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                        $log['timestamp'],
                        $log['ip'],
                        $log['method'],
                        $log['request_uri'],
                        str_replace('"', '""', $requestData),
                        $responseCode,
                        $log['processing_time']
                    );
                }
                exit();
            } else {
                // JSON格式
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="access_logs_' . date('Y-m-d') . '.json"');
                echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                exit();
            }
            break;
            
        default:
            jsonResponse(['error' => '无效的操作'], 400);
    }
    
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

