# ðŸ”Œ Arquitetura do Plugin FluxIO Notify App Sync (GLPI 11)

Este documento descreve a estrutura interna, as tabelas de banco de dados, o ciclo de ganchos (hooks) e o sistema de disparo de notificaÃ§Ãµes push do plugin **FluxIO Notify App Sync** desenvolvido para o **GLPI 11**.

---

## ðŸ› ï¸ 1. Banco de Dados (Schemas)

O plugin cria trÃªs tabelas principais na instalaÃ§Ã£o (`plugin_fluxionotify_install` em `hook.php`):

### 1.1. `glpi_plugin_fluxionotify_configs`
Armazena a parametrizaÃ§Ã£o de conexÃµes que Ã© codificada no QR Code para parear o aplicativo mobile.
* `id` (INT, Primary Key) - Sempre ID 1.
* `api_url` (VARCHAR) - URL base do GLPI.
* `app_token` (VARCHAR) - Token de Aplicativo gerado no GLPI.
* `client_id` (VARCHAR) - Client ID para autenticaÃ§Ã£o OAuth2.
* `client_secret` (VARCHAR) - Client Secret para autenticaÃ§Ã£o OAuth2.

### 1.2. `glpi_plugin_fluxionotify_pushtokens`
Armazena os tokens de notificaÃ§Ã£o push gerados pelo Expo em cada dispositivo mÃ³vel dos tÃ©cnicos.
* `id` (INT, Primary Key).
* `users_id` (INT, Unique Key) - ID do usuÃ¡rio no GLPI (1:1).
* `pushtoken` (VARCHAR) - Token do tipo `ExponentPushToken[...]`.

### 1.3. `glpi_plugin_fluxionotify_logs`
Registra o histÃ³rico de notificaÃ§Ãµes disparadas pelo plugin para auditoria rÃ¡pida.
* `id` (INT, Primary Key).
* `date_creation` (DATETIME) - Data/Hora do disparo.
* `tickets_id` (INT) - ID do chamado.
* `users_id` (INT) - ID do tÃ©cnico destinatÃ¡rio.
* `title` (VARCHAR) - TÃ­tulo da notificaÃ§Ã£o.
* `message` (TEXT) - Corpo/Mensagem enviada.
* `status` (VARCHAR) - Status do envio (`success` ou `error`).
* `response` (TEXT) - Resposta JSON bruta obtida da API do Expo.

---

## ðŸ”— 2. Ganchos de Monitoramento (Hooks)

O plugin monitora o ciclo de vida dos chamados no GLPI registrando gatilhos em `setup.php`:

```php
// Registro dos hooks
$PLUGIN_HOOKS['item_add']['fluxionotify'] = [
   'Ticket'       => 'plugin_fluxionotify_item_add_ticket',
   'ITILFollowup' => 'plugin_fluxionotify_item_add_followup',
   'TicketTask'   => 'plugin_fluxionotify_item_add_task'
];
$PLUGIN_HOOKS['item_update']['fluxionotify'] = [
   'Ticket'       => 'plugin_fluxionotify_item_update_ticket'
];
```

* **CriaÃ§Ã£o de Chamados (`Ticket` - Add)**: Notifica os tÃ©cnicos atribuÃ­dos sobre o novo chamado.
* **AtualizaÃ§Ã£o de Chamados (`Ticket` - Update)**: Dispara notificaÃ§Ã£o em alteraÃ§Ãµes importantes (ex: mudanÃ§a de status ITIL ou reatribuiÃ§Ãµes).
* **Novos Acompanhamentos (`ITILFollowup` - Add)**: Envia o texto formatado (sem tags HTML) inserido na timeline do chamado.
* **Novas Tarefas (`TicketTask` - Add)**: Avisa os tÃ©cnicos vinculados sobre tarefas adicionadas ao chamado.

---

## ðŸ” 3. Direitos e PermissÃµes por Perfil (Profiles)

O plugin registra o direito de perfil Ãºnico `'plugin_fluxionotify'` associado ao mÃ³dulo nativo de perfis do GLPI atravÃ©s da classe **`PluginFluxionotifyProfile`**:
* **LER (1)**: PermissÃ£o para visualizar as configuraÃ§Ãµes bÃ¡sicas e ler a lista de tokens.
* **ATUALIZAR (2)**: PermissÃ£o para alterar as credenciais da API no formulÃ¡rio.
* **CRIAR (4)**: PermissÃ£o para cadastrar novos tokens.
* **EXCLUIR (8)**: PermissÃ£o para revogar acessos de tokens cadastrados.
* **APAGAR (16)**: PermissÃ£o para limpar o histÃ³rico da tabela de logs.

*Nota: Durante a instalaÃ§Ã£o, o perfil padrÃ£o 'super-admin' recebe automaticamente acesso total (soma 31) a essas permissÃµes.*

---

## ðŸš€ 4. Motor de Disparo Push (`notification.class.php`)

As notificaÃ§Ãµes sÃ£o disparadas de forma assÃ­ncrona ao servidor web do GLPI seguindo estas especificaÃ§Ãµes:
1. **Busca de DestinatÃ¡rios**: O plugin consulta os tÃ©cnicos atribuÃ­dos ao chamado em `glpi_tickets_users` onde `type = CommonITILActor::ASSIGN`.
2. **Resgate de Tokens**: Filtra e localiza os push tokens correspondentes na tabela `glpi_plugin_fluxionotify_pushtokens`.
3. **Chamada cURL com Timeouts**:
   ```php
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Limite de conexÃ£o: 2 segundos
   curl_setopt($ch, CURLOPT_TIMEOUT, 3);        // Limite de resposta: 3 segundos
   ```
   *Esses limites estritos impedem o travamento ou lentidÃ£o do painel web do GLPI caso a API do Expo esteja lenta.*

---

## ðŸŽ¨ 5. Painel Administrativo (`config.class.php`)

* **GeraÃ§Ã£o Local do QR Code**: O arquivo `js/qrcode.min.js` Ã© lido localmente pelo PHP via `file_get_contents()` e embutido inline no HTML em uma tag `<script>`. Isso soluciona o erro 404 de assets em ambientes fechados ou em GLPI 10+ que utilizam o diretÃ³rio `/public`.
* **Roteamento de Links**: O link da coluna "Chamado" nos logs utiliza caminho com trÃªs nÃ­veis de retorno (`../../../front/ticket.form.php?id=...`), apontando de forma consistente para a tela nativa do chamado.
* **BotÃ£o Copiar Resposta**: Um botÃ£o com micro-interaÃ§Ã£o Javascript que copia a resposta completa da API do Expo para o clipboard do desenvolvedor e dÃ¡ feedback visual rÃ¡pido de sucesso.

