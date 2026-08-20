<?php
// Script independente do plugin iFlux para receber requisições do App Mobile

// Captura IMEDIATA dos dados antes do includes.php
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$requestIp = $_SERVER['REMOTE_ADDR'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$rawBody = file_get_contents("php://input");

// Responder imediatamente ao pre-flight do CORS
if ($requestMethod === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, App-Token");
    http_response_code(200);
    exit;
}

define('GLPI_ROOT', '../../..');
define('DO_NOT_CHECK_HTTP_REFERER', 1);
define('GLPI_API', true);
define('GLPI_AJAX', true);
$AJAX_INCLUDE = 1;
include GLPI_ROOT . '/inc/includes.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, App-Token");

global $DB;

// Função de resposta com log automático
function jsonResponse($statusCode, $data) {
    global $DB, $requestMethod, $requestIp, $requestUri, $rawBody;
    
    $responseJson = json_encode($data);
    http_response_code($statusCode);
    
    $DB->insert('glpi_plugin_iflux_logs_api', [
        'date_creation' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
        'ip_address'    => substr($requestIp, 0, 100),
        'method'        => substr($requestMethod, 0, 10),
        'endpoint'      => substr($requestUri, 0, 255),
        'payload'       => $rawBody,
        'status_code'   => $statusCode,
        'response'      => $responseJson
    ]);

    echo $responseJson;
    exit;
}

// Analisa o payload JSON
$payloadData = json_decode($rawBody, true) ?: [];

// Pega os headers de forma segura (evita Fatal Error no getallheaders em Nginx/PHP-FPM)
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

// 1. Validação do App-Token
if (empty($appToken)) {
    $appToken = $payloadData['app_token'] ?? ''; // Tenta pegar do body
}

$resultConfig = $DB->request(['FROM' => 'glpi_plugin_iflux_configs', 'WHERE' => ['id' => 1]]);
$configData = $resultConfig->current();

if (!$configData || trim($configData['app_token']) !== trim($appToken)) {
    jsonResponse(403, ["error" => "Forbidden", "message" => "App-Token inválido. Verifique o QR Code.", "received" => $appToken]);
}

// 2. Extrai os dados
$userId = (int) ($payloadData['users_id'] ?? 0);
$pushToken = trim($payloadData['pushtoken'] ?? '');

if ($userId <= 0 || empty($pushToken)) {
    jsonResponse(400, ["error" => "Bad Request", "message" => "users_id e pushtoken são obrigatórios.", "body" => $rawBody, "method" => $requestMethod]);
}

// 3. Salva ou atualiza o token
$existing = $DB->request(['FROM' => 'glpi_plugin_iflux_pushtokens', 'WHERE' => ['users_id' => $userId]])->current();

if ($existing) {
    $success = $DB->update('glpi_plugin_iflux_pushtokens', ['pushtoken' => $pushToken], ['users_id' => $userId]);
    if ($success) {
        jsonResponse(200, ["status" => "OK", "message" => "Push token atualizado com sucesso."]);
    } else {
        jsonResponse(500, ["error" => "Internal Error", "message" => "Falha ao atualizar token."]);
    }
} else {
    $success = $DB->insert('glpi_plugin_iflux_pushtokens', ['users_id' => $userId, 'pushtoken' => $pushToken]);
    if ($success) {
        jsonResponse(201, ["status" => "OK", "message" => "Push token registrado com sucesso."]);
    } else {
        jsonResponse(500, ["error" => "Internal Error", "message" => "Falha ao inserir token."]);
    }
}
