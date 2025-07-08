# AI转接接口 - 硅基流动题库桥接（增强版）

一个功能完整的PHP转接接口，用于将题库系统与硅基流动AI API进行对接，并提供实时监控和日志分析功能。

## 🚀 功能特点

### 核心功能
- **纯PHP实现**：无需额外依赖，部署简单
- **支持多种请求方式**：GET和POST请求
- **灵活的配置系统**：支持自定义API密钥、模型和响应格式
- **完整的错误处理**：包含参数验证、网络错误、超时等处理
- **跨域支持**：支持前端直接调用

### 监控功能
- **实时访问监控**：可视化监控面板，实时显示访问情况
- **详细日志记录**：记录每次API调用的详细信息
- **统计分析**：提供访问统计、成功率分析、响应时间统计
- **日志搜索**：支持按IP、请求内容、响应内容搜索
- **数据导出**：支持JSON和CSV格式导出日志数据

## 📁 文件结构

```
ai-bridge-api/
├── index.php              # 主接口文件
├── config.php             # 配置文件
├── logger.php             # 日志记录类
├── api.php                # 数据API接口
├── dashboard.html          # 监控面板页面
├── generate_test_data.php  # 测试数据生成脚本
├── logs/                   # 日志文件目录
│   └── access.log         # 访问日志文件
└── README.md              # 说明文档
```

## 🔧 快速开始

### 1. 部署文件

将所有文件上传到您的Web服务器目录。

### 2. 配置API密钥

编辑 `config.php` 文件，修改以下配置：

```php
'ai_api' => [
    'api_key' => 'YOUR_API_KEY_HERE',  // 替换为您的硅基流动API密钥
    'model' => 'deepseek-chat',        // 可选择其他模型
]
```

### 3. 设置目录权限

确保日志目录可写：

```bash
chmod 755 logs/
chmod 644 logs/access.log  # 如果文件已存在
```

### 4. 测试接口

访问您的接口地址进行测试：

**GET请求示例：**
```
https://yourdomain.com/ai-bridge/index.php?title=1+1等于多少
```

**POST请求示例：**
```bash
curl -X POST https://yourdomain.com/ai-bridge/index.php \
  -H "Content-Type: application/json" \
  -d '{"title": "1+1等于多少"}'
```

### 5. 访问监控面板

打开浏览器访问：
```
https://yourdomain.com/ai-bridge/dashboard.html
```

## ⚙️ 配置说明

### AI API配置

```php
'ai_api' => [
    'base_url' => 'https://api.siliconflow.cn/v1/chat/completions',
    'api_key' => 'YOUR_API_KEY_HERE',
    'model' => 'deepseek-chat',
    'timeout' => 30
]
```

- `base_url`: 硅基流动API地址
- `api_key`: 您的API密钥
- `model`: 使用的AI模型（可选：deepseek-chat、qwen-plus等）
- `timeout`: 请求超时时间（秒）

### 响应映射配置

```php
'response_mapping' => [
    'success' => [
        'code' => 1,
        'question_field' => 'question',
        'answer_field' => 'answer'
    ],
    'error' => [
        'code' => 0,
        'message_field' => 'msg'
    ]
]
```

### 请求配置

```php
'request_config' => [
    'max_tokens' => 1000,
    'temperature' => 0.1,
    'system_prompt' => '你是一个专业的题目解答助手。请直接回答问题，不要添加额外的解释或格式。'
]
```

## 📊 监控面板功能

### 实时统计
- **总请求数**：显示指定时间段内的总请求数量
- **成功请求**：显示成功处理的请求数量和成功率
- **独立访客**：显示不同IP地址的访问数量
- **平均响应时间**：显示API响应的平均处理时间

### 可视化图表
- **每日访问趋势**：折线图显示每日访问量变化
- **请求状态分布**：饼图显示成功和失败请求的比例

### 日志管理
- **实时日志查看**：表格形式显示最新的访问日志
- **搜索功能**：支持按IP地址、请求内容、响应内容搜索
- **分页浏览**：支持大量日志数据的分页查看
- **自动刷新**：每30秒自动刷新数据

## 🔌 API接口说明

