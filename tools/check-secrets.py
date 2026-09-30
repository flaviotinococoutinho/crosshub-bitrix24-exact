#!/usr/bin/env python3
"""Audita arquivos, indice ou historico Git sem imprimir valores encontrados."""

import argparse
import os
from pathlib import Path
import re
import subprocess
import sys


ROOT = Path(__file__).resolve().parent.parent
SKIP = {'.git', '.remember', 'storage', 'vendor', '.baseline-source', '__pycache__'}
STORAGE_MARKERS = {Path('storage/.gitkeep'), Path('storage/.htaccess')}
PATTERNS = [
    re.compile(rb'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----'),
    re.compile(rb'https?://(?![^/\s\'"<>]*\.invalid/)[^\s\'"<>]+/rest/[0-9]+/(?!change-me|test(?:[\s/\'"?]|$))[A-Za-z0-9_-]{12,}'),
    re.compile(rb'\beyJ[A-Za-z0-9_-]{16,}\.[A-Za-z0-9_-]{16,}\.[A-Za-z0-9_-]{16,}\b'),
]


def git(*arguments):
    return subprocess.run(['git', '-C', str(ROOT), *arguments], capture_output=True, check=True).stdout


def safe_identity(identity):
    match = re.search(rb'<([^<>\r\n]+)>', identity)
    if not match:
        return False
    email = match.group(1).lower()
    return (
        email.endswith(b'@users.noreply.github.com')
        or email == b'noreply@github.com'
        or email.endswith(b'@example.invalid')
    )


def metadata():
    for commit in git('rev-list', '--all').splitlines():
        yield 'commit ' + commit.decode()[:12], git('cat-file', 'commit', commit.decode())
    for entry in git('for-each-ref', '--format=%(objectname) %(objecttype)', 'refs/tags').splitlines():
        object_id, kind = entry.split()
        if kind == b'tag':
            yield 'tag ' + object_id.decode()[:12], git('cat-file', 'tag', object_id.decode())


def local_secrets():
    path = ROOT / '.env'
    if not path.is_file():
        return []
    values = []
    for line in path.read_bytes().splitlines():
        key, separator, value = line.partition(b'=')
        if not separator or not re.search(rb'TOKEN|SECRET|PASSWORD|BTX_HOOK', key):
            continue
        value = value.strip().strip(b'\'"')
        if len(value) >= 12 and value not in (b'change-me',) and not value.startswith(b'test-'):
            values.append(value)
    return values


def forbidden(path):
    if path in STORAGE_MARKERS:
        return False
    parts = path.parts
    return (
        any(part in SKIP for part in parts)
        or (path.name.startswith('.env') and path.name != '.env.example')
        or path.name.startswith('._')
        or path.name == '.DS_Store'
        or path.name == 'auth.json'
        or (parts[0] == 'config' and '.local.' in path.name)
        or bool(re.search(r'\.(log(?:\..*)?|pem|key|bak|backup|sql|zip|tar|gz)$', path.name, re.I))
        or bool(re.match(r'(log|bck).*\.txt$', path.name, re.I))
    )


def history():
    seen = set()
    for commit in git('rev-list', '--all').splitlines():
        for entry in git('ls-tree', '-rz', '--full-tree', commit.decode()).split(b'\0'):
            if not entry:
                continue
            metadata, name = entry.split(b'\t', 1)
            _, kind, object_id = metadata.split()
            key = (name, object_id)
            if kind != b'blob' or key in seen:
                continue
            seen.add(key)
            yield Path(os.fsdecode(name)), git('cat-file', 'blob', object_id.decode())


def candidates(staged, committed=False):
    if committed:
        yield from history()
        return
    if staged:
        for name in git('ls-files', '-z').split(b'\0'):
            if name:
                path = Path(os.fsdecode(name))
                yield path, git('show', ':' + path.as_posix())
        return
    for directory, dirs, names in os.walk(ROOT):
        dirs[:] = [name for name in dirs if name not in SKIP]
        for name in names:
            path = Path(directory, name).relative_to(ROOT)
            if not forbidden(path):
                yield path, (ROOT / path).read_bytes()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    scope = parser.add_mutually_exclusive_group()
    scope.add_argument('--staged', action='store_true', help='Auditar os blobs do indice Git, inclusive arquivos forcados')
    scope.add_argument('--history', action='store_true', help='Auditar blobs de todos os commits e tags locais')
    parser.add_argument('--identity', action='store_true', help='Conferir o email da autoria e do committer do proximo commit')
    args = parser.parse_args()
    secrets = local_secrets()
    findings = []
    count = 0
    metadata_count = 0
    try:
        for path, content in candidates(args.staged, args.history):
            count += 1
            if forbidden(path):
                findings.append((path, 'arquivo privado/operacional versionado'))
                continue
            if any(secret in content for secret in secrets) or any(pattern.search(content) for pattern in PATTERNS):
                findings.append((path, 'possivel credencial'))
        if args.identity:
            for variable in ('GIT_AUTHOR_IDENT', 'GIT_COMMITTER_IDENT'):
                if not safe_identity(git('var', variable)):
                    findings.append((Path(variable), 'use email noreply do GitHub na identidade Git'))
        if args.history:
            for label, content in metadata():
                metadata_count += 1
                headers = content.split(b'\n\n', 1)[0].splitlines()
                identities = [line for line in headers if line.startswith((b'author ', b'committer ', b'tagger '))]
                if any(not safe_identity(identity) for identity in identities):
                    findings.append((Path(label), 'email pessoal em metadados Git'))
                if any(secret in content for secret in secrets) or any(pattern.search(content) for pattern in PATTERNS):
                    findings.append((Path(label), 'possivel credencial em metadados Git'))
    except subprocess.CalledProcessError:
        print('Nao foi possivel ler o Git. Execute na copia com repositorio inicializado.', file=sys.stderr)
        return 2
    for path, reason in findings:
        print(f'BLOQUEADO: {path.as_posix()} ({reason}; valor omitido)')
    print(f'{count} arquivos e {metadata_count} metadados auditados; {len(findings)} ocorrencias.')
    return 1 if findings else 0


if __name__ == '__main__':
    sys.exit(main())
