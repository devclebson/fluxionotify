# 🔌 Arquitetura do Plugin FluxIO Notify App Sync (GLPI 11)

Este documento descreve a estrutura interna, as tabelas de banco de dados, o ciclo de ganchos (hooks) e o sistema de disparo de notificações push do plugin **FluxIO Notify App Sync** desenvolvido para o **GLPI 11**.

---

## 🛠️ 1. Banco de Dados (Schemas)

O plugin cria três tabelas principais na instalação (`plugin_fluxionotify_install` em `hook.php`):

### 1.1. `glpi_plugin_fluxionotify_configs`
Armazena a parametrização de conexões que é codificada no QR Code para parear o aplicativo mobile.
* `id` (INT, Primary Key) - Sempre ID 1.
* `api_url` (VARCHAR) - URL base do GLPI.
* `app_token` (VARCHAR) - Token de Aplicativo gerado no GLPI.
* `client_id` (VARCHAR) - Client ID para autenticação OAuth2.
* `client_secret` (VARCHAR) - Client Secret para autenticação OAuth2.

### 1.2. `glpi_plugin_fluxionotify_pushtokens`
Armazena os tokens de notificação push gerados pelo Expo em cada dispositivo móvel dos técnicos.
* `id` (INT, Primary Key).
* `users_id` (INT, Unique Key) - ID do usuário no GLPI (1:1).
* `pushtoken` (VARCHAR) - Token do tipo `ExponentPushToken[...]`.

### 1.3. `glpi_plugin_fluxionotify_logs`
Registra o histórico de notificações disparadas pelo plugin para auditoria rápida.
* `id` (INT, Primary Key).
* `date_creation` (DATETIME) - Data/Hora do disparo.
* `tickets_id` (INT) - ID do chamado.
* `users_id` (INT) - ID do técnico destinatário.
* `title` (VARCHAR) - Título da notificação.
* `message` (TEXT) - Corpo/Mensagem enviada.
* `status` (VARCHAR) - Status do envio (`success` ou `error`).
* `response` (TEXT) - Resposta JSON bruta obtida da API do Expo.

---

## 🔗 2. Ganchos de Monitoramento (Hooks)

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

* **Criação de Chamados (`Ticket` - Add)**: Notifica os técnicos atribuídos sobre o novo chamado.
* **Atualização de Chamados (`Ticket` - Update)**: Dispara notificação em alterações importantes (ex: mudança de status ITIL ou reatribuições).
* **Novos Acompanhamentos (`ITILFollowup` - Add)**: Envia o texto formatado (sem tags HTML) inserido na timeline do chamado.
* **Novas Tarefas (`TicketTask` - Add)**: Avisa os técnicos vinculados sobre tarefas adicionadas ao chamado.

---

## 🔐 3. Direitos e Permissões por Perfil (Profiles)

O plugin registra o direito de perfil único `'plugin_fluxionotify'` associado ao módulo nativo de perfis do GLPI através da classe **`PluginFluxionotifyProfile`**:
* **LER (1)**: Permissão para visualizar as configurações básicas e ler a lista de tokens.
* **ATUALIZAR (2)**: Permissão para alterar as credenciais da API no formulário.
* **CRIAR (4)**: Permissão para cadastrar novos tokens.
* **EXCLUIR (8)**: Permissão para revogar acessos de tokens cadastrados.
* **APAGAR (16)**: Permissão para limpar o histórico da tabela de logs.

*Nota: Durante a instalação, o perfil padrão 'super-admin' recebe automaticamente acesso total (soma 31) a essas permissões.*

---

## 🚀 4. Motor de Disparo Push (`notification.class.php`)

As notificações são disparadas de forma assíncrona ao servidor web do GLPI seguindo estas especificações:
1. **Busca de Destinatários**: O plugin consulta os técnicos atribuídos ao chamado em `glpi_tickets_users` onde `type = CommonITILActor::ASSIGN`.
2. **Resgate de Tokens**: Filtra e localiza os push tokens correspondentes na tabela `glpi_plugin_fluxionotify_pushtokens`.
3. **Chamada cURL com Timeouts**:
   ```php
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Limite de conexão: 2 segundos
   curl_setopt($ch, CURLOPT_TIMEOUT, 3);        // Limite de resposta: 3 segundos
   ```
   *Esses limites estritos impedem o travamento ou lentidão do painel web do GLPI caso a API do Expo esteja lenta.*

---

## 🎨 5. Painel Administrativo (`config.class.php`)

* **Geração Local do QR Code**: O arquivo `js/qrcode.min.js` é lido localmente pelo PHP via `file_get_contents()` e embutido inline no HTML em uma tag `<script>`. Isso soluciona o erro 404 de assets em ambientes fechados ou em GLPI 10+ que utilizam o diretório `/public`.
* **Roteamento de Links**: O link da coluna "Chamado" nos logs utiliza caminho com três níveis de retorno (`../../../front/ticket.form.php?id=...`), apontando de forma consistente para a tela nativa do chamado.
* **Botão Copiar Resposta**: Um botão com micro-interação Javascript que copia a resposta completa da API do Expo para o clipboard do desenvolvedor e dá feedback visual rápido de sucesso.
