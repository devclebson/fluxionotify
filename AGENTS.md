# FluxIO Notify — guia de trabalho

## Escopo

Este repositório contém o plugin FluxIO Notify para GLPI. Ele registra tokens de push e processa notificações de Ticket, acompanhamento e tarefa, preservando suporte a GLPI 10 e GLPI 11.

## Fonte de regras

As regras comuns estão em [FluxIO Governance](https://github.com/devclebson/fluxio-governance). Este arquivo contém somente convenções locais do plugin.

## Estrutura atual

- `setup.php` e `hook.php`: ciclo de vida e hooks GLPI.
- `inc/`: modelos, configuração e motor de notificações.
- `front/`, `ajax/`, `src/`: superfícies HTTP e controller existentes.
- `tests/`: harnesses locais sem rede real.
- `docs/`: documentação canônica do plugin.

## Invariantes locais

- Não reduzir o suporte a GLPI 10 ou GLPI 11 sem decisão registrada, evidência e migração explícita.
- Follow-ups e tarefas privados ou de visibilidade desconhecida permanecem bloqueados antes de ler conteúdo, atores ou acionar transporte.
- Não ampliar destinatários para grupos ou perfis sem evidência de ACL por destinatário e validação real.
- Não registrar ou devolver token push, App-Token, Bearer token, QR completo, segredo OAuth ou conteúdo privado.
- Todo endpoint de token deve respeitar autenticação e autorização GLPI; não criar atalhos com usuário informado pelo cliente.

## Mudanças e validação

- Trabalhar em branch e PR para `develop`; versão/release somente antes de PR para `master`.
- Executar PHP lint quando houver runtime disponível e o harness de diagnósticos sem rede.
- Distinguir teste isolado de homologação GLPI 10/11 por perfil.
- Atualizar `docs/` ao alterar hook, endpoint, permissão, destinatário, QR, log, retenção ou instalação.
