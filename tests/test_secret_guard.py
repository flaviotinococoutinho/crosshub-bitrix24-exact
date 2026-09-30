#!/usr/bin/env python3
"""Exercita o hook em um repositorio temporario, sempre com dados ficticios."""

from pathlib import Path
import shutil
import subprocess
import tempfile


root = Path(__file__).resolve().parent.parent


def run(directory, *command, expected=0):
    result = subprocess.run(command, cwd=directory, capture_output=True)
    assert result.returncode == expected, f'Falha na verificacao de {command[0]} (saida omitida)'
    return result.stdout


with tempfile.TemporaryDirectory(prefix='crosshub-git-test-') as temporary:
    project = Path(temporary)
    for name in ['.gitignore', '.githooks/pre-commit', 'tools/check-secrets.py', 'storage/.gitkeep', 'storage/.htaccess']:
        target = project / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(root / name, target)
    (project / '.githooks/pre-commit').chmod(0o755)
    run(project, 'git', 'init', '-q')
    run(project, 'git', 'config', 'user.name', 'CrossHub Test')
    run(project, 'git', 'config', 'user.email', 'test@example.invalid')
    run(project, 'git', 'config', 'core.hooksPath', '.githooks')
    (project / 'safe.php').write_text('<?php echo "safe";\n')
    (project / '.env.example').write_text('EXACT_TOKEN_ALUGUEL=change-me\n')
    run(project, 'git', 'add', '.')
    run(project, 'git', 'commit', '-qm', 'Test safe code')
    run(project, 'python3', 'tools/check-secrets.py', '--history')

    secret = 'synthetic-' + 'private-value-123456'
    (project / '.env').write_text('EXACT_TOKEN_ALUGUEL=' + secret + '\n')
    for name in ['.env', 'config/bitrix-mappings.local.php', 'auth.json', 'storage/old.txt', 'sample.log', 'sample.log.1', '._code.php']:
        run(project, 'git', 'check-ignore', '--no-index', name)
    run(project, 'git', 'check-ignore', '--no-index', '.env.example', expected=1)
    for name in ['storage/.gitkeep', 'storage/.htaccess']:
        run(project, 'git', 'check-ignore', '--no-index', name, expected=1)
    run(project, 'git', 'add', '-f', '.env')
    run(project, 'git', 'commit', '-qm', 'Must be blocked', expected=1)
    run(project, 'git', 'restore', '--staged', '.env')

    (project / 'unsafe.php').write_text('<?php $value = "' + secret + '";\n')
    run(project, 'git', 'add', 'unsafe.php')
    run(project, 'git', 'commit', '-qm', 'Must also be blocked', expected=1)
    run(project, 'git', 'restore', '--staged', 'unsafe.php')
    hook = 'https://' + 'fixture.example/rest/1/' + 'synthetic123456789'
    (project / 'unsafe.php').write_text('<?php $value = "' + hook + '";\n')
    run(project, 'git', 'add', 'unsafe.php')
    run(project, 'git', 'commit', '-qm', 'Must block webhook URL', expected=1)
    run(project, 'git', 'restore', '--staged', 'unsafe.php')

    # A excecao permite somente os dois marcadores de storage, nunca dados reais.
    (project / 'storage/old.txt').write_text('private operational record\n')
    run(project, 'git', 'add', '-f', 'storage/old.txt')
    run(project, 'git', 'commit', '-qm', 'Must block operational data', expected=1)
    run(project, 'git', 'restore', '--staged', 'storage/old.txt')
    (project / 'config').mkdir()
    (project / 'config/bitrix-mappings.local.php').write_text('<?php return array();\n')
    run(project, 'git', 'add', '-f', 'config/bitrix-mappings.local.php')
    run(project, 'git', 'commit', '-qm', 'Must block local configuration', expected=1)
    run(project, 'git', 'restore', '--staged', 'config/bitrix-mappings.local.php')

    # Excluir uma credencial da arvore atual nao a remove do historico.
    run(project, 'git', 'add', 'unsafe.php')
    run(project, 'git', '-c', 'core.hooksPath=/dev/null', 'commit', '-qm', 'Synthetic unsafe history')
    (project / 'unsafe.php').write_text('<?php echo "safe again";\n')
    run(project, 'git', 'add', 'unsafe.php')
    run(project, 'git', 'commit', '-qm', 'Clean current tree')
    run(project, 'python3', 'tools/check-secrets.py', '--staged')
    run(project, 'python3', 'tools/check-secrets.py', '--history', expected=1)

with tempfile.TemporaryDirectory(prefix='crosshub-identity-test-') as temporary:
    project = Path(temporary)
    for name in ['.githooks/pre-commit', 'tools/check-secrets.py']:
        target = project / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(root / name, target)
    (project / '.githooks/pre-commit').chmod(0o755)
    run(project, 'git', 'init', '-q')
    run(project, 'git', 'config', 'user.name', 'CrossHub Test')
    run(project, 'git', 'config', 'user.email', '123+fixture@users.noreply.github.com')
    run(project, 'git', 'config', 'core.hooksPath', '.githooks')
    (project / 'safe.txt').write_text('synthetic data\n')
    run(project, 'git', 'add', 'safe.txt')
    run(project, 'git', 'commit', '-qm', 'Safe identity')
    run(project, 'python3', 'tools/check-secrets.py', '--history')

    private_email = 'private-fixture@example.org'
    run(project, 'git', 'config', 'user.email', private_email)
    output = run(project, 'git', 'commit', '--allow-empty', '-qm', 'Must block private identity', expected=1)
    assert private_email.encode() not in output, 'O email nao deve aparecer no relatorio'
    run(project, 'git', '-c', 'core.hooksPath=/dev/null', 'commit', '--allow-empty', '-qm', 'Synthetic private identity')
    run(project, 'git', 'tag', '-a', 'fixture-tag', '-m', 'Synthetic private tag')
    run(project, 'git', 'config', 'user.email', '123+fixture@users.noreply.github.com')
    run(project, 'git', 'commit', '--allow-empty', '-qm', 'Safe latest identity')
    run(project, 'python3', 'tools/check-secrets.py', '--staged')
    output = run(project, 'python3', 'tools/check-secrets.py', '--history', expected=1)
    assert b'commit ' in output and b'tag ' in output, 'Conferir autoria de commits anteriores e tags'
    assert private_email.encode() not in output, 'Metadados privados devem ser mascarados'

print('Git/hook: arquivos privados, credenciais e emails de autoria bloqueados; commits anteriores e tags conferidos sem expor valores.')
