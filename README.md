# 🔌 FluxIO Notify - GLPI Plugin

O **FluxIO Notify** é um plugin nativo para o **GLPI 11** projetado para integrar a plataforma de helpdesk com o aplicativo móvel **FluxIO Notify**. Ele gerencia o registro de tokens de notificação push e fornece uma interface administrativa para configurar os parâmetros de conexão do aplicativo.

---

## 🌟 Funcionalidades Principais

1. **Geração de QR Code de Setup**: Consolinea URL base, App-Token da API e credenciais de cliente OAuth2 em um JSON codificado em QR Code para rápida leitura do aplicativo móvel.
2. **Envio Automático de Notificações Push**: Monitora a criação ou atribuição de chamados (tickets) a técnicos e dispara alertas em tempo real no smartphone correspondente via **Expo Push API**.
3. **Gerenciamento de Dispositivos**: Permite que os administradores visualizem os técnicos com dispositivos vinculados e revoguem o acesso/token individualmente caso necessário.
4. **Log de Notificações**: Registro completo do status de envio de notificações, incluindo data, destinatário, mensagem e a resposta retornada pela API do Expo, útil para debug.

---

## 🗄️ Modelagem do Banco de Dados

Ao clicar em "Instalar" no GLPI, o plugin cria automaticamente três tabelas:

### 1. `glpi_plugin_fluxionotify_configs`
Armazena os parâmetros globais de conexão que serão transmitidos ao aplicativo móvel.
- `id` (int): ID único (sempre `1`).
- `api_url` (varchar): URL base do servidor GLPI.
- `app_token` (varchar): App-Token da API REST do GLPI.
- `client_id` (varchar): Client ID configurado no Cliente de API do GLPI (OAuth2).
- `client_secret` (varchar): Client Secret configurado no Cliente de API do GLPI (OAuth2).

### 2. `glpi_plugin_fluxionotify_pushtokens`
Armazena os tokens de notificação móvel vinculados a cada usuário.
- `id` (int): ID único autoincrementado.
- `users_id` (int): ID do usuário/técnico no GLPI (Único).
- `pushtoken` (varchar): Token gerado pelo Expo no dispositivo móvel.

### 3. `glpi_plugin_fluxionotify_logs`
Guarda o histórico dos disparos de notificações push.
- `id` (int): ID único autoincrementado.
- `date_creation` (datetime): Data e hora do disparo.
- `tickets_id` (int): ID do chamado que acionou o envio.
- `users_id` (int): ID do técnico destinatário da notificação.
- `title` (varchar): Título enviado na notificação.
- `message` (text): Corpo do texto (normalmente o título do chamado).
- `status` (varchar): Status do envio (`success` ou `error`).
- `response` (text): Resposta HTTP bruta retornada pelo serviço Expo.

---

## 🪝 Hooks de Integração GLPI

O plugin utiliza os hooks padrões de ciclo de vida do GLPI 11 registrados em `setup.php`:

* **`csrf_compliant`**: Registrado como compatível com tokens CSRF do GLPI 11 para segurança.
* **`config_page`**: Direciona o painel de configurações para `front/config.php`.
* **`menu_toadd`**: Adiciona o menu do plugin sob a árvore de configurações do sistema.
* **`item_add` para `Ticket`**: Dispara a função `plugin_fluxionotify_item_add_ticket()` em `hook.php` sempre que um chamado for adicionado.

---

## 📁 Estrutura de Arquivos

```
FluxIO Notify_Plugin/
├── front/
│   ├── config.php            # Controlador principal que processa ações (salvar, revogar, limpar logs)
│   ├── reflect.php           # Script auxiliar de redirecionamento ou reflexão de estado
│   └── teste.php             # Arquivo de teste de ambiente
├── inc/
│   ├── config.class.php      # Exibição visual e tabs (Configuração, Tokens de Push e Logs)
│   ├── notification.class.php# Motor de envio de Push (CURL HTTP para exp.host) e gravação de logs
│   └── pushtoken.class.php   # Modelo de tabela e travas de segurança/permissão de usuários (CommonDBTM)
├── hook.php                  # Rotinas de instalação/desinstalação (DDL SQL) e gatilho do Ticket Hook
└── setup.php                 # Arquivo central de registro do plugin e metadados no GLPI
```

---

## 🚀 Como Instalar e Configurar no GLPI

### Passo 1: Copiar os arquivos
Copie a pasta `FluxIO Notify_Plugin` para o diretório de plugins do seu GLPI, renomeando a pasta para `fluxionotify`:
```bash
# O caminho final deve ser:
[DIRETÓRIO_GLPI]/plugins/fluxionotify/
```

### Passo 2: Instalar no GLPI
1. Faça login como **Super-Admin** no painel web do GLPI.
2. Acesse **Administração > Plugins** (ou **Configurar > Plugins** dependendo da tradução).
3. Na linha do plugin **FluxIO Notify**, clique no ícone de **Instalar** (que executará o script de banco em `hook.php`).
4. Clique no ícone de **Ativar** (play verde).

### Passo 3: Configurar os Parâmetros
1. Acesse o menu de configurações do plugin (geralmente sob **Configurar > Plugins > FluxIO Notify**).
2. Na aba **Configuração**, preencha os campos:
   - **URL Base do GLPI**: Ex: `https://meu-glpi.empresa.com/` (sem a barra final, idealmente).
   - **App-Token da API**: O token obtido em *Configurar > Geral > API*.
   - **Client ID & Client Secret**: Gerados no módulo de Clientes de API (OAuth2) do seu GLPI para o login do aplicativo móvel.
3. Clique em **Salvar Configurações**.
4. O sistema irá exibir o **QR Code**. Agora, abra o aplicativo FluxIO no celular e faça a leitura deste QR Code para parear o dispositivo.
