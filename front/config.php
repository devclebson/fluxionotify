<?php
// Tenta localizar o diretório raiz do GLPI de forma absoluta
$glpi_root = realpath(__DIR__ . '/../../../');

if (file_exists($glpi_root . "/inc/includes.php")) {
    include_once($glpi_root . "/inc/includes.php");
} else {
    die("Erro crítico: Não foi possível localizar o arquivo includes.php em: " . $glpi_root);
}

Session::checkRight("plugin_iflux", READ);

$config = new PluginIfluxConfig();
global $DB;

// Carregar configuração base
$config->getFromDB(1);

// Ação 1: Salvar Configurações
if (isset($_POST["update"])) {
   Session::checkRight("plugin_iflux", UPDATE);
   
   $DB->update('glpi_plugin_iflux_configs', [
      'api_url'       => $_POST['api_url'],
      'app_token'     => $_POST['app_token'],
      'client_id'     => $_POST['client_id'] ?? '',
      'client_secret' => $_POST['client_secret'] ?? ''
   ], ['id' => 1]);
   
   Session::addMessageAfterRedirect("Configurações do iFlux salvas!");
   Html::redirect("config.php?tab=PluginIfluxConfig$2");
}

// Ação 2: Excluir/Revogar Token de Push
if (isset($_GET['action']) && $_GET['action'] === 'delete_token') {
   Session::checkRight("plugin_iflux", DELETE);
   
   $tokenId = (int)$_GET['id'];
   $DB->delete('glpi_plugin_iflux_pushtokens', ['id' => $tokenId]);
   
   Session::addMessageAfterRedirect("Acesso do dispositivo revogado com sucesso!");
   Html::redirect("config.php?tab=PluginIfluxConfig$3");
}

// Ação 3: Limpar Logs de Envio
if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
   Session::checkRight("plugin_iflux", PURGE);
   
   $DB->truncate('glpi_plugin_iflux_logs');
   
   Session::addMessageAfterRedirect("Histórico de logs de notificações apagado!");
   Html::redirect("config.php?tab=PluginIfluxConfig$4");
}

// Ação 4: Atualizar Direitos do Perfil (recebido da aba iFlux em perfis)
if (isset($_POST['update_iflux_profile'])) {
   Session::checkRight("plugin_iflux", UPDATE);
   
   $profiles_id = (int)$_POST['profiles_id'];
   
   $new_rights = 0;
   if (isset($_POST['iflux_read']))   $new_rights |= READ;
   if (isset($_POST['iflux_update'])) $new_rights |= UPDATE;
   if (isset($_POST['iflux_create'])) $new_rights |= CREATE;
   if (isset($_POST['iflux_delete'])) $new_rights |= DELETE;
   if (isset($_POST['iflux_purge']))  $new_rights |= PURGE;

   $result = $DB->request([
      'FROM'  => 'glpi_profilerights',
      'WHERE' => [
         'profiles_id' => $profiles_id,
         'name'        => 'plugin_iflux'
      ]
   ]);

   if ($row = $result->current()) {
      $DB->update('glpi_profilerights', ['rights' => $new_rights], ['id' => $row['id']]);
   } else {
      $DB->insert('glpi_profilerights', [
         'profiles_id' => $profiles_id,
         'name'        => 'plugin_iflux',
         'rights'      => $new_rights
      ]);
   }

   Session::addMessageAfterRedirect("Direitos do iFlux atualizados com sucesso!");
   Html::redirect($_SERVER['HTTP_REFERER'] ?? "../../../front/profile.form.php?id=$profiles_id");
}

Html::header('iFlux Config', $_SERVER['PHP_SELF'], "config", "PluginIfluxConfig");
$config->display(['id' => 1]);
Html::footer();
