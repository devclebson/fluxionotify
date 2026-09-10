# Inventário de endpoints e autenticação de token push

- Projeto: FluxIO Notify
- Estado: levantamento derivado do código em 2026-09-10. Não aprova, amplia nem remove endpoints.
- Objetivo: identificar a superfície atual antes de qualquer consolidação de API, QR, OAuth, logs ou autorização.

## Consumidor observado

O app FluxIO atual usa o recurso `PluginFluxionotifyPushtoken` no serviço de usuário (`fluxio/src/services/UserService.ts`). Não foi encontrada referência direta do app às rotas `front/api.php`, `front/api_pushtoken.php`, `ajax/api.php` ou ao controller Symfony nesta inspeção. Isso não prova ausência de consumidores externos; antes de retirar uma rota, confirmar tráfego e instalações existentes.

## Superfícies identificadas

| Superfície | Arquivo/rota | Identidade usada | Estado de suporte | Observações e decisão provisória |
| --- | --- | --- | --- | --- |
| Controller GLPI | `src/Controller/PushTokenController.php`, `POST/PUT /fluxionotify/pushtoken` | Sessão GLPI; usuário vem de `Session::getLoginUserID()` | Candidato a caminho suportado | Não aceita `users_id` do corpo. Ainda requer validação de permissões, formato de token e compatibilidade real GLPI 10/11. |
| Recurso GLPI | `PluginFluxionotifyPushtoken` | Depende da API GLPI e contexto do consumidor | Integração observada do app | Mapear operação/payload por GLPI 10 e 11 antes de qualquer alteração. |
| API App-Token legada | `front/api.php` | App-Token, aceita `users_id` no corpo | Legada, não ampliar | Registra payload/resposta brutos e pode devolver dados recebidos em erro. Precisa sanitização e plano de migração antes de manutenção funcional. |
| API OAuth legada | `front/api_pushtoken.php` | Bearer validado consultando sessão GLPI | Legada, não ampliar | Desabilita verificação TLS e altera sessão temporariamente. Corrigir e mapear consumidores antes de desativar. |
| API de bypass | `ajax/api.php` | App-Token; `users_id` no corpo | Bloqueada para novo uso | Lê `config_db.php`, abre PDO próprio e evita controles GLPI. Não usar em novos fluxos; investigar consumidores e planejar retirada segura. |

## Regras para mudanças futuras

1. Não remover, expor ou redirecionar qualquer endpoint antes de inventariar consumidores reais, incluindo versões de app instaladas e instalações do plugin.
2. Nenhum caminho novo pode aceitar `users_id` como autoridade de identidade; o usuário deve vir de contexto autenticado GLPI/OAuth validado.
3. Aplicar validação de formato/tamanho de token push no caminho suportado e não devolver o valor do token em respostas.
4. Não registrar corpo bruto, cabeçalhos, App-Token, Bearer token, resposta remota crua ou segredo OAuth.
5. TLS deve ser validado; exceções temporárias devem ser tratadas como bloqueio de segurança, não solução de produção.
6. CORS, métodos HTTP e erros devem ser restringidos ao mínimo necessário após o mapeamento de consumidores.
7. Documentar estratégia de migração, prazo de compatibilidade e rollback antes de consolidar endpoints.

## QR e credenciais

A configuração/pareamento pode carregar material de autenticação para o app. Não tratar criptografia com chave estática como fronteira de segredo. A evolução para pareamento curto, de uso único e revogável é uma decisão de arquitetura separada e deve ser registrada antes de implementação.

## Evidência pendente

- Qual caminho cada versão distribuída do app usa para registrar/atualizar token.
- Resultado real para GLPI 10 e GLPI 11, por perfil e por rota suportada.
- Permissões mínimas do recurso/modelo de token.
- Política de retenção e acesso dos logs API/push.
- Plano de desativação dos caminhos legados, com fallback e rollback.

## Próxima ação recomendada

Produzir um registro de decisão que escolha a superfície oficialmente suportada e um plano de migração baseado em consumidores confirmados. Essa decisão deve anteceder qualquer remoção de `front/` ou `ajax/`.
