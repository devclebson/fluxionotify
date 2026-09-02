# ðŸ”Œ FluxIO Notify App Sync - GLPI Plugin

O **FluxIO Notify App Sync** Ã© um plugin nativo para o **GLPI 11** projetado para integrar a plataforma de helpdesk com o aplicativo mÃ³vel **FluxIO Notify**. Ele gerencia o registro de tokens de notificaÃ§Ã£o push e fornece uma interface administrativa para configurar os parÃ¢metros de conexÃ£o do aplicativo.

---

## ðŸŒŸ Funcionalidades Principais

1. **GeraÃ§Ã£o de QR Code de Setup**: Consolinea URL base, App-Token da API e credenciais de cliente OAuth2 em um JSON codificado em QR Code para rÃ¡pida leitura do aplicativo mÃ³vel.
2. **Envio AutomÃ¡tico de NotificaÃ§Ãµes Push**: Monitora a criaÃ§Ã£o ou atribuiÃ§Ã£o de chamados (tickets) a tÃ©cnicos e dispara alertas em tempo real no smartphone correspondente via **Expo Push API**.
3. **Gerenciamento de Dispositivos**: Permite que os administradores visualizem os tÃ©cnicos com dispositivos vinculados e revoguem o acesso/token individualmente caso necessÃ¡rio.
4. **Log de NotificaÃ§Ãµes**: Registro completo do status de envio de notificaÃ§Ãµes, incluindo data, destinatÃ¡rio, mensagem e a resposta retornada pela API do Expo, Ãºtil para debug.

---

## ðŸ—„ï¸ Modelagem do Banco de Dados

Ao clicar em "Instalar" no GLPI, o plugin cria automaticamente trÃªs tabelas:

### 1. `glpi_plugin_fluxionotify_configs`
Armazena os parÃ¢metros globais de conexÃ£o que serÃ£o transmitidos ao aplicativo mÃ³vel.
- `id` (int): ID Ãºnico (sempre `1`).
- `api_url` (varchar): URL base do servidor GLPI.
- `app_token` (varchar): App-Token da API REST do GLPI.
- `client_id` (varchar): Client ID configurado no Cliente de API do GLPI (OAuth2).
- `client_secret` (varchar): Client Secret configurado no Cliente de API do GLPI (OAuth2).

### 2. `glpi_plugin_fluxionotify_pushtokens`
Armazena os tokens de notificaÃ§Ã£o mÃ³vel vinculados a cada usuÃ¡rio.
- `id` (int): ID Ãºnico autoincrementado.
- `users_id` (int): ID do usuÃ¡rio/tÃ©cnico no GLPI (Ãšnico).
- `pushtoken` (varchar): Token gerado pelo Expo no dispositivo mÃ³vel.

### 3. `glpi_plugin_fluxionotify_logs`
Guarda o histÃ³rico dos disparos de notificaÃ§Ãµes push.
- `id` (int): ID Ãºnico autoincrementado.
- `date_creation` (datetime): Data e hora do disparo.
- `tickets_id` (int): ID do chamado que acionou o envio.
- `users_id` (int): ID do tÃ©cnico destinatÃ¡rio da notificaÃ§Ã£o.
- `title` (varchar): TÃ­tulo enviado na notificaÃ§Ã£o.
- `message` (text): Corpo do texto (normalmente o tÃ­tulo do chamado).
- `status` (varchar): Status do envio (`success` ou `error`).
- `response` (text): Resposta HTTP bruta retornada pelo serviÃ§o Expo.

---

## ðŸª Hooks de IntegraÃ§Ã£o GLPI

O plugin utiliza os hooks padrÃµes de ciclo de vida do GLPI 11 registrados em `setup.php`:

* **`csrf_compliant`**: Registrado como compatÃ­vel com tokens CSRF do GLPI 11 para seguranÃ§a.
* **`config_page`**: Direciona o painel de configuraÃ§Ãµes para `front/config.php`.
* **`menu_toadd`**: Adiciona o menu do plugin sob a Ã¡rvore de configuraÃ§Ãµes do sistema.
* **`item_add` para `Ticket`**: Dispara a funÃ§Ã£o `plugin_fluxionotify_item_add_ticket()` em `hook.php` sempre que um chamado for adicionado.

---

## ðŸ“ Estrutura de Arquivos

```
fluxionotify/
â”œâ”€â”€ front/
â”‚   â”œâ”€â”€ config.php            # Controlador principal que processa aÃ§Ãµes (salvar, revogar, limpar logs)
â”‚   â”œâ”€â”€ reflect.php           # Script auxiliar de redirecionamento ou reflexÃ£o de estado
â”‚   â””â”€â”€ teste.php             # Arquivo de teste de ambiente
â”œâ”€â”€ inc/
â”‚   â”œâ”€â”€ config.class.php      # ExibiÃ§Ã£o visual e tabs (ConfiguraÃ§Ã£o, Tokens de Push e Logs)
â”‚   â”œâ”€â”€ notification.class.php# Motor de envio de Push (CURL HTTP para exp.host) e gravaÃ§Ã£o de logs
â”‚   â””â”€â”€ pushtoken.class.php   # Modelo de tabela e travas de seguranÃ§a/permissÃ£o de usuÃ¡rios (CommonDBTM)
â”œâ”€â”€ hook.php                  # Rotinas de instalaÃ§Ã£o/desinstalaÃ§Ã£o (DDL SQL) e gatilho do Ticket Hook
â””â”€â”€ setup.php                 # Arquivo central de registro do plugin e metadados no GLPI
```

---

## ðŸš€ Como Instalar e Configurar no GLPI

### Passo 1: Copiar os arquivos
Copie a pasta `fluxionotify` para o diretÃ³rio de plugins do seu GLPI, renomeando a pasta para `fluxionotify`:
```bash
# O caminho final deve ser:
[DIRETÃ“RIO_GLPI]/plugins/fluxionotify/
```

### Passo 2: Instalar no GLPI
1. FaÃ§a login como **Super-Admin** no painel web do GLPI.
2. Acesse **AdministraÃ§Ã£o > Plugins** (ou **Configurar > Plugins** dependendo da traduÃ§Ã£o).
3. Na linha do plugin **FluxIO Notify App Sync**, clique no Ã­cone de **Instalar** (que executarÃ¡ o script de banco em `hook.php`).
4. Clique no Ã­cone de **Ativar** (play verde).

### Passo 3: Configurar os ParÃ¢metros
1. Acesse o menu de configuraÃ§Ãµes do plugin (geralmente sob **Configurar > Plugins > FluxIO Notify App Sync**).
2. Na aba **ConfiguraÃ§Ã£o**, preencha os campos:
   - **URL Base do GLPI**: Ex: `https://meu-glpi.empresa.com/` (sem a barra final, idealmente).
   - **App-Token da API**: O token obtido em *Configurar > Geral > API*.
   - **Client ID & Client Secret**: Gerados no mÃ³dulo de Clientes de API (OAuth2) do seu GLPI para o login do aplicativo mÃ³vel.
3. Clique em **Salvar ConfiguraÃ§Ãµes**.
4. O sistema irÃ¡ exibir o **QR Code**. Agora, abra o aplicativo FluxIO Notify no celular e faÃ§a a leitura deste QR Code para parear o dispositivo.

