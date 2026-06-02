<?php
class PluginIfluxConfig extends CommonDBTM {
   
   static $rightname = 'config';
   
   static function getTypeName($nb = 0) {
      return 'Configuração iFlux';
   }

   function showFormConfig($tab = 'config') {
      global $DB, $CFG_GLPI;

      // 1. Renderizar Barra de Abas Estilizada
      echo "<div class='center' style='margin-bottom: 25px; margin-top: 15px;'>";
      echo "<a href='config.php?tab=config' style='margin: 0 5px; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; background: ".($tab == 'config' ? '#143860; color: white;' : '#f0f0f0; color: #333; border: 1px solid #ccc;')."'>Configuração</a>";
      echo "<a href='config.php?tab=tokens' style='margin: 0 5px; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; background: ".($tab == 'tokens' ? '#143860; color: white;' : '#f0f0f0; color: #333; border: 1px solid #ccc;')."'>Tokens de Push</a>";
      echo "<a href='config.php?tab=logs' style='margin: 0 5px; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; background: ".($tab == 'logs' ? '#143860; color: white;' : '#f0f0f0; color: #333; border: 1px solid #ccc;')."'>Logs de Envio</a>";
      echo "</div>";

      if ($tab === 'tokens') {
         $this->showTokensList();
      } else if ($tab === 'logs') {
         $this->showLogsList();
      } else {
         $this->showConfigFormSection();
      }
   }

