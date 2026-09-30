# Changelog

Mudanças relevantes para entender e manter o contrato da integração.

## 0.1.0-rc.1 — 2026-09-30

Primeira versão candidata do CrossHub. Os testes cobrem os cenários documentados; a homologação das APIs e dos mapeamentos depende de um ambiente controlado com os CRMs.

### Integração

- Webhooks para sincronização de leads, contatos e negócios entre Bitrix24 e Exact Sales.
- Fluxos de aplicação, normalização, portas HTTP/logs e adaptadores com composição explícita.
- Configuração por ambiente e seleção de mapeamento privado por `BTX_MAPPINGS_FILE`.
- Namespace `CrossHub` e opções locais `CROSSHUB_ENV_FILE` e `CROSSHUB_PHP_IMAGE`.
- Corpos de requisição serializados com `json_encode`.

### Privacidade e operação

- Contas, unidades, regiões e canais específicos representados por exemplos genéricos.
- Credenciais, mapeamentos pessoais, logs e backups fora da árvore versionada.
- Logs circulares ou `stderr`, com mascaramento das credenciais configuradas.
- Hook e auditoria para arquivos privados, padrões de credenciais e e-mails de autoria em commits e tags.

### Verificação e documentação

- Cenários de regressão dos clientes de integração e dos dois webhooks com dados fictícios.
- Testes de ambiente, configuração, cURL em loopback, rotação concorrente e bloqueio de arquivos internos.
- CI para PHP 8.3 e 8.4, sem acesso aos CRMs e sem credenciais de operação.
- Guias de integração, arquitetura, operação, segurança, contribuição e versões.

O processamento é síncrono. A versão não implementa fila, repetição automática, reconciliação em lote ou garantia de idempotência.
