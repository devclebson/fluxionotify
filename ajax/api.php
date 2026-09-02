<?php
// API Independente para FluxIO Notify - GLPI 11 (Bypass do CSRF/Router)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, App-Token");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rawBody = file_get_contents("php://input");
$payloadData = json_decode($rawBody, true) ?: [];

// Extrair headers seguros (NGINX/FPM support)
$appToken = '';
if (isset($_SERVER['HTTP_APP_TOKEN'])) {
    $appToken = trim($_SERVER['HTTP_APP_TOKEN']);
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_' && strtolower($name) == 'http_app_token') {
            $appToken = trim($value);
        }
    }
}
if (empty($appToken)) {
    $appToken = $payloadData['app_token'] ?? '';
}

$userId = (int) ($payloadData['users_id'] ?? 0);
$pushToken = trim($payloadData['pushtoken'] ?? '');

if ($userId <= 0 || empty($pushToken)) {
    http_response_code(400);
    echo json_encode(["error" => "Bad Request", "message" => "users_id e pushtoken sÃ£o obrigatÃ³rios."]);
    exit;
}

// === BYPASS TOTAL DO GLPI (LENDO O CONFIG_DB DIRETAMENTE) ===
$configPath = __DIR__ . '/../../../../config/config_db.php';
if (!file_exists($configPath)) {
    $configPath = '/etc/glpi/config_db.php';
}
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Error", "message" => "config_db.php nÃ£o encontrado."]);
    exit;
}

// Ler o conteÃºdo do config_db.php
$configContent = file_get_contents($configPath);
$dbhost = $dbuser = $dbpassword = $dbdefault = '';

if (preg_match("/public\s+\\\$dbhost\s*=\s*['\"]([^'\"]+)['\"]/i", $configContent, $m)) $dbhost = $m[1];
if (preg_match("/public\s+\\\$dbuser\s*=\s*['\"]([^'\"]+)['\"]/i", $configContent, $m)) $dbuser = $m[1];
if (preg_match("/public\s+\\\$dbpassword\s*=\s*['\"]([^'\"]+)['\"]/i", $configContent, $m)) $dbpassword = $m[1];
if (preg_match("/public\s+\\\$dbdefault\s*=\s*['\"]([^'\"]+)['\"]/i", $configContent, $m)) $dbdefault = $m[1];

if (empty($dbhost) || empty($dbuser) || empty($dbdefault)) {
    http_response_code(500);
    echo json_encode(["error" => "Internal Error", "message" => "Falha ao ler credenciais do BD."]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbdefault;charset=utf8mb4", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ValidaÃ§Ã£o do App-Token
    $stmt = $pdo->prepare("SELECT app_token FROM glpi_plugin_fluxionotify_configs WHERE id = 1");
    $stmt->execute();
    $configData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$configData || trim($configData['app_token']) !== trim($appToken)) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden", "message" => "App-Token invÃ¡lido."]);
        exit;
    }

    // Atualizar Token
    $stmt = $pdo->prepare("SELECT id FROM glpi_plugin_fluxionotify_pushtokens WHERE users_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE glpi_plugin_fluxionotify_pushtokens SET pushtoken = ? WHERE users_id = ?");
        $stmt->execute([$pushToken, $userId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO glpi_plugin_fluxionotify_pushtokens (users_id, pushtoken) VALUES (?, ?)");
        $stmt->execute([$userId, $pushToken]);
    }

    echo json_encode(["status" => "OK", "message" => "Push token salvo."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database Error", "message" => "Falha na transaÃ§Ã£o DB."]);
}

