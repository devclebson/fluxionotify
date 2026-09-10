# FluxIO Notify 1.0.2 — pacote de homologação

## Estado

Preparado na branch `fix/push-diagnostics-1.0.2`, preservando a documentação de instalação anterior. Sem push ou implantação no servidor. Código anterior equivalente a origin/main; diferenças locais anteriores eram documentação. Esta versão não comprova entrega de push em GLPI 10/11.

## Alterações

- Versão 1.0.2; mínimo GLPI 10.0.0 mantido, sem remover integração GLPI 11.
- Quatro callbacks carregam a classe via `__DIR__`, independentemente de plugins/marketplace.
- Diagnósticos `skipped`, exibidos como **Não enviado**, sem conteúdo ou token: `author_ignored`, `no_push_token`, `no_eligible_actors`.
- Deduplicação por usuário; tokens vazios/nulos não geram envio.
- Acompanhamentos e tarefas só enviam conteúdo quando `is_private` é explicitamente 0, '0' ou false. Eventos privados, valor ausente/nulo ou desconhecido são bloqueados antes da leitura de título/atores e do transporte, registrando `private_or_unknown_visibility` com IDs e texto fixo. Não são enviados nem mesmo ao técnico. Política conservadora autorizada pelo usuário; ACL individual futura é trabalho separado.
- Não amplia destinatários para grupos nem modifica permissões ou endpoints.

## Evidência e limites

No GLPI 10, token de tech foi gerado e persistido. No chamado #14, requerente post-only e técnico tech diretamente atribuído confirmados visualmente; respostas recentes sem cadeado visível. Não atribuir ausência de envio a autor/grupo sem nova evidência. Execução do hook instalado e entrega permanecem sem confirmação.

Teste local: `wsl.exe -d Ubuntu -- php -n /mnt/c/projetos/fluxionotify/tests/push-diagnostics.php`: 10 testes, 0 falhas. Teste de privacidade observado RED antes da correção, GREEN após. Doubles de DB/Session/cURL; nenhum envio real. Teste Node de identificadores aprovado. PHP CLI instalado no Ubuntu WSL local para testes, não no servidor.

Logs de tentativas existentes ainda armazenam título/mensagem e resposta Expo; não publicar esses logs sem redação. Novos skipped são minimizados. `success` significa aceite inicial Expo, não entrega no dispositivo. Falhas PHP/cURL ou inserção no banco podem impedir logs.

## Atualização segura 1.0.1 → 1.0.2

1. Fazer backup da pasta do plugin e do banco GLPI.
2. Desativar temporariamente o plugin. **Não desinstalar**: uninstall apaga tabelas, tokens e configurações.
3. Extrair o ZIP: contém uma única pasta `fluxionotify/`. Substituir arquivos no diretório efetivamente usado pelo servidor, sem criar `fluxionotify/fluxionotify` e sem manter cópias ativas em plugins e marketplace.
4. Preservar proprietário/permissões do ambiente. Usar a atualização oferecida pelo gerenciador GLPI caso apareça, depois habilitar.
5. Confirmar versão 1.0.2 na interface, configurações e token de tech preservados. Se código antigo persistir, solicitar ao administrador limpeza/reinício do OPcache conforme o ambiente.
6. Executar testes abaixo antes de considerar a versão homologada. Não criar novo AAB como parte desta atualização.

## Checklist pendente por GLPI 10 e GLPI 11

- [x] GLPI 10: plugin 1.0.2 atualizado/habilitado pelo usuário; o caminho ativo observado é `plugins/fluxionotify`.
- [x] GLPI 10: token/configuração preservados e a tabela de logs recebeu novas gravações.
- [x] GLPI 10: alteração pública de status no chamado #14, com post-only e tech diretamente atribuído: Expo aceitou o envio (`Sucesso`/`Enviado`) e a notificação apareceu no tablet em 2026-09-09. A abertura do chamado ao tocar a notificação ainda não foi evidenciada.
- [ ] Acompanhamento privado e tarefa privada sintéticos: nenhum conteúdo enviado; motivo private_or_unknown_visibility, sem título/conteúdo/token no log skipped.
- [ ] Criação/atualização de Ticket, acompanhamento público e tarefa pública.
- [ ] Autor ignorado, destinatário sem token e ausência de atores geram diagnóstico esperado.
- [ ] Repetir por Self-Service, técnico e administrador com evidência de sessão e vínculo; sem inferir permissões por nome.
- [ ] Se não houver logs: conferir hook ativo, erros PHP/GLPI, extensão cURL e banco; ausência de logs não prova ausência de evento.

Nenhuma validação de servidor acima foi executada localmente. Testes PHP isolados não substituem homologação GLPI 10/11.
