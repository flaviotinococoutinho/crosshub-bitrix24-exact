# Dados privados e publicação

Credenciais, nomes reais de pessoas nos mapeamentos, dados de clientes, logs e backups não devem ser versionados. Essa regra se aplica independentemente da visibilidade do repositório.

## Configuração

Use variáveis do processo ou `.env` para tokens e URLs autenticadas. Use `config/*.local.*` ou arquivos externos para mapeamentos privados, selecionados por `BTX_MAPPINGS_FILE`. A referência de mapeamento versionada substitui nomes de pessoas por exemplos.

`.gitignore` exclui esses arquivos, chaves, dados de execução, cópias antigas e arquivos da máquina. `.env.example` contém apenas placeholders e endpoints dos fornecedores. IDs técnicos de campos e pipelines e nomes de canais comerciais também precisam de revisão de privacidade: não são credenciais, mas podem identificar uma instalação.

## Proteção antes do envio

Ative o hook com `git config --local core.hooksPath .githooks`. O verificador bloqueia arquivos operacionais e privados, inclusive quando adicionados à força, e procura credenciais locais conhecidas e padrões de URLs autenticadas, tokens e chaves privadas. Ele informa o caminho e o motivo, sem imprimir o valor.

```sh
python3 tools/check-secrets.py --staged
python3 tools/check-secrets.py --history
```

A segunda verificação procura o conteúdo também nos commits anteriores alcançáveis pelas referências locais e confere os e-mails de autor, committer e autor de tags. O hook também verifica a identidade do próximo commit. Nas [configurações de e-mail do GitHub](https://github.com/settings/emails), ative `Keep my email addresses private` para que edições, merges e commits de teste de PR criados pelo serviço também usem noreply. Use o e-mail `noreply` fornecido pelo GitHub; o endereço automático do GitHub e identidades fictícias em `example.invalid` são aceitos para CI e testes. Remover uma credencial do arquivo atual não a remove do histórico. A CI repete as verificações sem receber o `.env` real.

O scanner não reconhece todo tipo de dado privado. A revisão manual continua necessária, incluindo nomes comerciais, dados pessoais, mensagens e anotações, descrições de PR, anexos e artefatos. A limpeza de branches e tags também não garante a remoção de referências internas de PR ou de visualizações em cache no GitHub.

## Operação

Os logs mascaram as credenciais configuradas, mas podem conter nomes, e-mails, telefones e outros dados recebidos dos CRMs. A retenção circular limita o espaço, não anonimiza essas informações. Proteja o diretório e restrinja sua coleta ao necessário.

Os arquivos `.htaccess` e o roteador de desenvolvimento restringem o acesso aos arquivos internos. A configuração efetiva do servidor precisa ser verificada na implantação, incluindo a proteção das entradas de webhook. A aplicação não implementa validação própria de assinatura dos webhooks.

## Se encontrar uma exposição

Interrompa a publicação do dado e use o [relato privado de vulnerabilidade](https://github.com/flaviotinococoutinho/crosshub-bitrix24-exact/security/advisories/new), sem copiar o valor para issue, PR ou log. Se uma credencial tiver sido publicada, sua remoção do código não substitui revogação e rotação. A limpeza de histórico, quando necessária, deve ser planejada com backup e coordenação das cópias existentes.

Backups podem conter material privado e devem permanecer fora deste repositório. Não os anexe a discussões ou relatórios públicos.
