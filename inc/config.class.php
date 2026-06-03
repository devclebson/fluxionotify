<?php
class PluginIfluxConfig extends CommonDBTM {
   
   static $rightname = 'plugin_iflux';
   
   static function getTypeName($nb = 0) {
      return 'Configuração iFlux';
   }

   static function getIcon() {
      return 'ti ti-device-mobile';
   }

   public function defineTabs($options = []) {
      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);
      return $ong;
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() === __CLASS__) {
         return [
            1 => 'Configuração',
            2 => 'Dispositivos Registrados',
            3 => 'Logs de Notificação'
         ];
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() === __CLASS__) {
         switch ($tabnum) {
            case 1:
               $item->showConfigFormSection();
               break;
            case 2:
               $item->showTokensList();
               break;
            case 3:
               $item->showLogsList();
               break;
         }
      }
      return true;
   }

   public function showConfigFormSection() {
      global $DB, $CFG_GLPI;

      // Busca as configurações no banco
      $result = $DB->request(['FROM' => 'glpi_plugin_iflux_configs', 'WHERE' => ['id' => 1]]);
      $data = $result->current() ?: [
         'app_token'     => '', 
         'api_url'       => $CFG_GLPI['url_base'], 
         'client_id'     => '', 
         'client_secret' => ''
      ];

      echo "<div class='center' style='margin-top: 15px;'>";
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
         
         $jsFile = dirname(__DIR__) . '/js/qrcode.min.js';
         if (file_exists($jsFile)) {
            echo "<script>" . file_get_contents($jsFile) . "</script>";
         } else {
            $jsPath = Plugin::getWebDir('iflux') . '/js/qrcode.min.js';
            echo "<script src='$jsPath' onload='initIfluxQrCode()'></script>";
         }
         echo "<h3>QR Code para Configuração do App</h3>";
         echo "<div id='qrcode' style='display: inline-block; padding: 10px; background: #fff; border: 10px solid #fff; box-shadow: 0 0 10px #ccc; margin-bottom: 20px;'></div>";
         
         echo "<script>
            function initIfluxQrCode() {
               var el = document.getElementById('qrcode');
               if (el && !el.hasChildNodes()) {
                  new QRCode(el, {
                     text: " . json_encode($qrData) . ",
                     width: 200,
                     height: 200,
                     colorDark : '#000000',
                     colorLight : '#ffffff',
                     correctLevel : QRCode.CorrectLevel.H
                  });
               }
            }
            if (typeof QRCode !== 'undefined') {
               initIfluxQrCode();
            }
         </script>";
      }
      echo "</div>";
   }

   public function showTokensList() {
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

   public function showLogsList() {
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
             $friendlyResponse = '';
             $resJson = json_decode($responseClean, true);
             if (isset($resJson['data'][0])) {
                $item = $resJson['data'][0];
                if (isset($item['status'])) {
                   if ($item['status'] === 'ok') {
                      $friendlyResponse = "Enviado";
                   } else {
                      $errMsg = $item['message'] ?? 'Erro desconhecido';
                      $errCode = $item['details']['error'] ?? '';
                      $friendlyResponse = "Erro: " . ($errCode ? "[$errCode] " : "") . $errMsg;
                   }
                }
             }
             if (empty($friendlyResponse)) {
                $friendlyResponse = $responseClean;
             }
             $responseTrunc = strlen($friendlyResponse) > 60 ? substr($friendlyResponse, 0, 60) . '...' : $friendlyResponse;

            echo "<tr class='tab_bg_1'>";
            echo "<td>".$row['date_creation']."</td>";
            echo "<td><a href='../../../front/ticket.form.php?id=".$row['tickets_id']."' target='_blank'>#".$row['tickets_id']."</a></td>";
            echo "<td>".htmlentities($userLabel)."</td>";
            echo "<td>".htmlentities($row['title'])."</td>";
            echo "<td>".htmlentities($row['message'])."</td>";
            echo "<td>".$statusLabel."</td>";
             $escResponse = htmlentities($responseClean, ENT_QUOTES, 'UTF-8');
             echo "<td style='position: relative; padding-right: 65px; min-width: 200px;' title='".$escResponse."'>";
             echo "<code>".htmlentities($responseTrunc)."</code>";
             echo "<button type='button' style='position: absolute; right: 5px; top: 50%; transform: translateY(-50%); padding: 2px 6px; font-size: 10px; margin: 0; background: #2196f3; color: white; border: none; border-radius: 3px; cursor: pointer; transition: background 0.2s; font-weight: bold;' onclick='var btn=this; navigator.clipboard.writeText(btn.getAttribute(\"data-text\")).then(function(){ btn.innerText=\"Copiado!\"; btn.style.background=\"#2fe417\"; setTimeout(function(){ btn.innerText=\"Copiar\"; btn.style.background=\"#2196f3\"; }, 1500); })' data-text='".$escResponse."'>Copiar</button>";
             echo "</td>";
            echo "</tr>";
         }
      }
      echo "</table>";
      echo "</div>";
   }
}