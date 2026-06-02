<?php
define('PLUGIN_IFLUX_VERSION', '1.0.1');

function plugin_init_iflux() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['iflux'] = true;
   $PLUGIN_HOOKS['config_page']['iflux'] = 'front/config.php';

   Plugin::registerClass('PluginIfluxConfig');
   Plugin::registerClass('PluginIfluxPushtoken');
   Plugin::registerClass('PluginIfluxNotification'); // Registra a classe de push

   if (class_exists('PluginIfluxConfig')) {
      $PLUGIN_HOOKS['menu_toadd']['iflux'] = ['config' => 'PluginIfluxConfig'];
   }

   // Ativa o envio de push ao criar chamados
   $PLUGIN_HOOKS['item_add']['iflux'] = [
      'Ticket' => 'plugin_iflux_item_add_ticket'
   ];
}

function plugin_version_iflux() {
   return [
      'name'           => 'iFlux App Sync',
      'version'        => PLUGIN_IFLUX_VERSION,
      'author'         => 'iFlux',
      'license'        => 'GPLv2+',
      'requirements'   => ['glpi' => ['min' => '11.0.0']]
   ];
}