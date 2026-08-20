<?php
// Endpoint dedicado para o App iFlux sincronizar o Push Token dinamicamente via Bearer Token
include("../../../inc/includes.php");

// Cabeçalhos CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);
$pushToken = $data['pushtoken'] ?? '';

if (empty($authHeader) || empty($pushToken)) {
    http_response_code(400);
    echo json_encode(['error' => 'Token OAuth2 ou Push Token não fornecido']);
    exit;
}

// 1. Validar dinamicamente o Bearer Token consultando a própria API V2 do GLPI
global $CFG_GLPI;
$apiUrl = $CFG_GLPI['url_base'] . '/api.php/session';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Evita erro de certificado SSL se for localhost/http
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $authHeader,
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($response)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token OAuth2 inválido ou expirado', 'details' => $response]);
    exit;
}

$sessionData = json_decode($response, true);
$userId = (int)($sessionData['session']['glpiID'] ?? 0);

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Não foi possível determinar o ID do usuário na sessão']);
    exit;
}

// 2. Burlar temporariamente a trava de sessão do GLPI para o CommonDBTM conseguir salvar
$_SESSION['glpiID'] = $userId;

$tokenItem = new PluginIfluxPushtoken();
$found = $tokenItem->find(['users_id' => $userId]);

if (count($found) > 0) {
    $existing = reset($found);
    $tokenItem->update([
        'id'        => $existing['id'],
        'pushtoken' => $pushToken
    ]);
} else {
    $tokenItem->add([
        'users_id'  => $userId,
        'pushtoken' => $pushToken
    ]);
}

// Limpa a sessão fake
unset($_SESSION['glpiID']);

echo json_encode([
    'success' => true, 
    'message' => 'Push Token salvo com sucesso via Endpoint Dinâmico',
    'users_id' => $userId
]);
