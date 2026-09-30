# Contrato da integração

O CrossHub recebe eventos de um CRM e coordena operações no outro. Os dados chegam pelas duas entradas HTTP; as traduções ficam em `src/Integration/` e `config/`.

## Bitrix24 → Exact

Entrada: `btxincoming/index.php`. O script lê os parâmetros de query e entrega o array a `Application/BitrixWebhook`.

Os parâmetros incluem identificação do lead (`id`), pessoa e empresa (`nome`, `sobrenome`, `nome_empresa`, `email`), telefones (`tel_work`, `tel_celular`, `tel_outro`), imóvel (`cod_imovel`), conta/origem (`tipo_lead`, `tipo_origem`) e informações de qualificação. A lista executável de campos está nos cenários de [webhooks](../tests/Fixtures/webhook-scenarios.php).

1. Normaliza nome e e-mail, traduz enumerações e monta as observações.
2. Solicita a criação de um lead na Exact usando a credencial da conta.
3. Lê o ID retornado, inclusive a resposta de lead já existente, e consulta sua etapa quando aplicável.
4. Se a criação não teve sucesso e o lead está descartado, solicita recuperação e atualização na Exact.
5. Se a criação não teve sucesso e o lead já está em uma das etapas previstas, descarta o lead Bitrix correspondente conforme a regra do fluxo.
6. Registra o resultado na lista do Bitrix e no destino de logs configurado.

Os nomes de etapas e as condições exatas ficam em `Application/BitrixWebhook.php`. Esse fluxo não faz uma atualização genérica para qualquer resposta ou etapa recebida.

## Exact → Bitrix24

Entrada: `exactincoming/index.php`. O script lê `tipo` na query e o JSON do corpo HTTP, entregando ambos a `Application/ExactWebhook`.

O corpo usa `Event`, `Lead` e, quando presente, `Agendamento`. Os dados do lead incluem contatos, vendedor, pré-vendedor, links, etapas de qualificação e campos personalizados. Os tipos de conta aceitos são definidos nos mapeamentos e nos cenários de webhook.

1. Extrai o ID Bitrix e o código do imóvel dos campos personalizados correspondentes à conta.
2. Serializa perguntas e respostas e solicita a atualização do lead Bitrix.
3. Consulta um contato pelo ID Exact. Se não existir, cria o contato nos eventos previstos.
4. Consulta um negócio pelo ID Exact. Atualiza o existente ou cria um novo nos eventos previstos.
5. Registra o resultado na lista do Bitrix e no destino de logs configurado.

Os eventos de criação previstos no fluxo são `event.schedule`, `event.leadqualified` e `event.leadwon`. Os mapeamentos de estágio também tratam reagendamento, cancelamento e perda. A presença de um evento no fluxo não garante que todo pipeline tenha um estágio configurado para ele; o arquivo de mapeamentos é a referência para cada operação.

## Requisições de saída

| Cliente | Operações |
|---|---|
| Bitrix | Consultar contato/negócio, criar contato/negócio, atualizar lead/negócio, descartar lead e registrar evento de integração. |
| Exact | Criar, consultar, recuperar e atualizar lead. |

`Integration/Bitrix` define os métodos REST e os campos personalizados utilizados. `Integration/Exact` monta os corpos com `json_encode` e usa os endpoints configurados em `EXACT_*_URL`. Confirme a compatibilidade desses contratos com a versão das APIs utilizada na instalação.

As credenciais só entram na construção da requisição em tempo de execução. Fixtures usam domínios `.invalid` e tokens de teste. Não copie payloads reais, telefones, e-mails ou URLs autenticadas para novos cenários.

## Referência de regressão

| Arquivo | Papel |
|---|---|
| `tests/Fixtures/scenarios.php` | Entradas e respostas simuladas das operações dos clientes. |
| `tests/Fixtures/requests.json` | Resultado e requisições esperadas por operação. |
| `tests/Fixtures/webhook-scenarios.php` | Eventos completos e sequências de resposta de cada fluxo. |
| `tests/Fixtures/webhook-requests.json` | Requisições esperadas para cada webhook. |

As referências usam dados fictícios. O teste compara campos e tipos do JSON, sem exigir a mesma indentação. Mudanças deliberadas devem alterar primeiro o cenário relevante, explicar o efeito no contrato e atualizar somente as expectativas justificadas.

## Fronteiras de garantia

Consultar um registro antes de criá-lo não garante idempotência diante de eventos concorrentes. O tratamento de falhas de rede e respostas inválidas precisa ser considerado na operação. Não há política automática de repetição, que poderia duplicar alterações nos CRMs.

O retorno HTTP da entrada não é um comprovante de sucesso de todas as operações externas. A validação em ambiente controlado deve conferir também os registros nos dois CRMs e os resultados de cada chamada.
