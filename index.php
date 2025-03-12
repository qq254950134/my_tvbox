<?php
// TVBox多仓管理系统 - 增强版
// 配置文件路径
$config_file = 'config.json';
$password_file = '.password';

// 默认访问密码
$default_password = 'admin123';

// 获取或设置密码
function getPassword() {
    global $password_file, $default_password;
    
    if (file_exists($password_file)) {
        return trim(file_get_contents($password_file));
    }
    
    // 如果密码文件不存在，创建默认密码
    file_put_contents($password_file, $default_password);
    return $default_password;
}

// 验证会话
function checkSession() {
    return isset($_SESSION['tvbox_auth']) && $_SESSION['tvbox_auth'] === true;
}

// 启动会话
session_start();

// 处理API请求 - API不需要密码验证
if (isset($_GET['api']) && $_GET['api'] === 'json') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(getConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action']) && $_POST['login_action'] === 'login') {
    $input_password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if ($input_password === getPassword()) {
        $_SESSION['tvbox_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = '密码错误，请重试';
    }
}

// 处理登出请求
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 处理修改密码请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action']) && $_POST['login_action'] === 'change_password') {
    if (checkSession()) {
        $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        
        if (!empty($new_password)) {
            file_put_contents($password_file, $new_password);
            $password_message = '密码已成功修改';
        } else {
            $password_error = '新密码不能为空';
        }
    }
}

// 如果未登录，显示登录页面
if (!checkSession()) {
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TVBox多仓管理系统 - 登录</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        }
        .api-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .api-link a {
            color: #3498db;
            text-decoration: none;
        }
        .api-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>TVBox多仓管理系统</h1>
        <?php if (isset($login_error)): ?>
        <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="login_action" value="login">
            <div class="form-group">
                <label for="password">请输入管理密码：</label>
                <input type="password" id="password" name="password" required autofocus>
            </div>
            <button type="submit">登录</button>
        </form>
        <div class="api-link">
            <p>访问API: <a href="?api=json" target="_blank"><?php echo getServerUrl(); ?>/?api=json</a></p>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}

// 获取服务器URL
function getServerUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script_name);
    $path = $path === '/' ? '' : $path;
    
    return "$protocol://$host$path";
}

