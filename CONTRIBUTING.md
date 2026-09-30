# Como mantenho o projeto

Configure `user.name` com seu nome e `user.email` com o endereço noreply da sua conta GitHub neste clone. O hook confere autor e committer; a CI também verifica os metadados dos commits e das tags. Use `git config --local` para manter essa configuração limitada ao projeto.

Quero mudanças pequenas, com motivo claro e comportamento verificável. Uma alteração interna não deve mudar silenciosamente campos, eventos ou sequência de chamadas da integração.

## Preparar uma cópia

```sh
git config --local core.hooksPath .githooks
sh tools/php tools/lint.php
sh tools/php tests/run.php
python3 tests/test_secret_guard.py
```

Use sua própria identidade Git. Para proteger seu endereço pessoal, configure o e-mail `noreply` fornecido pelo GitHub antes de criar commits e tags. O hook não é instalado automaticamente pelo clone.

## Commits semânticos

Uso [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/), com descrição direta em português:

```text
tipo(escopo): descreve o efeito da mudança
```

| Tipo | Quando usar |
|---|---|
| `feat` | Capacidade nova ou importação inicial de uma parte funcional. |
| `fix` | Correção de comportamento ou proteção. |
| `refactor` | Organização interna sem mudança intencional de comportamento. |
| `test` | Cenários e verificações. |
| `docs` | Explicação de contrato, operação ou decisão. |
| `ci` | Automação de validação. |
| `chore` | Manutenção de repositório e ferramentas. |

Escopos possíveis: `integracao`, `config`, `logs`, `seguranca`. O escopo é opcional. Prefiro dizer o que muda, como `fix(config): rejeita um mapeamento privado inexistente`, em vez de uma descrição genérica de ajustes.

Uma quebra de contrato precisa ser explícita no commit e no changelog. Não reescreva o histórico publicado para esconder correções; acrescente um commit com o motivo.

## Antes de abrir ou atualizar uma PR

1. Crie uma branch a partir da base adequada, usando `feat/`, `fix/` ou `docs/`.
2. Implemente a mudança e atualize os cenários relevantes. Use dados fictícios.
3. Execute lint, testes PHP e o teste do hook.
4. Revise o diff e rode `python3 tools/check-secrets.py --staged` e `python3 tools/check-secrets.py --history` antes do push.
5. Descreva problema, comportamento resultante, validação e limites. Mudanças de integração precisam indicar o efeito nas chamadas externas.

O modo `--staged` lê o conteúdo de todo o índice, inclusive arquivos forçados. `--history` examina as versões de arquivos alcançáveis pelas branches e tags locais. A CI usa checkout completo para repetir essa auditoria; ela não recebe credenciais de operação.

## Tags e versões

Uso a estrutura de [Semantic Versioning](https://semver.org/) para identificar entregas. Enquanto o projeto está em `0.x`, cada tag continua exigindo documentação dos efeitos no contrato. `rc.N` marca uma versão candidata, ainda em revisão.

`v0.1.0-rc.1` identifica uma versão candidata. Tags são anotadas e não devem ser movidas depois de publicadas. Os critérios para novas versões estão em [Versões](docs/VERSOES.md).

A aprovação de uma PR, a validação nos CRMs e a implantação são etapas diferentes. Registre somente o que foi efetivamente verificado.