### 统计数据API
```
GET /api.php?action=stats&days=7
```

返回指定天数内的统计数据：
```json
{
  "total_requests": 100,
  "success_requests": 95,
  "error_requests": 5,
  "unique_ips": 20,
  "avg_processing_time": 250,
  "daily_stats": [...],
  "top_ips": {...}
}
```

### 日志数据API
```
GET /api.php?action=logs&limit=50&offset=0&search=关键词
```

返回日志数据：
```json
{
  "logs": [...],
  "total": 500,
  "offset": 0,
  "limit": 50
}
```

### 其他API
- `action=recent` - 获取最近的日志
- `action=ip_stats` - 获取IP统计
- `action=daily_stats` - 获取每日统计
- `action=system_info` - 获取系统信息
- `action=export_logs` - 导出日志数据

## 🎯 题库配置示例

在OCS题库系统中的配置示例：

```json
{
  "url": "https://yourdomain.com/ai-bridge/index.php",
  "name": "硅基流动AI题库",
  "method": "get",
  "contentType": "json",
  "data": {
    "title": "${title}",
    "type": "${type}",
    "options": "${options}"
  },
  "handler": "return (res) => res.code === 1 ? [res.question, res.answer] : [res.msg, undefined]"
}
```

## 📋 API响应格式

### 成功响应
```json
{
  "code": 1,
  "question": "1+1等于多少",
  "answer": "2"
}
```

### 错误响应
```json
{
  "code": 0,
  "msg": "错误信息"
}
```

## 🔍 支持的请求参数

- `title` - 问题内容（主要参数）
- `question` - 问题内容（备用参数）
- `type` - 题目类型（可选，用于日志记录）
- `options` - 题目选项（可选，用于日志记录）

## 🛡️ 安全特性

### 日志安全
- 自动日志轮转，防止日志文件过大
- 敏感信息过滤，不记录API密钥等敏感数据
- 访问IP记录，支持安全审计

### 错误处理
- 完整的异常捕获和处理
- 详细的错误日志记录
- 用户友好的错误信息返回

## 🔧 故障排除

### 常见问题

**问题1：监控面板无法显示数据**
- 检查 `logs/` 目录是否存在且可写
- 确认 `api.php` 文件可以正常访问
- 检查浏览器控制台是否有JavaScript错误

**问题2：接口返回空响应**
- 检查PHP错误日志
- 确认cURL扩展已启用
- 验证API密钥是否正确

**问题3：跨域请求失败**
- 确认CORS头部设置正确
- 检查浏览器控制台错误信息

**问题4：API调用超时**
- 增加timeout配置值
- 检查网络连接状况
- 确认硅基流动API服务状态

### 日志文件位置
- 访问日志：`logs/access.log`
- PHP错误日志：查看服务器错误日志
- 服务器日志：根据服务器配置查看

## 📈 性能优化

### 日志管理
- 自动日志轮转，默认保留5个历史文件
- 单个日志文件最大10MB
- 支持日志清理和导出功能

### 缓存策略
- 统计数据可考虑添加缓存机制
- 大量日志查询时建议使用分页

### 监控建议
- 定期检查日志文件大小
- 监控API响应时间
- 关注错误率变化

## 🔄 更新日志

### v2.0.0 (当前版本)
- ✅ 新增可视化监控面板
- ✅ 完整的日志记录系统
- ✅ 实时统计和图表展示
- ✅ 日志搜索和导出功能
- ✅ API接口扩展

### v1.0.0
- ✅ 基础AI转接功能
- ✅ 配置文件支持
- ✅ 错误处理机制

## 📞 技术支持

如需技术支持，请检查：
1. **PHP版本兼容性**（建议PHP 7.4+）
2. **服务器配置**（确保支持cURL扩展）
3. **API密钥权限**（确认硅基流动API密钥有效）
4. **网络连接状况**（确保服务器可访问外部API）
5. **文件权限**（确保日志目录可写）

## 📄 许可证

本项目采用MIT许可证，您可以自由使用、修改和分发。

---

**作者**: 墨韵流年：阿舰
**版本**: 2.0.0  
**更新时间**: 2025-06-10

