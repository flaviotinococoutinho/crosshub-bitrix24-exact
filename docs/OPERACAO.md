# Operação e configuração

## Requisitos

Use PHP com `curl`, `json`, `mbstring` e `ctype`. A suíte é executada em PHP 8.3 e 8.4. O requisito `>=5.6` do manifesto não substitui a validação: versões fora da matriz de CI não são verificadas por este projeto.

O projeto usa autoload próprio e não precisa de `composer install`. Python 3 e Git são ferramentas de desenvolvimento usadas na proteção de publicação. O adaptador de arquivo precisa de permissão para escrever no diretório de logs.

## Ambiente

Em uma instalação nova, copie `.env.example` para `.env` e preencha os valores. Preserve um `.env` já existente. Restrinja a leitura do arquivo ao usuário que executa a aplicação.

Variáveis do processo prevalecem sobre o arquivo. `CROSSHUB_ENV_FILE` seleciona um arquivo de ambiente fora da pasta do projeto. O leitor não executa shell, não interpola variáveis e não expande comandos. Aspas simples e duplas apenas delimitam valores literais.

| Variável | Uso e padrão |
|---|---|
| `CROSSHUB_ENV_FILE` | Caminho do arquivo privado; por padrão, `.env` na raiz. Configure esta opção no processo. |
| `BTX_HOOK` | URL autenticada do Bitrix. Obrigatória; mantenha o formato sem uma barra final adicional. |
| `BTX_MAPPINGS_FILE` | Mapeamento Bitrix. Por padrão, `config/bitrix-mappings.php`; caminho relativo usa a raiz do projeto. |
| `EXACT_TOKEN_ALUGUEL` | Token da conta de aluguel. |
| `EXACT_TOKEN_VENDAS` | Token da conta de vendas. |
| `EXACT_TOKEN_CAPTACAO_LOCACAO` | Token da conta de captação para locação. |
| `EXACT_TOKEN_CAPTACAO_VENDAS` | Token da conta de captação para vendas. |
| `EXACT_TOKEN_ALUGUEL_ESPECIAL` | Token da conta específica de aluguel definida no contrato. |
| `EXACT_ADD_URL`, `EXACT_GET_URL` | Endpoints de criação e consulta. O endpoint de consulta mantém o sufixo `?id=`. |
| `EXACT_RECOVER_URL`, `EXACT_UPDATE_URL` | Endpoints de recuperação e atualização. |
| `APP_TIMEZONE` | Fuso dos eventos; padrão `America/Sao_Paulo`. |
| `HTTP_TIMEOUT_SECONDS` | Limite total por chamada; padrão `0`, sem limite explícito. |
| `BTX_SSL_VERIFY_PEER` | Verificação do certificado no cliente Bitrix; padrão `false`. |
| `LOG_DRIVER` | `file` ou `stderr`; padrão `file`. |
| `LOG_DIR` | Diretório de novos logs; padrão `storage/logs`. Caminho relativo usa a raiz do projeto. |
| `LOG_MAX_BYTES` | Limite por arquivo; padrão `10485760` (10 MiB), mínimo `256`. |
| `LOG_BACKUP_COUNT` | Cópias anteriores por canal; padrão `5`, mínimo `0`. |

Todos os tokens listados são exigidos pela composição atual. Os valores de exemplo não são credenciais válidas. Antes de conectar os CRMs, defina um timeout finito e habilite a verificação TLS, validando os certificados do ambiente.

`BTX_MAPPINGS_FILE` carrega um arquivo PHP de configuração confiável, controlado pelo operador. Para dados privados, mantenha uma cópia em `config/bitrix-mappings.local.php` ou fora da raiz pública e aponte a variável para ela. Arquivos `config/*.local.*` são ignorados pelo Git. A versão de referência usa nomes fictícios para as correspondências pessoais.

## Verificação e desenvolvimento

```sh
sh tools/php tools/lint.php
sh tools/php tests/run.php
sh tools/php tools/doctor.php
python3 tests/test_secret_guard.py
python3 tools/check-secrets.py --staged
python3 tools/check-secrets.py --history
```

O doctor verifica extensões, carrega a configuração e confere o destino de logs sem fazer chamadas aos CRMs nem imprimir credenciais. Os testes usam o ambiente fictício de `tests/Fixtures/` e diretórios temporários.

`tools/php` usa PHP local quando disponível. Na ausência dele, reutiliza uma imagem já instalada em um daemon Docker local, copia o projeto para um container temporário sem rede e exclui Git, backups operacionais e logs locais da cópia. O `.env` pode ser lido nesse container para o doctor; ele não é enviado a um serviço externo. O container é removido ao terminar.

Para selecionar outra imagem já disponível:

```sh
CROSSHUB_PHP_IMAGE=php:8.4-cli-alpine sh tools/php tests/run.php
```

Com PHP local, o servidor de desenvolvimento pode ser iniciado por:

```sh
php -S 127.0.0.1:8080 tools/dev-router.php
```

Esse roteador libera apenas a raiz e as duas entradas de webhook. Uma chamada às entradas com credenciais reais pode alterar registros externos. A suíte automatizada mantém essas operações simuladas.

## Logs circulares

O modo `file` cria um arquivo atual por canal e até `LOG_BACKUP_COUNT` cópias anteriores. Com o padrão, são até seis arquivos de 10 MiB por canal, além da trava. A rotação conserva as mensagens mais recentes e usa uma trava estável para coordenar processos concorrentes.

Um registro maior que o limite é truncado com indicação. Reduzir a retenção elimina as cópias excedentes na próxima escrita. Falhas na escrita do log não interrompem o processamento do webhook. Logs e backups devem permanecer fora do repositório.

O modo `stderr` encaminha os eventos para o ambiente, que passa a ser responsável pela coleta, rotação e retenção. Credenciais configuradas são mascaradas em ambos os modos; dados pessoais nos eventos não são anonimizados automaticamente. O registro de eventos na lista do Bitrix continua independente desses arquivos.

## Implantação

Publique somente as entradas HTTP necessárias e mantenha configuração, fontes, testes, ferramentas e dados fora do acesso público. No Apache, as regras `.htaccess` incluídas dependem da configuração efetiva de `AllowOverride` e dos módulos correspondentes. Nginx não interpreta esses arquivos: configure explicitamente as três rotas permitidas e bloqueie os outros caminhos.

Antes de usar uma versão em uma instalação real, valide a matriz de extensões, a configuração do servidor, os mapeamentos, as permissões de escrita e os fluxos dos dois CRMs em ambiente controlado. A CI não executa homologação externa nem deploy.

Para recuperar uma instalação, use a versão Git conhecida e a configuração privada correspondente. Reverter código não desfaz alterações já enviadas aos CRMs. Preserve backups e configurações privadas fora do repositório.
