# CrossHub — Bitrix24 e Exact

[![Validar integração](https://github.com/flaviotinococoutinho/crosshub-bitrix24-exact/actions/workflows/ci.yml/badge.svg)](https://github.com/flaviotinococoutinho/crosshub-bitrix24-exact/actions/workflows/ci.yml)

Integração entre **Bitrix24 e Exact Sales**, desenvolvida em PHP. O CrossHub recebe webhooks, traduz os dados de cada CRM e coordena a criação, consulta e atualização de leads, contatos e negócios.

O projeto usa portas e adaptadores para separar os fluxos comerciais do transporte HTTP e dos logs. A configuração vem do ambiente, e os testes verificam as requisições sem acessar os CRMs. Mantido por **Flávio Tinoco**.

## O que a integração faz

Integração, aqui, é a tradução de um evento de uma ferramenta em consultas e alterações na outra. Cada CRM mantém seus próprios IDs e regras; o CrossHub faz a correspondência entre eles.

| Entrada | Fluxo |
|---|---|
| `btxincoming/index.php` | Recebe dados de um lead do Bitrix, normaliza os campos e solicita sua criação na Exact. Conforme a resposta e a etapa, recupera/atualiza o lead Exact ou descarta o lead Bitrix correspondente. |
| `exactincoming/index.php` | Recebe eventos da Exact, atualiza o lead Bitrix e consulta contatos e negócios para reutilizá-los ou criá-los conforme o evento. |

O processamento é síncrono. O projeto não implementa filas, repetição automática, reconciliação em lote ou garantia de entrega exatamente uma vez. Os [contratos e limites dos fluxos](docs/INTEGRACAO.md) fazem parte da documentação.

## Estrutura

```text
btxincoming/ e exactincoming/  Entradas HTTP dos webhooks
bootstrap.php                 Configuração e composição das dependências
config/                       Mapeamentos de campos, origens e estágios
src/Application/              Coordenação dos dois fluxos
src/Domain/                   Normalização dos dados
src/Integration/              Tradução do contrato de cada CRM
src/Ports/                    Contratos HTTP e de logs
src/Adapters/                 cURL, arquivo circular e stderr
src/Configuration/            Ambiente e validação de configuração
src/Http/ e src/Logging/       Valores HTTP, retenção e mascaramento
tests/                        Regressão e cenários sem CRMs reais
tools/                        Execução local e verificações
docs/                         Integração, arquitetura, operação e versões
storage/                      Apenas marcadores; dados gerados fora do Git
```

Usei ports and adapters nas fronteiras de HTTP e logs, dependências explícitas e um vocabulário comum para lead, contato, negócio, conta e evento. Object Calisthenics e os 12 fatores orientam as escolhas que cabem neste tamanho de projeto. Mantive a composição manual, sem framework ou dependências de terceiros. As decisões e concessões estão em [Arquitetura](docs/ARQUITETURA.md).

## Validar localmente

Clone o projeto e entre na pasta:

```sh
git clone https://github.com/flaviotinococoutinho/crosshub-bitrix24-exact.git
cd crosshub-bitrix24-exact
```

Configure sua identidade Git com o e-mail noreply do GitHub, conforme [Contribuição](CONTRIBUTING.md), e execute as verificações:

```sh
git config --local core.hooksPath .githooks
sh tools/php tools/lint.php
sh tools/php tests/run.php
python3 tests/test_secret_guard.py
python3 tools/check-secrets.py --staged
python3 tools/check-secrets.py --history
```

Os testes usam dados fictícios e servidores HTTP em loopback. Não precisam de `.env` nem acessam os CRMs. `tools/php` usa PHP instalado ou uma imagem PHP já disponível no Docker local, sem baixar ferramentas. A CI executa a suíte em PHP 8.3 e 8.4 e verifica o índice e o histórico Git.

Para executar a integração, configure as extensões PHP e o ambiente conforme o [guia de operação](docs/OPERACAO.md). A matriz de validação cobre PHP 8.3 e 8.4. O requisito `>=5.6` do manifesto, por si só, não comprova funcionamento nas versões fora dessa matriz.

## Dados privados e logs

Credenciais ficam no ambiente ou em `.env` ignorado. O mapeamento com nomes reais de pessoas fica em arquivo local ignorado, selecionado por `BTX_MAPPINGS_FILE`; a referência versionada usa nomes fictícios. `.env.example` contém exemplos sem credenciais válidas.

Logs e backups não acompanham o repositório. Os logs podem usar arquivos circulares configuráveis ou `stderr`. O mascaramento protege as credenciais configuradas; os eventos ainda podem conter dados pessoais dos CRMs. A [política de segurança](SECURITY.md) descreve essas fronteiras.

Os nomes de contas, unidades, regiões e canais específicos usam exemplos genéricos. Os mapeamentos de campos, categorias, estágios e canais dependem da instalação. Revise a configuração e os contratos antes de conectar uma conta real.

## Documentação

- [Integração](docs/INTEGRACAO.md): entradas, sequência de chamadas e cenários de referência.
- [Arquitetura](docs/ARQUITETURA.md): abstrações, ontologia e dependências.
- [Operação](docs/OPERACAO.md): configuração, execução, logs e implantação.
- [Versões](docs/VERSOES.md): tags, versões candidatas e critérios de validação.
- [Contribuição](CONTRIBUTING.md): commits semânticos, PRs e verificações.
- [Changelog](CHANGELOG.md): versão candidata e ajustes intencionais.

`v0.1.0-rc.1` identifica uma versão candidata à revisão. Os testes verificam os cenários documentados; a homologação das APIs e da configuração de uma instalação depende de validação com os CRMs em ambiente controlado.

Código mantido como proprietário, conforme `composer.json`. Não há concessão de licença de uso aberto.