   private function showConfigFormSection() {
      global $DB, $CFG_GLPI;

      // Busca as configurações no banco
      $result = $DB->request(['FROM' => 'glpi_plugin_iflux_configs', 'WHERE' => ['id' => 1]]);
      $data = $result->current() ?: [
         'app_token'     => '', 
         'api_url'       => $CFG_GLPI['url_base'], 
         'client_id'     => '', 
         'client_secret' => ''
      ];

      echo "<div class='center'>";
      echo "<form action='config.php' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>Configuração do Servidor iFlux (Mobile)</th></tr>";
      
      echo "<tr class='tab_bg_1'><td>URL Base do GLPI</td>";
      echo "<td><input type='text' name='api_url' value='".$data['api_url']."' size='50'></td></tr>";

      echo "<tr class='tab_bg_1'><td>App-Token da API</td>";
      echo "<td><input type='text' name='app_token' value='".$data['app_token']."' size='50'></td></tr>";

      echo "<tr class='tab_bg_1'><td>Client ID (OAuth2)</td>";
      echo "<td><input type='text' name='client_id' value='".$data['client_id']."' size='50'></td></tr>";

      echo "<tr class='tab_bg_1'><td>Client Secret (OAuth2)</td>";
      echo "<td><input type='text' name='client_secret' value='".$data['client_secret']."' size='50'></td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='Salvar Configurações'>";
      echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."' />";
      echo "</td></tr></table></form>";

      // Lógica do QR Code
      if (!empty($data['app_token']) && !empty($data['api_url'])) {
         $qrData = json_encode([
            'url'           => rtrim($data['api_url'], '/'),
            'token'         => $data['app_token'],
            'client_id'     => $data['client_id'],
            'client_secret' => $data['client_secret']
         ]);
         echo "<h3>QR Code para Configuração do App</h3>";
         $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);
         echo "<img src='$qrUrl' style='border:10px solid #fff; box-shadow: 0 0 10px #ccc;' />";
      }
      echo "</div>";
   }

   private function showTokensList() {
      global $DB;

      $query = [
         'SELECT' => [
            't.id',
            't.pushtoken',
            'u.name AS username',
            'u.realname',
            'u.firstname'
         ],
         'FROM' => 'glpi_plugin_iflux_pushtokens AS t',
         'INNER JOIN' => [
            'glpi_users AS u' => [
               'ON' => [
                  't' => 'users_id',
                  'u' => 'id'
               ]
            ]
         ],
         'ORDER' => 'u.name'
      ];
      $result = $DB->request($query);

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='5'>Dispositivos Registrados para Push</th></tr>";
      echo "<tr class='tab_bg_2' style='font-weight: bold;'>";
      echo "<td>ID</td><td>Login</td><td>Nome Completo</td><td>Token de Push</td><td class='center'>Ação</td>";
      echo "</tr>";

      if (count($result) === 0) {
         echo "<tr class='tab_bg_1'><td colspan='5' class='center'>Nenhum dispositivo registrado ainda.</td></tr>";
      } else {
         foreach ($result as $row) {
            $fullName = trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? ''));
            if (empty($fullName)) {
               $fullName = "-";
            }
            $tokenTruncated = strlen($row['pushtoken']) > 45 ? substr($row['pushtoken'], 0, 45) . '...' : $row['pushtoken'];
            
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$row['id']."</td>";
            echo "<td>".$row['username']."</td>";
            echo "<td>".$fullName."</td>";
            echo "<td title='".$row['pushtoken']."'><code>".htmlentities($tokenTruncated)."</code></td>";
            echo "<td class='center'>";
            $deleteUrl = "config.php?action=delete_token&id=".$row['id']."&_glpi_csrf_token=".Session::getNewCSRFToken();
            echo "<a href='$deleteUrl' class='vsubmit' style='background: #f44336; color: white; padding: 4px 8px; text-decoration: none; border-radius: 4px; font-size: 12px;' onclick=\"return confirm('Deseja realmente revogar o token deste dispositivo?');\">Revogar Acesso</a>";
            echo "</td>";
            echo "</tr>";
         }
      }
      echo "</table>";
      echo "</div>";
   }

   private function showLogsList() {
      global $DB;

      $query = [
         'SELECT' => [
            'l.id',
            'l.date_creation',
            'l.tickets_id',
            'l.title',
            'l.message',
            'l.status',
            'l.response',
            'u.name AS username',
            'u.realname',
            'u.firstname'
         ],
         'FROM' => 'glpi_plugin_iflux_logs AS l',
         'LEFT JOIN' => [
            'glpi_users AS u' => [
               'ON' => [
                  'l' => 'users_id',
                  'u' => 'id'
               ]
            ]
         ],
         'ORDER' => 'l.id DESC',
         'LIMIT' => 100
      ];
      $result = $DB->request($query);

      echo "<div class='center'>";
      
      // Botão para limpar logs
      if (count($result) > 0) {
         echo "<form action='config.php' method='post' style='margin-bottom: 15px; text-align: right; width: 95%; max-width: 950px; margin-left: auto; margin-right: auto;'>";
         echo "<input type='hidden' name='action' value='clear_logs' />";
         echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."' />";
         echo "<input type='submit' class='submit' style='background: #ffa500; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; cursor: pointer;' value='Limpar Histórico de Logs' onclick=\"return confirm('Deseja realmente apagar todos os logs de notificações?');\" />";
         echo "</form>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>Logs de Notificações Enviadas (Últimos 100)</th></tr>";
      echo "<tr class='tab_bg_2' style='font-weight: bold;'>";
      echo "<td>Data/Hora</td><td>Chamado</td><td>Destinatário (Técnico)</td><td>Título</td><td>Mensagem</td><td>Status</td><td>Resposta da API (Expo)</td>";
      echo "</tr>";

      if (count($result) === 0) {
         echo "<tr class='tab_bg_1'><td colspan='7' class='center'>Nenhum log registrado ainda.</td></tr>";
      } else {
         foreach ($result as $row) {
            $fullName = $row['username'] ? trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? '')) : 'Sistema/Desconhecido';
            $userLabel = $row['username'] ? $row['username'] . " (".$fullName.")" : 'Desconhecido';
            $statusLabel = $row['status'] === 'success' ? 
               "<span style='background: #2fe417; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;'>Sucesso</span>" : 
               "<span style='background: #f44336; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;'>Falha</span>";

            $responseClean = $row['response'];
            // Truncar resposta longa
            $responseTrunc = strlen($responseClean) > 40 ? substr($responseClean, 0, 40) . '...' : $responseClean;

            echo "<tr class='tab_bg_1'>";
            echo "<td>".$row['date_creation']."</td>";
            echo "<td><a href='../front/ticket.form.php?id=".$row['tickets_id']."' target='_blank'>#".$row['tickets_id']."</a></td>";
            echo "<td>".htmlentities($userLabel)."</td>";
            echo "<td>".htmlentities($row['title'])."</td>";
            echo "<td>".htmlentities($row['message'])."</td>";
            echo "<td>".$statusLabel."</td>";
            echo "<td title='".htmlentities($responseClean)."'><code>".htmlentities($responseTrunc)."</code></td>";
            echo "</tr>";
         }
      }
      echo "</table>";
      echo "</div>";
   }
}