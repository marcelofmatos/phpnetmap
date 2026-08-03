#!/usr/bin/env php
<?php
/**
 * PHPNetMap — self-heals the SQLite schema on every container start.
 *
 * This app has no migration framework: new tables/columns have historically
 * been added by hand-editing the committed protected/data/phpnetmap.db and
 * shipping it as-is. That only reaches a FRESH install — any existing
 * deployment already has its own database file sitting in the mounted
 * VOLUME /app/protected/data, which the shipped .db never touches. Upgrading
 * the image tag alone does not retroactively add tables/columns to it,
 * which is exactly what broke MCP Tokens on a long-running deployment
 * upgraded past the release that introduced the mcp_token/mcp_audit_log
 * tables ("table 'mcp_token' for active record class 'McpToken' cannot be
 * found in the database").
 *
 * Every statement below MUST be idempotent (CREATE TABLE/INDEX IF NOT
 * EXISTS, or an ALTER TABLE ADD COLUMN guarded by a column-existence check)
 * so this is safe to run unconditionally on every boot, including against a
 * database that already has the schema. In Docker, docker-entrypoint.sh
 * runs this automatically on every container start, right after
 * seed_admin_user.php. For a standalone (non-Docker) install, run it by
 * hand once after upgrading — see STANDALONE_INSTALLATION_GUIDE.md.
 *
 * When a future change adds a table/column: append another idempotent
 * statement here — this script is the one place that keeps every
 * deployment's database (fresh or years-old) in sync with what the current
 * code expects.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script is meant to be run from the command line: php ensure_schema.php\n");
    exit(1);
}

require_once __DIR__ . '/yii/framework/yii.php';

try {
    Yii::createWebApplication(__DIR__ . '/protected/config/main.php');
    $db = Yii::app()->db;

    $db->createCommand('
        CREATE TABLE IF NOT EXISTS "mcp_token" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT,
            "description" VARCHAR(255) NOT NULL,
            "token_hash" VARCHAR(64) NOT NULL,
            "token_prefix" VARCHAR(12) NOT NULL,
            "expires_at" VARCHAR(10) NOT NULL,
            "last_used_at" DATETIME,
            "created_at" DATETIME NOT NULL
        )
    ')->execute();
    $db->createCommand('
        CREATE UNIQUE INDEX IF NOT EXISTS "idx_mcp_token_hash" ON mcp_token ("token_hash")
    ')->execute();

    $db->createCommand('
        CREATE TABLE IF NOT EXISTS "mcp_audit_log" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT,
            "mcp_token_id" INTEGER NOT NULL,
            "tool_name" VARCHAR(50) NOT NULL,
            "params_json" TEXT NOT NULL,
            "created_at" DATETIME NOT NULL,
            "token_description" VARCHAR(255),
            FOREIGN KEY(mcp_token_id) REFERENCES "mcp_token"(id)
        )
    ')->execute();

    // mcp_audit_log.token_description was added via ALTER TABLE after the
    // table already existed on some deployments — CREATE TABLE IF NOT
    // EXISTS above is a no-op there, so it needs its own existence check.
    $columns = $db->createCommand('PRAGMA table_info(mcp_audit_log)')->queryAll();
    $hasTokenDescription = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'token_description') {
            $hasTokenDescription = true;
            break;
        }
    }
    if (!$hasTokenDescription) {
        $db->createCommand('ALTER TABLE "mcp_audit_log" ADD COLUMN "token_description" VARCHAR(255)')->execute();
    }

    // mcp_token.mode didn't exist before this change — same idempotent
    // ALTER-if-missing pattern as mcp_audit_log.token_description above.
    // 'readonly' default is deliberate and fail-closed: an existing token
    // never silently gains write access it didn't have, even if the
    // (now-removed) site-wide Configuration mode used to be 'readwrite'.
    $columns = $db->createCommand('PRAGMA table_info(mcp_token)')->queryAll();
    $hasMode = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'mode') {
            $hasMode = true;
            break;
        }
    }
    if (!$hasMode) {
        $db->createCommand('ALTER TABLE "mcp_token" ADD COLUMN "mode" VARCHAR(10) NOT NULL DEFAULT \'readonly\'')->execute();
    }

    // Only ever applied to the committed protected/data/phpnetmap.db
    // directly (see git log 34fe69e) — never added here, so any deployment
    // whose live database predates that commit never got it, the same gap
    // this whole script exists to close for every other table/column.
    $db->createCommand('
        CREATE UNIQUE INDEX IF NOT EXISTS "idx_user_username" ON "user" ("username")
    ')->execute();

    fwrite(STDOUT, "Schema check complete.\n");
    exit(0);
} catch (Exception $e) {
    fwrite(STDERR, "Failed to ensure schema: " . $e->getMessage() . "\n");
    exit(1);
}