// 获取当前配置
function getConfig() {
    global $config_file;
    
    if (file_exists($config_file)) {
        $content = file_get_contents($config_file);
        $config = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $config;
        }
    }
    
    // 如果配置文件不存在或无效，尝试从URL参数初始化
    if (isset($_GET['init']) && !empty($_GET['init'])) {
        $init_data = urldecode($_GET['init']);
        $init_config = json_decode($init_data, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            file_put_contents($config_file, json_encode($init_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $init_config;
        }
    }
    
    // 返回默认配置
    return ['urls' => []];
}

// 扫描tvboxqq目录，检查现有文件
function scanTvboxqqDirectory($all_json = false) {
    $base_dir = './tvboxqq/';
    $results = [];
    
    // 如果tvboxqq目录不存在，创建它
    if (!file_exists($base_dir)) {
        mkdir($base_dir, 0755, true);
        return $results;
    }
    
    // 获取服务器URL前缀
    $server_url = getServerUrl();
    
    // 获取所有子目录
    $subdirs = array_filter(glob($base_dir . '*'), 'is_dir');
    
    foreach ($subdirs as $subdir) {
        $name = basename($subdir);
        
        if ($all_json) {
            // 查找所有JSON文件
            $json_files = glob($subdir . '/*.json');
            foreach ($json_files as $json_file) {
                $json_name = basename($json_file, '.json');
                $display_name = ($json_name === 'api') ? $name : $name . '-' . $json_name;
                
                $results[] = [
                    'name' => $display_name,
                    'url' => $server_url . '/tvboxqq/' . $name . '/' . basename($json_file)
                ];
            }
        } else {
            // 只查找api.json文件
            $api_file = $subdir . '/api.json';
            if (file_exists($api_file)) {
                $results[] = [
                    'name' => $name,
                    'url' => $server_url . '/tvboxqq/' . $name . '/api.json'
                ];
            }
        }
    }
    
    return $results;
}

// 添加API端点
if (isset($_GET['api']) && $_GET['api'] === 'json') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(getConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save':
                // 保存整个JSON配置
                if (isset($_POST['json_config'])) {
                    $config = json_decode($_POST['json_config'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $message = '配置已成功保存';
                    } else {
                        $error = '无效的JSON格式';
                    }
                }
                break;
                
            case 'add':
                // 添加新的URL
                if (isset($_POST['name']) && isset($_POST['url'])) {
                    $name = trim($_POST['name']);
                    $url = trim($_POST['url']);
                    
                    if (!empty($name) && !empty($url)) {
                        $config = getConfig();
                        $config['urls'][] = [
                            'name' => $name,
                            'url' => $url
                        ];
                        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $message = '新仓库已添加';
                    } else {
                        $error = '名称和URL不能为空';
                    }
                }
                break;
                
            case 'delete':
                // 删除URL
                if (isset($_POST['index'])) {
                    $index = (int)$_POST['index'];
                    $config = getConfig();
                    
                    if (isset($config['urls'][$index])) {
                        array_splice($config['urls'], $index, 1);
                        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $message = '仓库已删除';
                    }
                }
                break;
                
            case 'update':
                // 更新单个URL
                if (isset($_POST['index']) && isset($_POST['name']) && isset($_POST['url'])) {
                    $index = (int)$_POST['index'];
                    $name = trim($_POST['name']);
                    $url = trim($_POST['url']);
                    
                    if (!empty($name) && !empty($url)) {
                        $config = getConfig();
                        
                        if (isset($config['urls'][$index])) {
                            $config['urls'][$index] = [
                                'name' => $name,
                                'url' => $url
                            ];
                            file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                            $message = '仓库已更新';
                        }
                    } else {
                        $error = '名称和URL不能为空';
                    }
                }
                break;
                
            case 'move_up':
                // 上移仓库
                if (isset($_POST['index'])) {
                    $index = (int)$_POST['index'];
                    $config = getConfig();
                    
                    if ($index > 0 && $index < count($config['urls'])) {
                        $temp = $config['urls'][$index - 1];
                        $config['urls'][$index - 1] = $config['urls'][$index];
                        $config['urls'][$index] = $temp;
                        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $message = '仓库已上移';
                    }
                }
                break;
                
            case 'move_down':
                // 下移仓库
                if (isset($_POST['index'])) {
                    $index = (int)$_POST['index'];
                    $config = getConfig();
                    
                    if ($index >= 0 && $index < count($config['urls']) - 1) {
                        $temp = $config['urls'][$index + 1];
                        $config['urls'][$index + 1] = $config['urls'][$index];
                        $config['urls'][$index] = $temp;
                        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $message = '仓库已下移';
                    }
                }
                break;
                
            case 'move_to_top':
                // 移至顶部
                if (isset($_POST['index'])) {
                    $index = (int)$_POST['index'];
                    $config = getConfig();
                    
                    if ($index > 0 && $index < count($config['urls'])) {
                        $item = $config['urls'][$index];
                        array_splice($config['urls'], $index, 1);
                        array_unshift($config['urls'], $item);
                        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $message = '仓库已移至顶部';
                    }
                }
                break;
                
            case 'move_to_bottom':
                // 移至底部
                if (isset($_POST['index'])) {
                    $index = (int)$_POST['index'];
                    $config = getConfig();
                    
                    if ($index >= 0 && $index < count($config['urls']) - 1) {
                        $item = $config['urls'][$index];
                        array_splice($config['urls'], $index, 1);
                        $config['urls'][] = $item;
                        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $message = '仓库已移至底部';
                    }
                }
                break;
                
            case 'bulk_add':
                // 批量添加目录下的所有json文件
                $all_json = isset($_POST['scan_all_json']) && $_POST['scan_all_json'] == 1;
                $directory_items = scanTvboxqqDirectory($all_json);
                
                if (!empty($directory_items)) {
                    $config = getConfig();
                    
                    // 合并现有配置和新扫描结果，避免重复
                    $existing_urls = [];
                    foreach ($config['urls'] as $item) {
                        $existing_urls[$item['url']] = $item;
                    }
                    
                    $added_count = 0;
                    foreach ($directory_items as $item) {
                        if (!isset($existing_urls[$item['url']])) {
                            $config['urls'][] = $item;
                            $added_count++;
                        }
                    }
                    
                    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $message = "已批量添加{$added_count}个新仓库，当前共" . count($config['urls']) . '个仓库';
                } else {
                    $error = '未找到任何有效的JSON文件';
                }
                break;
                
            case 'regenerate':
                // 从目录重新生成配置
                $all_json = isset($_POST['scan_all_json']) && $_POST['scan_all_json'] == 1;
                $directory_items = scanTvboxqqDirectory($all_json);
                
                if (!empty($directory_items)) {
                    $config = getConfig();
                    $config['urls'] = $directory_items;
                    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $message = '已从目录重新生成仓库列表，共' . count($directory_items) . '个仓库';
                } else {
                    $error = '未找到任何有效的JSON文件';
                }
                break;
                
            case 'upload':
                // 处理文件上传
                if (isset($_FILES['api_file']) && $_FILES['api_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = './tvboxqq/';
                    
                    // 确保tvboxqq目录存在
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $target_subdir = '';
                    
                    if (isset($_POST['subdir']) && !empty($_POST['subdir'])) {
                        $target_subdir = trim($_POST['subdir']) . '/';
                        
                        // 创建子目录(如果不存在)
                        if (!file_exists($upload_dir . $target_subdir)) {
                            mkdir($upload_dir . $target_subdir, 0755, true);
                        }
                    }
                    
                    $filename = basename($_FILES['api_file']['name']);
                    $target_path = $upload_dir . $target_subdir . $filename;
                    
                    if (move_uploaded_file($_FILES['api_file']['tmp_name'], $target_path)) {
                        $message = '文件上传成功: ' . $target_path;
                        
                        // 如果上传的是JSON文件并且指定了子目录，自动添加到配置
                        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'json' && !empty($target_subdir)) {
                            $subdir_name = rtrim($target_subdir, '/');
                            $json_name = pathinfo($filename, PATHINFO_FILENAME);
                            $display_name = ($json_name === 'api') ? $subdir_name : $subdir_name . '-' . $json_name;
                            
                            $config = getConfig();
                            
                            // 检查是否已存在相同URL的仓库
                            $file_url = getServerUrl() . '/tvboxqq/' . $target_subdir . $filename;
                            $exists = false;
                            foreach ($config['urls'] as $item) {
                                if ($item['url'] === $file_url) {
                                    $exists = true;
                                    break;
                                }
                            }
                            
                            if (!$exists) {
                                $config['urls'][] = [
                                    'name' => $display_name,
                                    'url' => $file_url
                                ];
                                file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                $message .= '，并已添加到仓库列表';
                            }
                        }
                    } else {
                        $error = '文件上传失败';
                    }
                }
                break;
        }
    }
}

