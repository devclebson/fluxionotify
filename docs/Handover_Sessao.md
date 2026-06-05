# Documentação de Status do Projeto - Plugin iFlux (GLPI)
**Data/Hora:** 05/06/2026
**Objetivo:** Registro da análise do ecossistema e das implementações feitas no plugin PHP.

---

## 🛠 O que foi feito nesta sessão no Plugin iFlux

### 1. Análise de Arquitetura e Segurança
- Exploramos a fundo a nova estrutura do `iflux_plugin`.
- Constatamos que ele possui uma arquitetura excelente para o GLPI 11:
  - **Notificações em 360 Graus:** O plugin utiliza os Hooks nativos (`item_add_ticket`, `item_update_ticket`, `item_add_followup`, `item_add_task`) para notificar ações, englobando todo o ciclo de vida do chamado.
  - **Prevenção de Auto-Feedback:** Possui uma verificação robusta (`if ($userId == $authorId) { continue; }`) que garante que um técnico não receba no celular uma notificação de uma ação que ele mesmo gerou no painel Web.
  - **Logs e Auditoria:** Foi mapeada a tabela `glpi_plugin_iflux_logs`, que rastreia tudo o que a API do Expo respondeu sobre cada envio Push.
  - **Resiliência:** A conexão `cURL` implementa `CURLOPT_TIMEOUT` de 3 segundos, evitando o congelamento do GLPI caso os servidores do Expo caiam.

### 2. Implementações Novas (Integração Push Actions)
- Para suportar os superpoderes do Aplicativo Mobile, modificamos o arquivo de envios de notificação (`iflux_plugin/inc/notification.class.php`).
- A injeção nativa de Categorias Push da Apple/Google foi adicionada.
- Quando um chamado é da categoria `sendForTicket` (Chamado Novo), o plugin agora injeta a chave `"categoryId": "TICKET_NEW"` no payload JSON do Expo.
- Isso ativa o botão secreto de "Ação Rápida" (ex: "Assumir Chamado") diretamente na tela de bloqueio dos celulares dos técnicos.

---

## 🎯 Próximos Passos (Para Amanhã)

Ao assumir a posição no computador da empresa, observe:

1. **Reinstalação/Limpeza de Cache no GLPI:** 
   Como mexemos diretamente na classe `notification.class.php`, garantir que os caches do PHP/GLPI foram limpos ou desativar e reativar o plugin (via painel Super-Admin) para garantir a compilação atualizada do arquivo se necessário.
2. **Homologação Push Actions:**
   Criar um chamado de teste e observar na tabela `glpi_plugin_iflux_logs` se os retornos estão indo com sucesso e se os celulares estão ativando o modal estendido de notificação corretamente (com o botão "Assumir").
3. **Task Recomendada:**
   Criar no GLPI uma `AutomaticAction` futura para varrer e excluir logs velhos de notificação ou tokens inativos ("DeviceNotRegistered") da tabela, evitando o inchaço do banco de dados a longo prazo.
