<?php
class PluginIfluxNotification {
   
   /**
    * Dispara a notificação Push via Expo para todos os técnicos atribuídos ao chamado
    */
   static function notifyAssignedTechnicians($ticketId, $title, $message) {
      global $DB;
      
      // Buscar quem é o técnico atribuído ao chamado
      $result = $DB->request([
         'SELECT' => 'users_id',
         'FROM'   => 'glpi_tickets_users',
         'WHERE'  => [
            'tickets_id' => $ticketId,
            'type'       => CommonITILActor::ASSIGN
         ]
      ]);
      
      $sentCount = 0;
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
            
            $response = self::sendToExpo($pushToken, $title, $message, ['ticketId' => $ticketId]);
            
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
               'message'       => $message,
               'status'        => $status,
               'response'      => $response ? $response : 'Sem resposta do Expo'
            ]);
            $sentCount++;
         }
      }
      return $sentCount;
   }

   static function getStatusLabel($statusId) {
      $statuses = [
         1 => 'Novo',
         2 => 'Em Atendimento',
         3 => 'Planejado',
         4 => 'Pendente',
         5 => 'Solucionado',
         6 => 'Fechado'
      ];
      return $statuses[$statusId] ?? 'Desconhecido';
   }

   /**
    * Chamado quando um chamado é criado (item_add para Ticket)
    */
   static function sendForTicket(Ticket $ticket) {
      $ticketId = $ticket->fields['id'];
      $ticketName = $ticket->fields['name'] ?? 'Novo Chamado';
      
      $title = "Novo Chamado (#$ticketId)";
      self::notifyAssignedTechnicians($ticketId, $title, $ticketName);
   }

   /**
    * Chamado quando um chamado é atualizado (item_update para Ticket)
    */
   static function sendForTicketUpdate(Ticket $ticket) {
      $ticketId = $ticket->fields['id'];
      $ticketName = $ticket->fields['name'] ?? 'Chamado Atualizado';
      
      // Detecta se houve mudança de status para colocar no título
      $statusText = '';
      if (isset($ticket->updates) && in_array('status', $ticket->updates)) {
         $statusId = $ticket->fields['status'];
         $statusText = " - Status: " . self::getStatusLabel($statusId);
      }
      
      $title = "Chamado Atualizado (#$ticketId)" . $statusText;
      self::notifyAssignedTechnicians($ticketId, $title, $ticketName);
   }

   /**
    * Chamado quando um acompanhamento (followup) é criado (item_add para ITILFollowup)
    */
   static function sendForFollowup(ITILFollowup $followup) {
      if ($followup->fields['itemtype'] !== 'Ticket') {
         return;
      }
      $ticketId = $followup->fields['items_id'];
      
      // Buscar o nome do chamado
      global $DB;
      $ticketResult = $DB->request([
         'SELECT' => 'name',
         'FROM'   => 'glpi_tickets',
         'WHERE'  => ['id' => $ticketId]
      ]);
      $ticketRow = $ticketResult->current();
      $ticketName = $ticketRow ? $ticketRow['name'] : 'Chamado';
      
      $content = strip_tags(html_entity_decode($followup->fields['content'] ?? ''));
      $contentTruncated = (function_exists('mb_substr') && function_exists('mb_strlen')) ? 
         (mb_strlen($content) > 100 ? mb_substr($content, 0, 100) . '...' : $content) :
         (strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content);
      
      $title = "Novo Acompanhamento (#$ticketId)";
      $message = "No chamado: $ticketName\n\"$contentTruncated\"";
      
      self::notifyAssignedTechnicians($ticketId, $title, $message);
   }

   /**
    * Chamado quando uma tarefa (task) é criada (item_add para TicketTask)
    */
   static function sendForTask(TicketTask $task) {
      $ticketId = isset($task->fields['tickets_id']) ? $task->fields['tickets_id'] : (isset($task->fields['items_id']) && $task->fields['itemtype'] === 'Ticket' ? $task->fields['items_id'] : null);
      if (!$ticketId) {
         return;
      }
      
      // Buscar o nome do chamado
      global $DB;
      $ticketResult = $DB->request([
         'SELECT' => 'name',
         'FROM'   => 'glpi_tickets',
         'WHERE'  => ['id' => $ticketId]
      ]);
      $ticketRow = $ticketResult->current();
      $ticketName = $ticketRow ? $ticketRow['name'] : 'Chamado';
      
      $content = strip_tags(html_entity_decode($task->fields['content'] ?? ''));
      $contentTruncated = (function_exists('mb_substr') && function_exists('mb_strlen')) ? 
         (mb_strlen($content) > 100 ? mb_substr($content, 0, 100) . '...' : $content) :
         (strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content);
      
      $title = "Nova Tarefa (#$ticketId)";
      $message = "No chamado: $ticketName\n\"$contentTruncated\"";
      
      self::notifyAssignedTechnicians($ticketId, $title, $message);
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
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Limite de 2 segundos para conectar
      curl_setopt($ch, CURLOPT_TIMEOUT, 3);        // Limite de 3 segundos para resposta
      
      $response = curl_exec($ch);
      curl_close($ch);
      
      return $response;
   }
}
