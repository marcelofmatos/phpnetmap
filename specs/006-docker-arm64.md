# Spec 006 — Suporte a build ARM64 da imagem Docker

- **Issue:** [#6 — arm64](https://github.com/marcelofmatos/phpnetmap/issues/6)
- **Tipo:** Feature
- **Status:** Aprovada — decisões resolvidas (ver seção 5); pronta para implementação

## 1. Requisito

> "any hope of a arm64 build, or a instruction to build it out docker"

O usuário quer **uma imagem Docker que rode em arm64** (ex.: Raspberry Pi,
Apple Silicon, servidores ARM) ou, no mínimo, **instruções de como buildar**
para arm64.

## 2. Estado atual

`Dockerfile`:

```dockerfile
FROM tutum/apache-php
RUN apt-get install -y snmp php5-snmp php5-sqlite sqlite3 snmpd git apache2-utils
RUN git clone https://github.com/marcelofmatos/phpnetmap.git app
...
ENV ALLOW_OVERRIDE true
```

Problemas para arm64:

1. **Base image `tutum/apache-php`**: imagem antiga, descontinuada, baseada em
   **PHP 5** e publicada **somente para `linux/amd64`**. Não há manifest arm64
   → `docker build` em arm64 falha por falta de plataforma.
2. **Pacotes `php5-*`**: as distros atuais não fornecem mais `php5-snmp` /
   `php5-sqlite`. Migrar de base implica migrar para PHP 7/8.
3. **CI** (`.github/workflows/`, atualmente *untracked*): `docker-image.yml` e
   `release-and-build.yml` buildam com `platforms: linux/amd64` apenas. Não há
   `docker/setup-qemu-action`.
4. **Código clonado no build**: o `Dockerfile` faz `git clone` do repositório em
   vez de `COPY . /app`, o que ignora o código local e dificulta builds
   reproduzíveis/multi-arch a partir do checkout do CI.

## 3. Objetivo (spec)

Publicar a imagem oficial no GHCR como **multi-arch** cobrindo
`linux/amd64` **e** `linux/arm64`, de forma que:

```bash
docker run -d -p 80:80 ghcr.io/marcelofmatos/phpnetmap:latest
```

funcione tanto em amd64 quanto em arm64 (o Docker seleciona o manifest da
plataforma automaticamente), **mantendo o comportamento funcional atual**
(SNMP, SQLite, Apache + rewrite, `set_htpasswd.sh`, volume de dados).

## 4. Abordagem aprovada

Reescrita do `Dockerfile` para uma imagem **self-contained equivalente ao
`tutum/php-apache`**, sem depender da base descontinuada.

### 4.1 Base e dependências

1. **Base:** `php:7.4-apache` (Debian, multi-arch amd64+arm64, Apache + mod_php
   já incluídos).
2. **apt:** `snmp` (CLI + libsnmp), `snmpd` (daemon local, paridade funcional),
   `sqlite3`, `apache2-utils` (fornece `htpasswd`). `git` sai (usamos `COPY`).
3. **Extensões PHP:** `snmp` e `pdo_sqlite` via `docker-php-ext-install`,
   usando `libsnmp-dev`/`libsqlite3-dev` como *build-deps* purgadas ao final
   para enxugar a imagem (libs de runtime `libsnmp*`/`libsqlite3-0`
   permanecem via os pacotes `snmp`/base).
4. **`custom.ini`** em `/usr/local/etc/php/conf.d/custom.ini` reproduzindo o
   comportamento atual:
   ```ini
   error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE
   disable_functions =
   ```

### 4.2 Apache

5. `a2enmod rewrite`; `DocumentRoot` → `/app`; bloco
   `<Directory /app> AllowOverride All; Require all granted </Directory>`.
   A aplicação **sempre** exige `AllowOverride All` (rewrite + auth do
   `.htaccess`), então isso fica fixo — a env `ALLOW_OVERRIDE` (feature genérica
   do tutum, não usada de fato pela app) é **removida**.

### 4.3 Código, dados e permissões

6. **`COPY . /app`** em vez de `git clone` (respeita o `.dockerignore`).
7. `chown www-data` em `/app/protected/data`, `/app/protected/runtime` e
   `/app/assets` (SQLite e Yii precisam escrever).
8. `VOLUME /app/protected/data`; `EXPOSE 80`.

### 4.4 Startup (substitui o `/run.sh` do tutum)

9. Novo `docker-entrypoint.sh`:
   ```bash
   #!/bin/bash
   set -e
   service snmpd start || true
   /app/set_htpasswd.sh
   exec "$@"
   ```
   `ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]` +
   `CMD ["apache2-foreground"]`. Mantém o `sed` no `snmpd.conf` para liberar a
   view (`-V systemonly`).
10. **`set_htpasswd.sh`:** adicionar shebang `#!/bin/bash` (hoje não tem; era
    chamado de dentro do `/run.sh` do tutum).

### 4.5 CI multi-arch

11. **`docker-image.yml`:** converter de `docker build`/`docker push` cru para
    `docker/setup-qemu-action` + `docker/setup-buildx-action` +
    `docker/build-push-action` com `platforms: linux/amd64,linux/arm64`.
12. **`release-and-build.yml`:** adicionar `setup-qemu-action`; definir
    `platforms: linux/amd64,linux/arm64` no passo de push (o passo de
    "build + test" com `load: true` permanece single-arch amd64, pois `load`
    não aceita multi-arch). De passagem, corrigir os labels herdados de outro
    projeto (`Fishboard`/`Abstrakt Agencia`) e o comando de run inválido
    (`8080:3000` → `80:80`).

### 4.6 README

13. Adicionar seção curta de build local multi-arch:
    ```bash
    docker buildx build --platform linux/amd64,linux/arm64 \
      -t ghcr.io/marcelofmatos/phpnetmap:dev --push .
    ```

## 5. Decisões resolvidas

1. **Migração de PHP 5 → 7/8:** ✅ migrar para **PHP 7.4** (`php:7.4-apache`).
   Melhor equilíbrio: extensões `snmp`/`pdo_sqlite` disponíveis, Yii 1.1.x roda
   bem, e habilita arm64. PHP 8 traria mais atrito com o Yii 1.1.16 legado.
2. **Escopo:** ✅ **completo** — Dockerfile + CI multi-arch + README.
3. **Versão do PHP alvo:** ✅ **7.4**.
4. **Reorganização do CI:** ✅ correções pontuais de multi-arch e dos
   metadados/labels claramente errados (de outro projeto). Sem reorganização
   ampla além disso.

## 6. Critérios de aceite (para a abordagem completa)

- [ ] `docker buildx build --platform linux/amd64,linux/arm64` conclui sem erro.
- [ ] A imagem publicada no GHCR expõe manifest para amd64 **e** arm64.
- [ ] Container sobe em arm64 e serve a aplicação (Apache + rewrite ativos).
- [ ] SNMP e SQLite funcionam (extensões PHP carregadas; `snmpd` inicia).
- [ ] `set_htpasswd.sh` e o volume `/app/protected/data` continuam funcionando.
- [ ] README documenta o build multi-arch.

## 7. Plano de verificação

- Build local via `buildx` para as duas plataformas (QEMU) → sucesso.
- `docker run` em host arm64 (ou emulado) → app responde HTTP 200 na home.
- `docker run php -m` / checagem de `snmpget` dentro do container.
- Inspeção do manifest: `docker buildx imagetools inspect <imagem>` lista
  `amd64` e `arm64`.

> Não há testes automatizados que cubram a imagem hoje; a verificação é via
> build + smoke test manual descritos acima.
