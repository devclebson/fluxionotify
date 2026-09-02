<?php
class PluginFluxionotifyConfig extends CommonDBTM {
   
   static $rightname = 'plugin_fluxionotify';
   
   static function getTypeName($nb = 0) {
      return 'Configuração FluxIO Notify';
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
            1 => 'Bem-vindo',
            2 => 'Configuração',
            3 => 'Dispositivos Registrados',
            4 => 'Logs de Notificação',
            5 => 'Logs da API'
         ];
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() === __CLASS__) {
         switch ($tabnum) {
            case 1:
               $item->showWelcomeTab();
               break;
            case 2:
               $item->showConfigFormSection();
               break;
            case 3:
               $item->showTokensList();
               break;
            case 4:
               $item->showLogsList();
               break;
            case 5:
               $item->showApiLogsList();
               break;
         }
      }
      return true;
   }

   public function showWelcomeTab() {
      echo "<div class='center' style='margin-top: 15px;'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>Bem-vindo ao Plugin FluxIO Notify</th></tr>";
      echo "<tr class='tab_bg_1'><td colspan='2' style='padding: 20px; text-align: left; font-size: 14px; line-height: 1.6;'>";
      echo "<h2>O que este plugin faz?</h2>";
      echo "<p>O <b>FluxIO Notify</b> é a ponte de comunicação oficial entre o seu servidor GLPI e o aplicativo mobile FluxIO Notify. Ele gerencia:</p>";
      echo "<ul>";
      echo "<li>Sincronização de Tokens de Push Notification (Expo) para cada usuário.</li>";
      echo "<li>Autenticação segura e geração de QR Code criptografado para facilitar o login no App.</li>";
      echo "<li>Disparo de alertas e logs de notificação em tempo real (novos chamados, tarefas, acompanhamentos).</li>";
      echo "</ul>";
      echo "<h2>Como configurar?</h2>";
      echo "<p>Na aba <b>Configuração</b>, defina a URL Base do seu servidor e escolha entre as opções GLPI 10 (App-Token) ou GLPI 11 (OAuth2). Em seguida, gere o QR Code para os seus técnicos lerem no App.</p>";
      echo "<h2>Testes em Andamento</h2>";
      echo "<p>No momento, estamos validando a recepção dos tokens de push via API. Se o login no App estiver configurado corretamente, o token do usuário deve aparecer na aba <b>Dispositivos Registrados</b> logo após o login no App.</p>";
      echo "</td></tr>";
      echo "</table>";
      echo "</div>";
   }

   public function showConfigFormSection() {
      global $DB, $CFG_GLPI;

      // Busca as configurações no banco
      $result = $DB->request(['FROM' => 'glpi_plugin_fluxionotify_configs', 'WHERE' => ['id' => 1]]);
      $data = $result->current() ?: [
         'app_token'     => '', 
         'api_url'       => $CFG_GLPI['url_base'], 
         'client_id'     => '', 
         'client_secret' => ''
      ];

      echo "<div class='center' style='margin-top: 15px;'>";
      echo "<form action='config.php' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>Configuração do Servidor FluxIO Notify (Mobile)</th></tr>";
      
      echo "<tr class='tab_bg_1'><td>URL Base do GLPI</td>";
      echo "<td><input type='text' name='api_url' value='".$data['api_url']."' size='50' style='margin: 0;'></td></tr>";

      echo "<tr class='tab_bg_2'><th colspan='2' style='text-align: left; padding-left: 20px; font-weight: bold; font-size: 14px;'>Configurações GLPI 10 (API Legada)</th></tr>";
      echo "<tr class='tab_bg_1'><td>App-Token da API</td>";
      echo "<td>";
      echo "<div style='display: inline-flex; align-items: center; gap: 6px; width: 100%; max-width: 500px;'>";
      echo "<input type='password' id='app_token' name='app_token' value='".htmlentities($data['app_token'] ?? '')."' size='50' style='flex-grow: 1; margin: 0;' placeholder='Preencha apenas se for usar o GLPI 10'>";
      echo "<button type='button' class='btn btn-icon btn-outline-secondary' style='height: 35px; min-width: 38px; display: inline-flex; align-items: center; justify-content: center; margin: 0;' onclick='toggleVisibility(\"app_token\", this)' title='Visualizar'><i class='ti ti-eye'></i></button>";
      echo "<button type='button' class='btn btn-icon btn-outline-secondary' style='height: 35px; min-width: 38px; display: inline-flex; align-items: center; justify-content: center; margin: 0;' onclick='pasteFromClipboard(\"app_token\")' title='Colar'><i class='ti ti-clipboard'></i></button>";
      echo "</div>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><th colspan='2' style='text-align: left; padding-left: 20px; font-weight: bold; font-size: 14px;'>Configurações GLPI 11 (API v2 - OAuth2)</th></tr>";
      echo "<tr class='tab_bg_1'><td>Client ID (OAuth2)</td>";
      echo "<td>";
      echo "<div style='display: inline-flex; align-items: center; gap: 6px; width: 100%; max-width: 500px;'>";
      echo "<input type='password' id='client_id' name='client_id' value='".htmlentities($data['client_id'] ?? '')."' size='50' style='flex-grow: 1; margin: 0;' placeholder='Preencha apenas se for usar o GLPI 11'>";
      echo "<button type='button' class='btn btn-icon btn-outline-secondary' style='height: 35px; min-width: 38px; display: inline-flex; align-items: center; justify-content: center; margin: 0;' onclick='toggleVisibility(\"client_id\", this)' title='Visualizar'><i class='ti ti-eye'></i></button>";
      echo "<button type='button' class='btn btn-icon btn-outline-secondary' style='height: 35px; min-width: 38px; display: inline-flex; align-items: center; justify-content: center; margin: 0;' onclick='pasteFromClipboard(\"client_id\")' title='Colar'><i class='ti ti-clipboard'></i></button>";
      echo "</div>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>Client Secret (OAuth2)</td>";
      echo "<td>";
      echo "<div style='display: inline-flex; align-items: center; gap: 6px; width: 100%; max-width: 500px;'>";
      echo "<input type='password' id='client_secret' name='client_secret' value='".htmlentities($data['client_secret'] ?? '')."' size='50' style='flex-grow: 1; margin: 0;' placeholder='Preencha apenas se for usar o GLPI 11'>";
      echo "<button type='button' class='btn btn-icon btn-outline-secondary' style='height: 35px; min-width: 38px; display: inline-flex; align-items: center; justify-content: center; margin: 0;' onclick='toggleVisibility(\"client_secret\", this)' title='Visualizar'><i class='ti ti-eye'></i></button>";
      echo "<button type='button' class='btn btn-icon btn-outline-secondary' style='height: 35px; min-width: 38px; display: inline-flex; align-items: center; justify-content: center; margin: 0;' onclick='pasteFromClipboard(\"client_secret\")' title='Colar'><i class='ti ti-clipboard'></i></button>";
      echo "</div>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='Salvar Configurações'>";
      echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."' />";
      echo "</td></tr></table></form>";

      echo "<script>
         function toggleVisibility(id, btn) {
            var input = document.getElementById(id);
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
               input.type = 'text';
               if (icon) {
                  icon.className = 'ti ti-eye-off';
               }
            } else {
               input.type = 'password';
               if (icon) {
                  icon.className = 'ti ti-eye';
               }
            }
         }

         function pasteFromClipboard(id) {
            if (!navigator.clipboard) {
               alert('Acesso à área de transferência bloqueado pelo navegador. Por favor, use Ctrl+V para colar manualmente.');
               return;
            }
            navigator.clipboard.readText().then(function(text) {
               if (text) {
                  var input = document.getElementById(id);
                  input.value = text;
               }
            }).catch(function(err) {
               alert('Permissão para colar negada ou bloqueada. Por favor, use Ctrl+V para colar manualmente.');
            });
         }
      </script>";

      // Lógica do QR Code Criptografado
      if (!empty($data['api_url'])) {
         $isGlpi11 = !empty($data['client_id']);
         
         $qrDataPayload = [
            'url'     => rtrim($data['api_url'], '/'),
            'version' => $isGlpi11 ? '11' : '10'
         ];
         
         if ($isGlpi11) {
             $qrDataPayload['client_id'] = $data['client_id'];
             $qrDataPayload['client_secret'] = $data['client_secret'];
         }
         // Sempre enviar o token, pois o GLPI 11 também usa a V1 (Legacy) como porto seguro para Push Tokens
         $qrDataPayload['token'] = $data['app_token'];

         $secretKey = "FluxIO Notify@AppSync#2026";
         $qrJson = json_encode($qrDataPayload);
         $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
         $encrypted = openssl_encrypt($qrJson, 'aes-256-cbc', $secretKey, 0, $iv);
         
         // Base64 duplo para garantir leitura segura no app
         $finalQrText = base64_encode($encrypted . '::' . base64_encode($iv));
         
         $jsFile = dirname(__DIR__) . '/js/qrcode.min.js';
         if (file_exists($jsFile)) {
            echo "<script>" . file_get_contents($jsFile) . "</script>";
         } else {
            $jsPath = Plugin::getWebDir('fluxionotify') . '/js/qrcode.min.js';
            echo "<script src='$jsPath' onload='initFluxIONotifyQrCode()'></script>";
         }
         
         $versionText = $isGlpi11 ? "GLPI 11 (OAuth2)" : "GLPI 10 (App-Token)";
         echo "<h3>QR Code Seguro para App FluxIO Notify ($versionText)</h3>";
         echo "<p style='color: #666; font-size: 12px;'>Este QR Code está criptografado (AES-256-CBC) e só pode ser lido pelo aplicativo oficial.</p>";
         echo "<div id='qrcode' style='display: inline-block; padding: 10px; background: #fff; border: 10px solid #fff; box-shadow: 0 0 10px #ccc; margin-bottom: 20px;'></div>";
         
         echo "<script>
            function initFluxIONotifyQrCode() {
               var el = document.getElementById('qrcode');
               if (el && !el.hasChildNodes()) {
                  new QRCode(el, {
                     text: " . json_encode($finalQrText) . ",
                     width: 200,
                     height: 200,
                     colorDark : '#000000',
                     colorLight : '#ffffff',
                     correctLevel : QRCode.CorrectLevel.H
                  });
               }
            }
            if (typeof QRCode !== 'undefined') {
               initFluxIONotifyQrCode();
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
         'FROM' => 'glpi_plugin_fluxionotify_pushtokens AS t',
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
         'FROM' => 'glpi_plugin_fluxionotify_logs AS l',
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

   public function showApiLogsList() {
      global $DB;

      $query = [
         'SELECT' => '*',
         'FROM' => 'glpi_plugin_fluxionotify_logs_api',
         'ORDER' => 'id DESC',
         'LIMIT' => 100
      ];
      $result = $DB->request($query);

      echo "<div class='center'>";
      
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>Logs de Requisições na API Customizada (Últimas 100)</th></tr>";
      echo "<tr class='tab_bg_2' style='font-weight: bold;'>";
      echo "<td>ID</td><td>Data/Hora</td><td>IP</td><td>Método</td><td>Endpoint</td><td>Status</td><td>Payload / Resposta</td>";
      echo "</tr>";

      if (count($result) === 0) {
         echo "<tr class='tab_bg_1'><td colspan='7' class='center'>Nenhuma requisição recebida ainda.</td></tr>";
      } else {
         foreach ($result as $row) {
            $statusLabel = ($row['status_code'] >= 200 && $row['status_code'] < 300) ? 
               "<span style='background: #2fe417; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;'>".$row['status_code']."</span>" : 
               "<span style='background: #f44336; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;'>".$row['status_code']."</span>";

            $payloadTrunc = strlen($row['payload']) > 40 ? substr($row['payload'], 0, 40) . '...' : $row['payload'];
            $responseTrunc = strlen($row['response']) > 40 ? substr($row['response'], 0, 40) . '...' : $row['response'];

            echo "<tr class='tab_bg_1'>";
            echo "<td>".$row['id']."</td>";
            echo "<td>".$row['date_creation']."</td>";
            echo "<td>".htmlentities($row['ip_address'] ?? '')."</td>";
            echo "<td>".htmlentities($row['method'] ?? '')."</td>";
            echo "<td>".htmlentities($row['endpoint'] ?? '')."</td>";
            echo "<td>".$statusLabel."</td>";
            
            $escPayload = htmlentities($row['payload'] ?? '', ENT_QUOTES, 'UTF-8');
            $escResponse = htmlentities($row['response'] ?? '', ENT_QUOTES, 'UTF-8');
            
            echo "<td>";
            echo "<div style='font-size: 11px; margin-bottom: 4px;'><b>IN:</b> <code title='".$escPayload."'>".htmlentities($payloadTrunc)."</code></div>";
            echo "<div style='font-size: 11px;'><b>OUT:</b> <code title='".$escResponse."'>".htmlentities($responseTrunc)."</code></div>";
            echo "</td>";
            echo "</tr>";
         }
      }
      echo "</table>";
      echo "</div>";
   }
}