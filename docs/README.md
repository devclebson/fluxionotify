# Documentação do FluxIO Notify

## Objetivo

Este diretório é a fonte canônica da documentação específica do plugin. As regras compartilhadas ficam em [FluxIO Governance](https://github.com/devclebson/fluxio-governance).

## Índice atual

| Documento | Finalidade | Estado |
| --- | --- | --- |
| `architecture.md` | Arquitetura, hooks, tabelas e fluxo de push. | Ativo; revisar quando endpoints e destinatários forem consolidados. |
| `release-1.0.2.md` | Pacote de homologação e limites conhecidos. | Histórico operacional ativo. |
| `rename-fluxionotify-plan.md` | Plano e rastreabilidade da migração de nome. | Histórico técnico; não usar para alterar protocolos sem revisão. |
| `Handover_Sessao.md` | Contexto de transição de sessão. | Referência histórica; validar antes de executar instruções. |
| `../AGENTS.md` | Convenções operacionais locais. | Ativo. |

## Documentos a criar quando o primeiro conteúdo for aprovado

- `api-and-auth/`: endpoint suportado, autenticação, autorização e ciclo de token.
- `validation/`: matriz GLPI 10/11, perfil, evento, visibilidade e entrega.
- `operations/`: instalação, atualização, backup, retenção de logs e rollback.
- `decisions/`: QR, destinatários, privacidade e política de logs.

Não criar diretórios vazios. Cada documento novo deve apontar para código, teste, decisão ou evidência sanitizada.

## Estado de evidência

Registrar de forma separada: documentado, testado localmente, validado em GLPI 10, validado em GLPI 11, pendente ou bloqueado. Aceite do Expo não é prova de entrega no dispositivo.
