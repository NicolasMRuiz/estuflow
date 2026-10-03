#!/usr/bin/env python3
"""Local proxy/CA configuration and official archive cache for managed cloud only."""
import concurrent.futures
import hashlib
import json
import os
from pathlib import Path
import re
import socket
import subprocess
import urllib.parse
import zipfile

root = Path(__file__).resolve().parent.parent
os.chdir(root)
proxy_host = urllib.parse.urlsplit(os.environ['HTTPS_PROXY']).hostname
proxy_ip = socket.gethostbyname(proxy_host)
build = f'''    build:
      args:
        HTTP_PROXY: ${{HTTP_PROXY}}
        HTTPS_PROXY: ${{HTTPS_PROXY}}
        NO_PROXY: ${{NO_PROXY}}
      secrets: [cloud_ca]
      extra_hosts: ["{proxy_host}:{proxy_ip}"]
'''
(root / 'docker/compose.cloud.yaml').write_text(f'''services:
  app:
    extra_hosts: ["{proxy_host}:{proxy_ip}"]
{build}    environment:
      HTTP_PROXY: ${{HTTP_PROXY}}
      HTTPS_PROXY: ${{HTTPS_PROXY}}
      NO_PROXY: ${{NO_PROXY}},postgres,test-postgres,redis,test-app,localhost,127.0.0.1
      COMPOSER_CAFILE: /cloud-ca.pem
      COMPOSER_CACHE_DIR: /app/.cache/composer
    volumes: [/etc/ssl/certs/ca-certificates.crt:/cloud-ca.pem:ro]
  node:
    extra_hosts: ["{proxy_host}:{proxy_ip}"]
    environment:
      HTTP_PROXY: ${{HTTP_PROXY}}
      HTTPS_PROXY: ${{HTTPS_PROXY}}
      NODE_EXTRA_CA_CERTS: /cloud-ca.pem
      npm_config_proxy: ${{HTTP_PROXY}}
      npm_config_https_proxy: ${{HTTPS_PROXY}}
    volumes: [/etc/ssl/certs/ca-certificates.crt:/cloud-ca.pem:ro]
  browser:
{build}secrets:
  cloud_ca:
    file: /etc/ssl/certs/ca-certificates.crt
''')
lock = json.loads((root / 'composer.lock').read_text())


def cache_archive(package):
    dist = package.get('dist', {})
    url = dist.get('url', '')
    match = re.fullmatch(r'https://api\.github\.com/repos/([^/]+)/([^/]+)/zipball/([a-f0-9]{40})', url)
    if not match:
        return
    owner, repo, reference = match.groups()
    if reference != dist.get('reference'):
        raise RuntimeError('Archive reference does not match lockfile')
    target = root / '.cache/composer/files' / package['name'] / (hashlib.sha1(url.encode()).hexdigest() + '.zip')
    if target.is_file():
        return
    target.parent.mkdir(parents=True, exist_ok=True)
    temp = target.with_suffix('.download')
    # GitHub's official API archive destination, at the exact locked commit.
    subprocess.run(['curl', '--fail', '--silent', '--show-error', '--location', '--max-time', '120',
                    f'https://codeload.github.com/{owner}/{repo}/legacy.zip/{reference}', '-o', str(temp)], check=True)
    expected = dist.get('shasum')
    if expected and hashlib.sha1(temp.read_bytes()).hexdigest() != expected:
        temp.unlink()
        raise RuntimeError('Locked archive checksum mismatch')
    with zipfile.ZipFile(temp) as archive:
        if archive.testzip() is not None:
            raise RuntimeError('Corrupt archive')
        roots = {entry.split('/')[0] for entry in archive.namelist()}
        if len(roots) != 1 or not next(iter(roots)).endswith('-' + reference[:7]):
            raise RuntimeError('Archive root does not match locked commit')
    temp.rename(target)


with concurrent.futures.ThreadPoolExecutor(max_workers=4) as executor:
    list(executor.map(cache_archive, lock['packages'] + lock.get('packages-dev', [])))
print('Cloud proxy/CA configured; official locked GitHub archives cached with TLS verification.')
