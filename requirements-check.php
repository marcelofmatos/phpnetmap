#!/usr/bin/env php
<?php
/**
 * PHPNetMap — standalone server requirements checker.
 *
 * Run from the console on the machine that will host PHPNetMap, before
 * (or after) a manual/standalone install (no Docker):
 *
 *   php requirements-check.php
 *
 * It only reads the environment — PHP version/extensions/ini, filesystem
 * permissions under this project, and (best-effort) the local Apache
 * binary — it does not modify anything. Checks mirror exactly what the
 * project's own Dockerfile installs/configures, so a server that passes
 * this script matches what actually ships in the Docker image. See
 * STANDALONE_INSTALLATION_GUIDE.md for how to fix anything reported here.
 *
 * Exit code: 0 if every required check passes (warnings are still shown
 * but don't fail the run), non-zero if any required check fails — safe to
 * use in a provisioning script, e.g. `php requirements-check.php || exit 1`.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script is meant to be run from the command line: php requirements-check.php\n");
    exit(1);
}

$root = __DIR__;
$results = array(); // each: array('label', 'status' => pass|warn|fail, 'detail')

function pnm_check($label, $status, $detail = '') {
    global $results;
    $results[] = array('label' => $label, 'status' => $status, 'detail' => $detail);
}

// Plain ASCII by default (safest over SSH/odd terminals); pass --no-color to
// force it off even when a color-capable TTY is detected.
$useColor = function_exists('posix_isatty') && @posix_isatty(STDOUT) && !in_array('--no-color', $argv, true);
function pnm_color($text, $code, $useColor) {
    return $useColor ? "\033[" . $code . "m" . $text . "\033[0m" : $text;
}

// ---------------------------------------------------------------------
// 1. PHP version — the Docker image (and the only combination this app
//    has actually been run against) is php:7.4-apache.
// ---------------------------------------------------------------------
$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '7.1.0', '<')) {
    pnm_check(
        'PHP version',
        'fail',
        "$phpVersion — needs at least PHP 7.1; PHP 7.4 is what the Docker image (and this checklist) targets."
    );
} elseif (version_compare($phpVersion, '8.0.0', '>=')) {
    pnm_check(
        'PHP version',
        'warn',
        "$phpVersion — this app (Yii 1.1) targets PHP 7.x, and PHP 7.4 is the exact version the Docker image "
        . "and CI use. PHP 8+ is untested here: it may run with extra deprecation notices, or not run at all."
    );
} else {
    pnm_check('PHP version', 'pass', $phpVersion);
}

// ---------------------------------------------------------------------
// 2. Required PHP extensions — exactly what the Dockerfile installs
//    (docker-php-ext-install snmp pdo_sqlite; pecl install apcu), plus
//    the couple of core ones the app code itself calls directly.
// ---------------------------------------------------------------------
$requiredExtensions = array(
    'pdo'        => 'base PDO layer required by pdo_sqlite',
    'pdo_sqlite' => 'stores all application data (hosts, connections, vlans, SNMP templates, host faces, config) in protected/data/phpnetmap.db',
    'snmp'       => 'queries network devices via SNMP v1/v2c/v3 — the core feature of this app (protected/components/PNMSnmp.php)',
    'json'       => 'used throughout the AJAX endpoints (port info/status/traffic, CAM/ARP lookups, etc.)',
    'session'    => "backs Yii's user/session component (protected/config/main.php 'user' => array('allowAutoLogin' => true))",
);
foreach ($requiredExtensions as $ext => $why) {
    if (extension_loaded($ext)) {
        pnm_check("Extension: $ext", 'pass', $why);
    } else {
        pnm_check("Extension: $ext", 'fail', "not loaded — $why");
    }
}

// apcu gets its own check: protected/components/CacheAPC.php throws an
// Exception at construction time if apc.enabled is off, even when the
// extension is present — and caching is on by default (see "cache" in
// protected/data/params.ini, editable from the Configuration page).
if (extension_loaded('apcu')) {
    if (ini_get('apc.enabled')) {
        pnm_check('Extension: apcu', 'pass', 'caches SNMP results (CAM/ARP tables, GET responses) per the TTLs on the Configuration page');
    } else {
        pnm_check(
            'Extension: apcu',
            'warn',
            'loaded but apc.enabled is Off in php.ini — CacheAPC will throw an exception as soon as the app tries to '
            . 'cache anything, unless you disable "Cache" on the Configuration page. Set apc.enabled=1.'
        );
    }
} else {
    pnm_check(
        'Extension: apcu',
        'warn',
        'not installed — caching is enabled by default (Configuration page), and will throw an exception until you '
        . 'either install apcu or turn "Cache" off there.'
    );
}

// ---------------------------------------------------------------------
// php.ini settings — the app never calls ini_set() for any of these
// (see index.php), so it's entirely at the mercy of the server's own
// php.ini. None of these are "the app won't start" failures, so they're
// warnings, not hard fails.
// ---------------------------------------------------------------------
function pnm_ini_bytes($value) {
    $value = trim((string) $value);
    if ($value === '' || $value === '-1') {
        return -1; // unlimited
    }
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    switch ($unit) {
        case 'g': return (int) ($number * 1024 * 1024 * 1024);
        case 'm': return (int) ($number * 1024 * 1024);
        case 'k': return (int) ($number * 1024);
        default:  return (int) $number;
    }
}

$displayErrors = ini_get('display_errors');
if ($displayErrors && strtolower($displayErrors) !== 'off' && $displayErrors !== '0') {
    pnm_check(
        'php.ini: display_errors',
        'warn',
        "currently On — a stray PHP warning/notice would get printed straight into an AJAX response (port info/status/"
        . "traffic, etc.) and break it client-side (invalid JSON). Set display_errors=Off and log_errors=On in production."
    );
} else {
    pnm_check('php.ini: display_errors', 'pass', 'Off, as recommended for production');
}

// The Host Face editor lets you upload an equipment photo (stored as a
// base64 data URI in the SVG) — both limits need to cover the photo, and
// post_max_size must be >= upload_max_filesize or uploads silently fail.
$uploadMax = pnm_ini_bytes(ini_get('upload_max_filesize'));
$postMax = pnm_ini_bytes(ini_get('post_max_size'));
$minRecommendedBytes = 8 * 1024 * 1024; // 8M
if ($uploadMax !== -1 && $uploadMax < $minRecommendedBytes) {
    pnm_check(
        'php.ini: upload_max_filesize',
        'warn',
        ini_get('upload_max_filesize') . ' — a decent-resolution equipment photo (Host Face editor "Upload image") '
        . 'can exceed this. Recommend at least 8M.'
    );
} else {
    pnm_check('php.ini: upload_max_filesize', 'pass', ini_get('upload_max_filesize'));
}
if ($postMax !== -1 && $uploadMax !== -1 && $postMax < $uploadMax) {
    pnm_check(
        'php.ini: post_max_size',
        'warn',
        ini_get('post_max_size') . " is smaller than upload_max_filesize (" . ini_get('upload_max_filesize')
        . ') — uploads will fail silently. post_max_size must be >= upload_max_filesize.'
    );
} else {
    pnm_check('php.ini: post_max_size', 'pass', ini_get('post_max_size'));
}

$memoryLimit = pnm_ini_bytes(ini_get('memory_limit'));
$minRecommendedMemory = 128 * 1024 * 1024; // 128M
if ($memoryLimit !== -1 && $memoryLimit < $minRecommendedMemory) {
    pnm_check(
        'php.ini: memory_limit',
        'warn',
        ini_get('memory_limit') . ' — a full SNMP walk (CAM/ARP table) on a switch with thousands of entries can use '
        . 'more than this. Recommend at least 128M.'
    );
} else {
    pnm_check('php.ini: memory_limit', 'pass', ini_get('memory_limit'));
}

$timezone = ini_get('date.timezone');
if ($timezone === '' || $timezone === false) {
    pnm_check(
        'php.ini: date.timezone',
        'warn',
        'not set — PHP silently falls back to UTC, which is fine functionally but means every timestamp shown in the '
        . 'UI is UTC regardless of where this server is. Set date.timezone to your local zone (e.g. America/Sao_Paulo) if that matters to you.'
    );
} else {
    pnm_check('php.ini: date.timezone', 'pass', $timezone);
}

// ---------------------------------------------------------------------
// 3. Writable directories — same paths the Dockerfile chown's to
//    www-data (mkdir -p protected/data protected/runtime assets).
// ---------------------------------------------------------------------
$writableDirs = array(
    'protected/data'    => 'the SQLite database (phpnetmap.db) and params.ini (persistent Configuration-page settings) live here',
    'protected/runtime' => "Yii's own runtime cache/log directory",
    'assets'            => 'Yii publishes CSS/JS assets here at runtime',
);
foreach ($writableDirs as $dir => $why) {
    $path = $root . '/' . $dir;
    if (!is_dir($path)) {
        pnm_check(
            "Writable: $dir",
            'fail',
            "directory does not exist — create it (mkdir -p $dir) and make it writable by the user Apache runs as. $why"
        );
    } elseif (!is_writable($path)) {
        pnm_check(
            "Writable: $dir",
            'fail',
            "exists but is not writable by the user running this check — chown/chmod it for the Apache user. $why"
        );
    } else {
        pnm_check("Writable: $dir", 'pass', $why);
    }
}

// ---------------------------------------------------------------------
// 4. .htpasswd — the whole app sits behind Apache Basic Auth (.htaccess),
//    not a login page; nothing loads at all without this file.
// ---------------------------------------------------------------------
$htpasswdPath = $root . '/.htpasswd';
if (file_exists($htpasswdPath) && filesize($htpasswdPath) > 0) {
    pnm_check('.htpasswd', 'pass', 'present and non-empty — Apache Basic Auth (see .htaccess) will use it');
} elseif (file_exists($htpasswdPath)) {
    pnm_check(
        '.htpasswd',
        'warn',
        'exists but is empty — no user can log in yet. Create an entry with: '
        . 'htpasswd -c ' . $htpasswdPath . ' admin (see STANDALONE_INSTALLATION_GUIDE.md)'
    );
} else {
    pnm_check(
        '.htpasswd',
        'warn',
        'not found at project root — every page will 500 until it exists. Create it with: '
        . 'htpasswd -c ' . $htpasswdPath . ' admin (see STANDALONE_INSTALLATION_GUIDE.md)'
    );
}

// ---------------------------------------------------------------------
// 5. Apache modules — best-effort only: apache_get_modules() only works
//    when PHP itself runs as the Apache SAPI, not from the CLI, so this
//    shells out to apache2ctl/httpd -M if one is on PATH. Never fatal.
// ---------------------------------------------------------------------
$apacheBinary = null;
foreach (array('apache2ctl', 'apachectl', 'httpd') as $bin) {
    $which = trim((string) @shell_exec('command -v ' . escapeshellarg($bin) . ' 2>/dev/null'));
    if ($which !== '') {
        $apacheBinary = $which;
        break;
    }
}
if ($apacheBinary === null) {
    pnm_check(
        'Apache modules',
        'warn',
        'no apache2ctl/apachectl/httpd found on PATH — could not check automatically. This app needs mod_rewrite '
        . '(clean URLs) and mod_authn_file + mod_auth_basic (the .htpasswd login) enabled, and AllowOverride All '
        . 'on the vhost so .htaccess is honored. Verify manually if this server does run Apache elsewhere.'
    );
} else {
    $modulesOutput = (string) @shell_exec(escapeshellarg($apacheBinary) . ' -M 2>/dev/null');
    if ($modulesOutput === '') {
        pnm_check(
            'Apache modules',
            'warn',
            "found $apacheBinary but couldn't list modules (permissions? not the same Apache serving this app?) — "
            . 'verify manually that mod_rewrite and mod_authn_file + mod_auth_basic are enabled.'
        );
    } else {
        $requiredModules = array(
            'rewrite_module'    => 'mod_rewrite — clean URLs (.htaccess RewriteRule)',
            'authn_file_module' => 'mod_authn_file — reads .htpasswd',
            'authz_user_module' => 'mod_authz_user — the "Require valid-user" directive in .htaccess',
        );
        foreach ($requiredModules as $needle => $why) {
            if (strpos($modulesOutput, $needle) !== false) {
                pnm_check("Apache: $needle", 'pass', $why);
            } else {
                pnm_check("Apache: $needle", 'fail', "not enabled — $why. Enable with: a2enmod " . str_replace('_module', '', $needle));
            }
        }
    }
}

// ---------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------
$labelWidth = 0;
foreach ($results as $r) {
    $labelWidth = max($labelWidth, strlen($r['label']));
}

echo "\nPHPNetMap — standalone server requirements check\n";
echo str_repeat('=', 50) . "\n\n";

$counts = array('pass' => 0, 'warn' => 0, 'fail' => 0);
foreach ($results as $r) {
    $counts[$r['status']]++;
    $badge = array(
        'pass' => pnm_color('PASS', '32', $useColor),
        'warn' => pnm_color('WARN', '33', $useColor),
        'fail' => pnm_color('FAIL', '31', $useColor),
    );
    printf("[%s] %-" . $labelWidth . "s  %s\n", $badge[$r['status']], $r['label'], $r['detail']);
}

echo "\n" . str_repeat('-', 50) . "\n";
printf(
    "%d passed, %d warning(s), %d failed\n",
    $counts['pass'],
    $counts['warn'],
    $counts['fail']
);

if ($counts['fail'] > 0) {
    echo "\nFix the FAIL items above before running PHPNetMap — see STANDALONE_INSTALLATION_GUIDE.md.\n";
    exit(1);
}
if ($counts['warn'] > 0) {
    echo "\nNo blocking issues, but review the WARN items above.\n";
    exit(0);
}
echo "\nEverything looks good.\n";
exit(0);
