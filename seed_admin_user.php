#!/usr/bin/env php
<?php
/**
 * PHPNetMap — seeds/updates the "admin" Yii-level login account.
 *
 * Run once after (re)provisioning the SQLite database, or any time you want
 * to reset the "admin" account's password:
 *
 *   ADMIN_PASSWORD=yourpassword php seed_admin_user.php
 *
 * Reads the same ADMIN_PASSWORD environment variable that set_htpasswd.sh
 * uses for the outer HTTP Basic Auth layer, so both layers share a secret
 * by default. In Docker, docker-entrypoint.sh runs this automatically on
 * every container start, right after set_htpasswd.sh. For a standalone
 * (non-Docker) install, run it by hand once after setup — see
 * STANDALONE_INSTALLATION_GUIDE.md.
 *
 * The Yii account is always named the literal string "admin" — matching
 * every controller's hardcoded accessRules() 'admin' check — regardless of
 * what ADMIN_USER is set to for the outer HTTP Basic Auth layer. The two
 * layers are independent; only the password is deliberately kept in sync.
 *
 * Idempotent / upsert-only: if "admin" already exists, only its password is
 * refreshed (its email is left alone), so it's safe to run on every boot.
 *
 * If ADMIN_PASSWORD isn't set, this is a no-op (exit 0): set_htpasswd.sh's
 * own random-password fallback for that case happens in a separate subshell
 * and isn't visible here, so there is nothing to seed with confidently —
 * set ADMIN_PASSWORD explicitly if you want the Yii login seeded too.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script is meant to be run from the command line: php seed_admin_user.php\n");
    exit(1);
}

require_once __DIR__ . '/yii/framework/yii.php';
Yii::createWebApplication(__DIR__ . '/protected/config/main.php');

$username = 'admin'; // deliberately NOT getenv('ADMIN_USER') — see note above

$password = getenv('ADMIN_PASSWORD');
if ($password === false || $password === '') {
    fwrite(STDOUT, "ADMIN_PASSWORD not set — skipping Yii admin-user seeding.\n");
    exit(0);
}

$user = User::model()->findByAttributes(array('username' => $username));
if ($user === null) {
    $user = new User;
    $user->username = $username;
    $user->email = $username . '@localhost';
}
$user->setPassword($password);

if ($user->save(false)) {
    fwrite(STDOUT, "Seeded Yii login for \"$username\".\n");
    exit(0);
}

fwrite(STDERR, "Failed to seed Yii admin user: " . print_r($user->getErrors(), true) . "\n");
exit(1);
