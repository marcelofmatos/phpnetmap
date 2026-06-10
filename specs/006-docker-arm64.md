# Spec 006 — Suporte a build ARM64 da imagem Docker

- **Issue:** [#6 — arm64](https://github.com/marcelofmatos/phpnetmap/issues/6)
- **Tipo:** Feature
- **Status:** Proposta (contém decisões em aberto — validar antes de implementar)

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

## 4. Abordagem proposta

1. **Trocar a base** por uma imagem oficial multi-arch, p.ex.
   `php:7.4-apache` ou `php:8.x-apache` (ambas publicam amd64 + arm64).
2. **Reescrever as dependências**: instalar `snmp`, `snmpd`, `sqlite3`,
   `apache2-utils` via apt; habilitar extensões PHP (`snmp`, `pdo_sqlite`)
   com `docker-php-ext-install` / pacotes da distro.
3. **`COPY . /app`** em vez de `git clone` (build a partir do checkout).
4. **Multi-arch no CI**: adicionar `docker/setup-qemu-action` +
   `docker/setup-buildx-action` e definir
   `platforms: linux/amd64,linux/arm64` no `build-push-action`.
5. **Documentar** no README o comando de build local multi-arch:
   ```bash
   docker buildx build --platform linux/amd64,linux/arm64 \
     -t ghcr.io/marcelofmatos/phpnetmap:dev --push .
   ```

## 5. Decisões em aberto (precisam de validação do usuário)

1. **Migração de PHP 5 → 7/8**: é a parte de maior risco. A migração da base é
   praticamente obrigatória (não existe `tutum/apache-php` arm64 nem `php5-*`
   nas distros atuais). É aceitável migrar para PHP 7.4/8.x? Isso pode exigir
   ajustes no código legado (Yii 1.x roda em PHP 7.x; em PHP 8 há mais atritos).
2. **Escopo**: entregar **imagem multi-arch publicada** (mexe na base + CI) ou
   apenas **instruções de build** documentadas no README (escopo menor)?
3. **Versão do PHP alvo** (7.4 vs 8.1+), considerando compatibilidade do Yii
   1.1.x usado pelo projeto.
4. **Reorganização do CI**: os workflows em `.github/` ainda contêm metadados de
   outro projeto ("Fishboard", "Abstrakt Agencia") e não estão versionados.
   Faz parte deste escopo limpá-los/versioná-los?

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
