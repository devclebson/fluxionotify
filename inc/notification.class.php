<?php
class PluginIfluxNotification {
   
   /**
    * Dispara a notificação Push via Expo quando um chamado é criado/atribuído
    */
   static function sendForTicket(Ticket $ticket) {
      global $DB;
      
      $ticketId = $ticket->fields['id'];
      $ticketName = $ticket->fields['name'] ?? 'Novo Chamado';
      
      // Buscar quem é o técnico atribuído ao chamado
      $result = $DB->request([
         'SELECT' => 'users_id',
         'FROM'   => 'glpi_tickets_users',
         'WHERE'  => [
            'tickets_id' => $ticketId,
            'type'       => CommonITILActor::ASSIGN
         ]
      ]);
      
      foreach ($result as $row) {
         $technicianId = $row['users_id'];
         
         // Buscar o token de push deste técnico na nossa tabela do plugin
         $tokenResult = $DB->request([
            'SELECT' => 'pushtoken',
            'FROM'   => 'glpi_plugin_iflux_pushtokens',
            'WHERE'  => ['users_id' => $technicianId]
         ]);
         
         if ($tokenRow = $tokenResult->current()) {
            $pushToken = $tokenRow['pushtoken'];
            
            $title = "Novo Chamado (#$ticketId)";
            $response = self::sendToExpo($pushToken, $title, $ticketName, ['ticketId' => $ticketId]);
            
            $status = 'error';
            if ($response) {
               $resData = json_decode($response, true);
               if (isset($resData['data'][0]['status']) && $resData['data'][0]['status'] === 'ok') {
                  $status = 'success';
               }
            }
            
            // Grava o log no banco de dados
            $DB->insert('glpi_plugin_iflux_logs', [
               'date_creation' => date('Y-m-d H:i:s'),
               'tickets_id'    => $ticketId,
               'users_id'     => $technicianId,
               'title'         => $title,
               'message'       => $ticketName,
               'status'        => $status,
               'response'      => $response ? $response : 'Sem resposta do Expo'
            ]);
         }
      }
   }
   
   static function sendToExpo($token, $title, $body, $data = []) {
      $payload = [
         [
            "to" => $token,
            "title" => $title,
            "body" => $body,
            "sound" => "default",
            "channelId" => "default",
            "data" => $data
         ]
      ];

      $ch = curl_init("https://exp.host/--/api/v2/push/send");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      
      $response = curl_exec($ch);
      curl_close($ch);
      
      return $response;
   }
}
