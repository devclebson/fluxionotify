<?php
// Tenta localizar o diretório raiz do GLPI de forma absoluta
$glpi_root = realpath(__DIR__ . '/../../../');

if (file_exists($glpi_root . "/inc/includes.php")) {
    include_once($glpi_root . "/inc/includes.php");
} else {
    die("Erro crítico: Não foi possível localizar o arquivo includes.php em: " . $glpi_root);
}

Session::checkRight("config", UPDATE);

$config = new PluginIfluxConfig();
global $DB;

// Ação 1: Salvar Configurações
if (isset($_POST["update"])) {
   $DB->update('glpi_plugin_iflux_configs', [
      'api_url'       => $_POST['api_url'],
      'app_token'     => $_POST['app_token'],
      'client_id'     => $_POST['client_id'] ?? '',
      'client_secret' => $_POST['client_secret'] ?? ''
   ], ['id' => 1]);
   
   Session::addMessageAfterRedirect("Configurações do iFlux salvas!");
   Html::redirect("config.php?tab=config");
}

// Ação 2: Excluir/Revogar Token de Push
if (isset($_GET['action']) && $_GET['action'] === 'delete_token') {
   $tokenId = (int)$_GET['id'];
   $DB->delete('glpi_plugin_iflux_pushtokens', ['id' => $tokenId]);
   
   Session::addMessageAfterRedirect("Acesso do dispositivo revogado com sucesso!");
   Html::redirect("config.php?tab=tokens");
}

// Ação 3: Limpar Logs de Envio
if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
   $DB->truncate('glpi_plugin_iflux_logs');
   
   Session::addMessageAfterRedirect("Histórico de logs de notificações apagado!");
   Html::redirect("config.php?tab=logs");
}

$tab = $_GET['tab'] ?? 'config';

Html::header('iFlux Config', $_SERVER['PHP_SELF'], "config", "PluginIfluxConfig");
$config->showFormConfig($tab);
Html::footer();
