# Versões e validação

As tags identificam versões do CrossHub e permitem relacionar código, documentação e verificações.

## Convenção

Uso `vMAJOR.MINOR.PATCH` para versões e o sufixo `-rc.N` para candidatas à revisão. Enquanto o projeto está em `0.x`, mudanças de contrato continuam exigindo descrição no [changelog](../CHANGELOG.md).

`v0.1.0-rc.1` identifica uma versão candidata. Uma nova candidata recebe outra tag, como `v0.1.0-rc.2`. Tags publicadas não são movidas durante a manutenção normal.

## Antes de marcar uma versão

1. Revise o efeito da mudança nas entradas, campos, eventos e chamadas externas.
2. Execute lint, testes e auditoria de dados privados conforme [Contribuição](../CONTRIBUTING.md).
3. Confirme o resultado da CI no commit que receberá a tag.
4. Atualize o changelog com a configuração necessária e os limites conhecidos.
5. Crie uma tag anotada, usando sua identidade Git e um e-mail apropriado para publicação.

Uma tag e uma CI aprovada identificam o código verificado. A homologação com os CRMs e a implantação precisam de evidências próprias, registradas para o ambiente correspondente.

## Compatibilidade

Os testes verificam cenários de criação, consulta e atualização de leads, contatos e negócios, além da configuração, logs e entradas HTTP. Eles usam respostas programadas e servidores em loopback.

A validação de uma instalação deve conferir as APIs dos fornecedores, os mapeamentos de campos e estágios, a proteção dos webhooks e os resultados das operações nos dois CRMs. Consulte [Operação](OPERACAO.md) para os requisitos.