// 首次加载时，扫描目录并更新配置
$config = getConfig();
$directory_items = scanTvboxqqDirectory(false); // 默认只扫描api.json

// 如果配置为空但目录扫描有结果，使用扫描结果
if ((empty($config['urls']) || count($config['urls']) === 0) && !empty($directory_items)) {
    $config['urls'] = $directory_items;
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TVBox多仓管理系统</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #2c3e50;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .section {
            flex: 1;
            min-width: 300px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 300px;
            font-family: monospace;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            word-break: break-all;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .action-buttons button, .action-buttons form {
            margin: 2px;
        }
        .action-buttons button {
            padding: 5px 10px;
            font-size: 12px;
        }
        .delete-btn {
            background: #e74c3c;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .edit-btn {
            background: #f39c12;
        }
        .edit-btn:hover {
            background: #d35400;
        }
        .up-btn {
            background: #27ae60;
        }
        .up-btn:hover {
            background: #219955;
        }
        .down-btn {
            background: #16a085;
        }
        .down-btn:hover {
            background: #138a72;
        }
        .top-btn {
            background: #8e44ad;
        }
        .top-btn:hover {
            background: #703688;
        }
        .bottom-btn {
            background: #2c3e50;
        }
        .bottom-btn:hover {
            background: #1a252f;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .hidden {
            display: none;
        }
        #edit-form {
            margin-top: 20px;
            padding: 15px;
            background-color: #fffbea;
            border: 1px solid #ffe58f;
            border-radius: 4px;
        }
        .flex-col {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
        .col-seq {
            width: 50px;
        }
        .col-name {
            width: 120px;
        }
        .col-url {
            width: auto;
        }
        .col-actions {
            width: 210px;
        }
        @media (max-width: 768px) {
            .col-url {
                display: none;
            }
            .col-actions {
                width: 120px;
            }
        }
        .move-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
        }
        .move-buttons form {
            margin: 0;
        }
        .move-buttons button {
            padding: 3px 6px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <h1>TVBox多仓管理系统</h1>
    
    <?php if (isset($message)): ?>
    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="container">
        <div class="section">
            <h2>仓库列表</h2>
            
            <table>
                <thead>
                    <tr>
                        <th class="col-seq">序号</th>
                        <th class="col-name">名称</th>
                        <th class="col-url">URL</th>
                        <th class="col-actions">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($config['urls'] as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['url']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="edit-btn" onclick="editItem(<?php echo $index; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>', '<?php echo htmlspecialchars(addslashes($item['url'])); ?>')">编辑</button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('确定要删除这个仓库吗？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="delete-btn">删除</button>
                                </form>
                            </div>
                            <div class="move-buttons">
                                <?php if ($index > 0): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="move_up">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="up-btn" title="上移">↑</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="move_to_top">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="top-btn" title="移至顶部">⇑</button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($index < count($config['urls']) - 1): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="move_down">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="down-btn" title="下移">↓</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="move_to_bottom">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="bottom-btn" title="移至底部">⇓</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($config['urls'])): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">没有仓库数据</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div id="edit-form" class="hidden">
                <h3>编辑仓库</h3>
                <div class="message" id="edit-message" style="display: none;"></div>
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="index" id="edit-index">
                    
                    <div class="form-group">
                        <label for="edit-name">名称:</label>
                        <input type="text" id="edit-name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-url">URL:</label>
                        <input type="text" id="edit-url" name="url" required>
                    </div>
                    
                    <button type="submit">保存修改</button>
                    <button type="button" onclick="cancelEdit()">取消</button>
                </form>
            </div>
            
            <h3>添加新仓库</h3>
            <form method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="new-name">名称:</label>
                    <input type="text" id="new-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="new-url">URL:</label>
                    <input type="text" id="new-url" name="url" required>
                </div>
                
                <button type="submit">添加仓库</button>
            </form>
        </div>
        
        <div class="section">
            <h2>上传API文件</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                
                <div class="form-group">
                    <label for="subdir">子目录名称:</label>
                    <input type="text" id="subdir" name="subdir" placeholder="例如: 饭太硬">
                </div>
                
                <div class="form-group">
                    <label for="api_file">选择文件:</label>
                    <input type="file" id="api_file" name="api_file" required>
                </div>
                
                <button type="submit">上传文件</button>
            </form>
            
            <div class="form-group" style="margin-top: 20px;">
                <p><strong>提示:</strong></p>
                <ul>
                    <li>上传的文件将保存在 tvboxqq/ 目录下</li>
                    <li>如果指定了子目录，文件将保存在 tvboxqq/子目录/ 下</li>
                    <li>如果上传的是JSON文件并指定了子目录，系统会自动添加到仓库列表</li>
                </ul>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <h3>目录管理</h3>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="scan_all_json" name="scan_all_json" value="1">
                    <label for="scan_all_json">扫描所有JSON文件 (不仅限于api.json)</label>
                </div>
                
                <!-- 添加重新生成按钮 -->
                <div style="margin-top: 10px;">
                    <form method="post" id="regenerate-form">
                        <input type="hidden" name="action" value="regenerate">
                        <input type="hidden" name="scan_all_json" id="regenerate_scan_all" value="0">
                        <button type="submit" style="background-color: #28a745;">从目录重新生成仓库列表</button>
                    </form>
                </div>
                
                <!-- 添加批量添加按钮 -->
                <div style="margin-top: 10px;">
                    <form method="post" id="bulk-add-form">
                        <input type="hidden" name="action" value="bulk_add">
                        <input type="hidden" name="scan_all_json" id="bulk_add_scan_all" value="0">
                        <button type="submit" style="background-color: #17a2b8;">批量添加未收录的仓库</button>
                    </form>
                </div>
                
                <h3>目录状态</h3>
                <pre style="background: #f0f0f0; padding: 10px; border-radius: 4px; overflow: auto; font-size: 12px;"><?php
                    // 显示tvboxqq目录结构
                    echo "tvboxqq目录";
                    if (!file_exists('./tvboxqq/')) {
                        echo " (不存在)\n";
                    } else {
                        echo " (存在)\n";
                        $subdirs = glob('./tvboxqq/*', GLOB_ONLYDIR);
                        if (empty($subdirs)) {
                            echo "└─ 没有子目录\n";
                        } else {
                            foreach ($subdirs as $index => $dir) {
                                $is_last = ($index == count($subdirs) - 1);
                                $prefix = $is_last ? "└─ " : "├─ ";
                                $name = basename($dir);
                                echo "{$prefix}{$name}/\n";
                                
                                $files = glob($dir . "/*.json");
                                if (!empty($files)) {
                                    $file_prefix = $is_last ? "    " : "│   ";
                                    foreach ($files as $file_index => $file) {
                                        $is_last_file = ($file_index == count($files) - 1);
                                        $file_item_prefix = $is_last_file ? "└─ " : "├─ ";
                                        $file_name = basename($file);
                                        echo "{$file_prefix}{$file_item_prefix}{$file_name} ★\n";
                                    }
                                }
                                
                                $other_files = array_filter(glob($dir . "/*"), function($f) {
                                    return !is_dir($f) && pathinfo($f, PATHINFO_EXTENSION) !== 'json';
                                });
                                
                                if (!empty($other_files)) {
                                    $other_file_count = count($other_files);
                                    $file_prefix = $is_last ? "    " : "│   ";
                                    if (empty($files)) {
                                        echo "{$file_prefix}└─ ... 及其他 {$other_file_count} 个文件\n";
                                    } else {
                                        echo "{$file_prefix}└─ ... 及其他 {$other_file_count} 个文件\n";
                                    }
                                }
                            }
                        }
                    }
                ?>
