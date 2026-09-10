<?php
// Standalone deterministic harness: php -n tests/push-diagnostics.php
// No GLPI bootstrap, real DB, credentials or network. Refuse real cURL.
if (extension_loaded('curl')) {
    fwrite(STDERR, "Run with php -n (real cURL must be disabled).\n");
    exit(2);
}
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
foreach (['CURLOPT_RETURNTRANSFER', 'CURLOPT_POSTFIELDS', 'CURLOPT_HTTPHEADER', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT'] as $i => $name) {
    define($name, $i + 1);
}
class FakeTransport {
    static $attempts = [];
    static $response = '{"data":[{"status":"ok"}]}';
}
function curl_init($url) { return (object) ['url' => $url, 'options' => []]; }
function curl_setopt($handle, $option, $value) { $handle->options[$option] = $value; return true; }
function curl_exec($handle) { FakeTransport::$attempts[] = $handle; return FakeTransport::$response; }
function curl_close($handle) {}
class Session {
    static $author = 4;
    static function getLoginUserID() { return self::$author; }
    static function getNewCSRFToken() { return 'fixture-csrf'; }
}
class Ticket { public $fields = ['id' => 14, 'name' => 'PRIVATE_TITLE', 'status' => 2]; public $updates = ['status']; }
class ITILFollowup { public $fields = ['itemtype' => 'Ticket', 'items_id' => 14, 'is_private' => 0, 'content' => 'PRIVATE_CONTENT']; }
class TicketTask { public $fields = ['tickets_id' => 14, 'is_private' => 0, 'content' => 'PRIVATE_CONTENT']; }
class CommonDBTM {}
class FakeDB {
    public $actors;
    public $tokens;
    public $logs = [];
    public $queries = [];
    function __construct($actors, $tokens = []) { $this->actors = $actors; $this->tokens = $tokens; }
    function request($query) {
        $this->queries[] = $query;
        switch ($query['FROM']) {
            case 'glpi_tickets_users':
                check($query['WHERE'] === ['tickets_id' => 14, 'users_id' => ['>', 0]], 'direct actor scope unchanged');
                return new ArrayIterator(array_map(function ($id) { return ['users_id' => $id]; }, $this->actors));
            case 'glpi_plugin_fluxionotify_pushtokens':
                $id = $query['WHERE']['users_id'];
                return new ArrayIterator(array_key_exists($id, $this->tokens) ? [['pushtoken' => $this->tokens[$id]]] : []);
            case 'glpi_tickets': return new ArrayIterator([['name' => 'PRIVATE_TITLE']]);
            case 'glpi_plugin_fluxionotify_logs AS l':
                return new ArrayIterator(array_map(function ($row) { return $row + ['username' => null, 'firstname' => null, 'realname' => null]; }, $this->logs));
            default: throw new RuntimeException('Unexpected DB table');
        }
    }
    function insert($table, $row) {
        check($table === 'glpi_plugin_fluxionotify_logs', 'only notification logs written');
        $this->logs[] = $row;
        return true;
    }
}
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
function resetFixture($actors, $tokens = []) {
    global $DB;
    $DB = new FakeDB($actors, $tokens);
    Session::$author = 4;
    FakeTransport::$attempts = [];
    FakeTransport::$response = '{"data":[{"status":"ok"}]}';
}
function notify() { return PluginFluxionotifyNotification::notifyTicketActors(14, 'PRIVATE_TITLE', 'PRIVATE_CONTENT'); }
function reasonCount($reason) {
    global $DB;
    return count(array_filter($DB->logs, function ($row) use ($reason) { return $row['status'] === 'skipped' && strpos($row['response'], $reason) !== false; }));
}
function safeDiagnostics() {
    global $DB;
    foreach ($DB->logs as $row) {
        if ($row['status'] !== 'skipped') { continue; }
        $serialized = json_encode($row);
        foreach (['PRIVATE_TITLE', 'PRIVATE_CONTENT', 'FIXTURE_TOKEN'] as $secret) {
            check(strpos($serialized, $secret) === false, 'diagnostic excludes sensitive fixture');
        }
    }
}
$coldHook = $argv[1] ?? null;
if ($coldHook === null) {
    require_once __DIR__ . '/../inc/notification.class.php';
}
$tests = [];
$tests['author ignored with safe diagnostic'] = function () {
    resetFixture([4], [4 => 'FIXTURE_TOKEN']);
    check(notify() === 0 && count(FakeTransport::$attempts) === 0, 'author must not receive push');
    check(reasonCount('author_ignored') === 1, 'missing author_ignored diagnostic');
    check(count($GLOBALS['DB']->queries) === 1, 'author token not queried');
    safeDiagnostics();
};
$tests['missing token diagnosed once per direct user'] = function () {
    resetFixture([6, 6]);
    check(notify() === 0 && count(FakeTransport::$attempts) === 0, 'no token means no attempt');
    check(reasonCount('no_push_token') === 1, 'missing unique no_push_token diagnostic');
    check(count($GLOBALS['DB']->queries) === 2, 'duplicate roles must not repeat token query');
    safeDiagnostics();
};
$tests['empty or author-only actor list has no eligible actors'] = function () {
    foreach ([[], [4, 4]] as $actors) {
        resetFixture($actors, [4 => 'FIXTURE_TOKEN']);
        check(notify() === 0 && count(FakeTransport::$attempts) === 0, 'empty eligibility means no attempt');
        check(reasonCount('no_eligible_actors') === 1, 'missing no_eligible_actors summary');
        safeDiagnostics();
    }
};
$tests['blank token is diagnosed without transport'] = function () {
    foreach (['', '   ', null] as $token) {
        resetFixture([6], [6 => $token]);
        check(notify() === 0 && count(FakeTransport::$attempts) === 0, 'blank token must not generate attempt');
        check(reasonCount('no_push_token') === 1, 'blank token reason');
    }
};
$tests['eligible direct user attempted once and logged'] = function () {
    resetFixture([4, 4, 6, '6', 7], [4 => 'FIXTURE_TOKEN_AUTHOR', 6 => 'FIXTURE_TOKEN_RECIPIENT', 99 => 'FIXTURE_TOKEN_UNRELATED']);
    check(notify() === 1 && count(FakeTransport::$attempts) === 1, 'one attempt despite duplicate roles');
    $payload = json_decode(FakeTransport::$attempts[0]->options[CURLOPT_POSTFIELDS], true);
    check($payload[0]['to'] === 'FIXTURE_TOKEN_RECIPIENT', 'only direct non-author recipient');
    check($payload[0]['data'] === ['ticketId' => 14], 'ticket routing preserved');
    $attemptLogs = array_values(array_filter($GLOBALS['DB']->logs, function ($row) { return $row['status'] !== 'skipped'; }));
    check(count($attemptLogs) === 1 && $attemptLogs[0]['status'] === 'success' && $attemptLogs[0]['users_id'] == 6, 'attempt log retained');
    check(reasonCount('author_ignored') === 1 && reasonCount('no_push_token') === 1, 'mixed diagnostic reasons');
    check(reasonCount('no_eligible_actors') === 0, 'eligible actor is not a missing actor');
    safeDiagnostics();
};
$tests['transport rejection or no response remains error attempt'] = function () {
    foreach ([false, '{"data":[{"status":"error"}]}', 'invalid-json'] as $response) {
        resetFixture([6], [6 => 'FIXTURE_TOKEN']);
        FakeTransport::$response = $response;
        check(notify() === 1 && count(FakeTransport::$attempts) === 1, 'return count means attempts not deliveries');
        check($GLOBALS['DB']->logs[0]['status'] === 'error', 'error attempt logged');
    }
};
$tests['all four hooks load notification from local directory'] = function () {
    // Deliberately absent plugin path: catches /plugins/ assumptions for marketplace installs.
    if (!defined('GLPI_ROOT')) { define('GLPI_ROOT', __DIR__ . '/nonexistent-glpi-root'); }
    require_once __DIR__ . '/../hook.php';
    foreach ([
        ['plugin_fluxionotify_item_add_ticket', new Ticket()],
        ['plugin_fluxionotify_item_update_ticket', new Ticket()],
        ['plugin_fluxionotify_item_add_followup', new ITILFollowup()],
        ['plugin_fluxionotify_item_add_task', new TicketTask()]
    ] as $case) {
        if (($GLOBALS['coldHook'] ?? null) !== null && $case[0] !== $GLOBALS['coldHook']) { continue; }
        resetFixture([]);
        $case[0]($case[1]);
        check(reasonCount('no_eligible_actors') === 1, 'hook reaches real notification engine');
    }
};
$tests['skipped log renders as neutral not delivery failure'] = function () {
    resetFixture([4]);
    notify();
    require_once __DIR__ . '/../inc/config.class.php';
    ob_start();
    try { (new PluginFluxionotifyConfig())->showLogsList(); $html = ob_get_contents(); }
    finally { ob_end_clean(); }
    check(strpos($html, 'Não enviado') !== false && strpos($html, '#6c757d') !== false, 'skipped needs neutral label');
    check(strpos($html, '>Falha</span>') === false, 'skipped is not transport failure');
    check(strpos($html, 'Resposta / Diagnóstico') !== false, 'diagnostics not labelled Expo response');
};
$tests['release metadata reports 1.0.2'] = function () {
    require_once __DIR__ . '/../setup.php';
    check(plugin_version_fluxionotify()['version'] === '1.0.2', 'release version must be 1.0.2');
    check(plugin_version_fluxionotify()['requirements']['glpi']['min'] === '10.0.0', 'GLPI 10 minimum retained');
};
$tests['private and unknown visibility never send content'] = function () {
    foreach ([new ITILFollowup(), new TicketTask()] as $item) {
        foreach ([1, '1', null, 'unexpected'] as $privacy) {
            resetFixture([6], [6 => 'FIXTURE_TOKEN']);
            $item->fields['is_private'] = $privacy;
            $method = $item instanceof ITILFollowup ? 'sendForFollowup' : 'sendForTask';
            PluginFluxionotifyNotification::$method($item);
            check(count(FakeTransport::$attempts) === 0, 'private or unknown visibility must block transport');
            check(reasonCount('private_or_unknown_visibility') === 1, 'safe privacy diagnostic required');
            check(count($GLOBALS['DB']->queries) === 0, 'block before reading ticket content or actors');
            safeDiagnostics();
        }
        foreach ([0, '0', false] as $privacy) {
            resetFixture([6], [6 => 'FIXTURE_TOKEN']);
            $item->fields['is_private'] = $privacy;
            PluginFluxionotifyNotification::$method($item);
            check(count(FakeTransport::$attempts) === 1, 'explicit public content retains send path');
        }
    }
};
$failed = 0;
if ($coldHook !== null) {
    check(in_array($coldHook, ['plugin_fluxionotify_item_add_ticket', 'plugin_fluxionotify_item_update_ticket', 'plugin_fluxionotify_item_add_followup', 'plugin_fluxionotify_item_add_task'], true), 'unknown cold hook');
    $tests = ['cold bootstrap ' . $coldHook => $tests['all four hooks load notification from local directory']];
}
foreach ($tests as $name => $test) {
    try { $test(); print "PASS: $name\n"; }
    catch (Throwable $error) { $failed++; print "FAIL: $name: " . $error->getMessage() . "\n"; }
}
print count($tests) . " tests, $failed failures; transport is an in-memory double.\n";
exit($failed ? 1 : 0);
