<?php
define('PLUGIN_FLUXIONOTIFY_VERSION', '1.0.1');

function plugin_init_fluxionotify() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['fluxionotify'] = true;
   $PLUGIN_HOOKS['config_page']['fluxionotify'] = 'front/config.php';

   Plugin::registerClass('PluginFluxionotifyConfig');
   Plugin::registerClass('PluginFluxionotifyPushtoken');
   Plugin::registerClass('PluginFluxionotifyNotification'); // Registra a classe de push
   Plugin::registerClass('PluginFluxionotifyProfile', ['addtabon' => ['Profile']]);

   if (class_exists('PluginFluxionotifyConfig')) {
      $PLUGIN_HOOKS['menu_toadd']['fluxionotify'] = ['config' => 'PluginFluxionotifyConfig'];
   }

    // Ativa o envio de push ao criar chamados, acompanhamentos e tarefas
    $PLUGIN_HOOKS['item_add']['fluxionotify'] = [
       'Ticket'       => 'plugin_fluxionotify_item_add_ticket',
       'ITILFollowup' => 'plugin_fluxionotify_item_add_followup',
       'TicketTask'   => 'plugin_fluxionotify_item_add_task'
    ];

    // Ativa o envio de push ao atualizar chamados
    $PLUGIN_HOOKS['item_update']['fluxionotify'] = [
       'Ticket'       => 'plugin_fluxionotify_item_update_ticket'
    ];
}

function plugin_version_fluxionotify() {
   return [
      'name'           => 'FluxIO Notify',
      'version'        => PLUGIN_FLUXIONOTIFY_VERSION,
      'author'         => 'FluxIO',
      'license'        => 'GPLv3',
      'requirements'   => ['glpi' => ['min' => '10.0.0']]
   ];
}