<?php

// Executado ao clicar em "Instalar" no painel do GLPI
function plugin_iflux_install() {
   global $DB;

   $migration = new Migration(PLUGIN_IFLUX_VERSION);

   // Tabela para armazenar os tokens de push do aplicativo de cada usuário
   if (!$DB->tableExists('glpi_plugin_iflux_pushtokens')) {
      $query = "CREATE TABLE `glpi_plugin_iflux_pushtokens` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `users_id` int(11) NOT NULL,
                  `pushtoken` varchar(255) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `users_id` (`users_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      $migration->addPostQuery($query);
   }

   // Tabela para armazenar as configurações (Base URL, App Token, OAuth2) que irão pro QR Code
   if (!$DB->tableExists('glpi_plugin_iflux_configs')) {
      $query = "CREATE TABLE `glpi_plugin_iflux_configs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `app_token` varchar(255) DEFAULT NULL,
                  `api_url` varchar(255) DEFAULT NULL,
                  `client_id` varchar(255) DEFAULT NULL,
                  `client_secret` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      $migration->addPostQuery($query);

      // Inserir registro base para a tela de configurações
      $migration->addPostQuery("INSERT IGNORE INTO `glpi_plugin_iflux_configs` (`id`, `app_token`, `api_url`, `client_id`, `client_secret`) VALUES (1, '', '', '', '')");
   } else {
      // Adicionar colunas novas caso o plugin já estivesse instalado sem elas
      if (!$DB->fieldExists('glpi_plugin_iflux_configs', 'client_id')) {
         $migration->addPostQuery("ALTER TABLE `glpi_plugin_iflux_configs` ADD `client_id` varchar(255) DEFAULT NULL, ADD `client_secret` varchar(255) DEFAULT NULL");
      }
   }

   // Tabela para armazenar os logs de envio de notificações
   if (!$DB->tableExists('glpi_plugin_iflux_logs')) {
      $query = "CREATE TABLE `glpi_plugin_iflux_logs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `date_creation` datetime DEFAULT NULL,
                  `tickets_id` int(11) DEFAULT NULL,
                  `users_id` int(11) DEFAULT NULL,
                  `title` varchar(255) DEFAULT NULL,
                  `message` text DEFAULT NULL,
                  `status` varchar(50) DEFAULT NULL,
                  `response` text DEFAULT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      $migration->addPostQuery($query);
   }

   // No GLPI 11, executar queries diretamente é bloqueado por segurança.
   // A classe Migration deve assumir a execução.
   $migration->executeMigration();

   // Registrar o direito 'plugin_iflux' na tabela glpi_profilerights
   if (class_exists('ProfileRight')) {
      ProfileRight::addProfileRights(['plugin_iflux']);

      // Buscar o perfil super-admin (por ID 4 ou variações de nome) e conceder permissões completas (31)
      $profileResult = $DB->request([
         'FROM'  => 'glpi_profiles',
         'WHERE' => [
            'OR' => [
               'name' => ['super-admin', 'Super-Admin'],
               'id'   => 4
            ]
         ]
      ]);
      if ($profileRow = $profileResult->current()) {
         $superadminId = $profileRow['id'];
         
         // Verificar se já existe registro em glpi_profilerights
         $rightResult = $DB->request([
            'FROM'  => 'glpi_profilerights',
            'WHERE' => [
               'profiles_id' => $superadminId,
               'name'        => 'plugin_iflux'
            ]
         ]);
         
         if (count($rightResult) === 0) {
            $DB->insert('glpi_profilerights', [
               'profiles_id' => $superadminId,
               'name'        => 'plugin_iflux',
               'rights'      => 31 // LER (1) | ATUALIZAR (2) | CRIAR (4) | EXCLUIR (8) | APAGAR (16)
            ]);
         } else {
            $DB->update('glpi_profilerights', ['rights' => 31], [
               'profiles_id' => $superadminId,
               'name'        => 'plugin_iflux'
            ]);
         }
      }
   }

   return true;
}

// Executado ao clicar em "Desinstalar" no GLPI
function plugin_iflux_uninstall() {
   global $DB;

   $tables = [
      'glpi_plugin_iflux_pushtokens',
      'glpi_plugin_iflux_configs',
      'glpi_plugin_iflux_logs'
   ];

   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->dropTable($table); // dropTable() é seguro no GLPI 11
      }
   }

   // Remover direitos registrados
   if (class_exists('ProfileRight')) {
      ProfileRight::deleteProfileRights(['plugin_iflux']);
   }

   return true;
}

/**
 * Função Hook engatilhada sempre que um Chamado (Ticket) for criado no GLPI.
 */
function plugin_iflux_item_add_ticket(Ticket $ticket) {
   include_once(GLPI_ROOT . '/plugins/iflux/inc/notification.class.php');
   PluginIfluxNotification::sendForTicket($ticket);
}

/**
 * Função Hook engatilhada sempre que um Chamado (Ticket) for atualizado no GLPI.
 */
function plugin_iflux_item_update_ticket(Ticket $ticket) {
   include_once(GLPI_ROOT . '/plugins/iflux/inc/notification.class.php');
   PluginIfluxNotification::sendForTicketUpdate($ticket);
}

/**
 * Função Hook engatilhada sempre que um Acompanhamento (ITILFollowup) for criado no GLPI.
 */
function plugin_iflux_item_add_followup(ITILFollowup $followup) {
   include_once(GLPI_ROOT . '/plugins/iflux/inc/notification.class.php');
   PluginIfluxNotification::sendForFollowup($followup);
}

/**
 * Função Hook engatilhada sempre que uma Tarefa (TicketTask) for criada no GLPI.
 */
function plugin_iflux_item_add_task(TicketTask $task) {
   include_once(GLPI_ROOT . '/plugins/iflux/inc/notification.class.php');
   PluginIfluxNotification::sendForTask($task);
}
