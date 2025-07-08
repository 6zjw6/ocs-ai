<?php
// 配置文件
return [
    // AI API 配置
    'ai_api' => [
        'base_url' => 'https://ark.cn-beijing.volces.com/api/v3/chat/completions',
        'api_key' => '你的火山key',
        'model' => 'doubao-1.5-pro-32k-250115',
        'timeout' => 30
    ],
    
    // 响应映射配置
    'response_mapping' => [
        // 成功响应映射
        'success' => [
            'code' => 1,
            'question_field' => 'question',
            'answer_field' => 'answer'
        ],
        
        // 失败响应映射
        'error' => [
            'code' => 0,
            'message_field' => 'msg'
        ]
    ],
    
    // 请求配置
    'request_config' => [
        'max_tokens' => 1000,
        'temperature' => 0.1,
        'system_prompt' => '你是一个专业的题目解答助手。请直接回答问题，不要添加额外的解释或格式。根据语气检测如果是判断题，就只回复正确或者错误，不要回答其他'
    ]
];

