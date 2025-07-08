<?php
/**
 * 日志记录类
 */
class Logger {
    private $logFile;
    private $maxLogSize;
    private $maxLogFiles;
    
    public function __construct($logFile = 'logs/access.log', $maxLogSize = 10485760, $maxLogFiles = 5) {
        $this->logFile = $logFile;
        $this->maxLogSize = $maxLogSize; // 10MB
        $this->maxLogFiles = $maxLogFiles;
        
        // 确保日志目录存在
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * 记录访问日志
     */
    public function logAccess($request, $response, $processingTime = 0) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'request_data' => $request,
            'response_data' => $response,
            'processing_time' => $processingTime,
            'response_code' => http_response_code()
        ];
        
        $this->writeLog($logEntry);
    }
    
    /**
     * 获取客户端真实IP
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * 写入日志
     */
    private function writeLog($logEntry) {
        // 检查日志文件大小，如果超过限制则轮转
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxLogSize) {
            $this->rotateLog();
        }
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 日志轮转
     */
    private function rotateLog() {
        for ($i = $this->maxLogFiles - 1; $i > 0; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i == $this->maxLogFiles - 1) {
                    unlink($oldFile); // 删除最老的日志
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        if (file_exists($this->logFile)) {
            rename($this->logFile, $this->logFile . '.1');
        }
    }
    
    /**
     * 读取日志
     */
    public function getLogs($limit = 100, $offset = 0) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = [];
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // 倒序读取（最新的在前面）
        $lines = array_reverse($lines);
        
        $totalLines = count($lines);
        $startIndex = $offset;
        $endIndex = min($offset + $limit, $totalLines);
        
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $logData = json_decode($lines[$i], true);
            if ($logData) {
                $logs[] = $logData;
            }
        }
        
        return [
            'logs' => $logs,
            'total' => $totalLines,
            'offset' => $offset,
            'limit' => $limit
        ];
    }
    
    /**
     * 获取统计数据
     */
    public function getStats($days = 7) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $stats = [
            'total_requests' => 0,
            'success_requests' => 0,
            'error_requests' => 0,
            'unique_ips' => [],
            'daily_stats' => [],
            'top_ips' => [],
            'avg_processing_time' => 0
        ];
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        $totalProcessingTime = 0;
        $ipCounts = [];
        $dailyCounts = [];
        
        foreach ($lines as $line) {
            $logData = json_decode($line, true);
            if (!$logData) continue;
            
            $logDate = substr($logData['timestamp'], 0, 10);
            if ($logDate < $cutoffDate) continue;
            
            $stats['total_requests']++;
            
            // 统计成功和失败请求
            if (isset($logData['response_data']['code']) && $logData['response_data']['code'] == 1) {
                $stats['success_requests']++;
            } else {
                $stats['error_requests']++;
            }
            
            // 统计IP
            $ip = $logData['ip'];
            $stats['unique_ips'][$ip] = true;
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
            
            // 统计每日请求
            $dailyCounts[$logDate] = ($dailyCounts[$logDate] ?? 0) + 1;
            
            // 统计处理时间
            if (isset($logData['processing_time'])) {
                $totalProcessingTime += $logData['processing_time'];
            }
        }
        
        $stats['unique_ips'] = count($stats['unique_ips']);
        $stats['avg_processing_time'] = $stats['total_requests'] > 0 ? 
            round($totalProcessingTime / $stats['total_requests'], 3) : 0;
        
        // 排序IP统计
        arsort($ipCounts);
        $stats['top_ips'] = array_slice($ipCounts, 0, 10, true);
        
        // 填充每日统计
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stats['daily_stats'][] = [
                'date' => $date,
                'count' => $dailyCounts[$date] ?? 0
            ];
        }
        
        return $stats;
    }
}

