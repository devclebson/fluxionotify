# Plano de renomeação do plugin para FluxIO Notify

## Decisões de nomenclatura

- Aplicativo: `FluxIO`
- Plugin/repositório: `fluxionotify`
- Nome exibido no GLPI: `FluxIO Notify`
- Identificador técnico do plugin: `fluxionotify`
- Namespace PHP: `GlpiPlugin\\Fluxionotify`
- Prefixo de classes: `PluginFluxionotify`
- Prefixo de funções: `plugin_fluxionotify_*`
- Permissão GLPI: `plugin_fluxionotify`
- Rota técnica: `/fluxionotify/pushtoken`
- Recurso de API: `PluginFluxionotifyPushtoken`

## Identificadores a substituir

```text
iflux                 -> fluxionotify
IFLUX                 -> FLUXIONOTIFY
Iflux                 -> Fluxionotify
PluginIflux           -> PluginFluxionotify
plugin_iflux         -> plugin_fluxionotify
glpi_plugin_iflux_   -> glpi_plugin_fluxionotify_
/plugins/iflux       -> /plugins/fluxionotify
GlpiPlugin\\Iflux     -> GlpiPlugin\\Fluxionotify
```

Textos de interface e documentação:

```text
iFlux App Sync      -> FluxIO Notify
iFlux              -> FluxIO Notify quando se referir ao plugin
aplicativo iFlux   -> aplicativo FluxIO
```

## Arquivos críticos

- `setup.php`: entry points, hooks, classes e identificador do plugin.
- `hook.php`: instalação, desinstalação, tabelas, direitos e callbacks.
- `inc/*.class.php`: classes, direitos, textos e consultas.
- `front/*.php`: permissões, redirects, tabelas, rotas e QR Code.
- `ajax/api.php`: tabelas e autenticação da API.
- `src/Controller/PushTokenController.php`: namespace, classe, rota e recurso.
- `README.md` e `docs/*.md`: instalação, configuração e compatibilidade.

## Migração de dados

Como existem instalações de teste com tabelas antigas, o novo plugin deve:

1. detectar tabelas `glpi_plugin_iflux_*`;
2. renomeá-las ou migrar seus dados para `glpi_plugin_fluxionotify_*`;
3. preservar registros de tokens, configurações e logs;
4. migrar o direito `plugin_iflux` para `plugin_fluxionotify` quando aplicável;
5. evitar `DROP TABLE` durante a instalação/atualização;
6. somente remover nomes antigos após confirmação de migração.

A desinstalação do novo plugin não deve apagar dados antigos sem uma decisão explícita.

## Compatibilidade com o aplicativo

O aplicativo FluxIO precisará ser atualizado para consumir:

```text
PluginFluxionotifyPushtoken
/fluxionotify/pushtoken
```

O formato do QR Code deve permanecer compatível, salvo quando houver um campo específico contendo o nome do plugin. A chave de criptografia do QR Code não deve ser alterada nesta etapa.

## Validação obrigatória

- `php -l` em todos os arquivos PHP.
- Busca sem ocorrências técnicas antigas fora de documentação histórica explicitamente marcada.
- Verificação das funções `plugin_init_fluxionotify`, `plugin_version_fluxionotify`, `plugin_fluxionotify_install` e `plugin_fluxionotify_uninstall`.
- Verificação de tabelas e permissões novas.
- Instalação em GLPI 10.
- Instalação em GLPI 11.
- Geração de QR Code.
- Leitura pelo aplicativo FluxIO.
- Registro de push token.
- Hooks de ticket, follow-up e tarefa.
- Configuração e permissões administrativas.

## Ordem de execução

1. Criar testes/verificações estruturais.
2. Renomear identificadores técnicos do plugin.
3. Adicionar migração das tabelas/direitos antigos.
4. Atualizar textos e documentação do plugin.
5. Executar lint PHP e verificações estruturais.
6. Atualizar o aplicativo FluxIO.
7. Validar GLPI 10 e GLPI 11.
8. Criar commits separados por escopo.
9. Fazer push somente ao final, conforme o fluxo do projeto.
