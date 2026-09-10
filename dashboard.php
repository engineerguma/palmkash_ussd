<?php
/**
 * Palmkash USSD - Logs & Transactions Dashboard (local dev tool)
 * Self-contained: reads conf/config.ini directly, connects to MySQL + Redis
 * on its own, and tails the trace logs. Does NOT touch the app framework.
 *
 * Served directly by XAMPP (it is a real file, so .htaccess does not rewrite it):
 *   http://localhost/palmkash_ussd/dashboard.php
 */

// ---- Safety: localhost only. Never expose this on a public host. ----
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1', ''], true)) {
    http_response_code(403);
    die('Dashboard restricted to localhost.');
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Africa/Kigali');

$conf = @parse_ini_file(__DIR__ . '/conf/config.ini', true);
if ($conf === false) {
    die('Could not parse conf/config.ini');
}

$DB   = $conf['datastore'] ?? [];
$RED  = $conf['PK_REDIS']  ?? [];

// ---------- connections (fail soft) ----------
$pdo = null; $dbErr = null;
try {
    $pdo = new PDO(
        ($DB['dtype'] ?? 'mysql') . ':host=' . ($DB['dhost'] ?? '127.0.0.1') .
        ';dbname=' . ($DB['dname'] ?? '') . ';charset=utf8mb4',
        $DB['duser'] ?? 'root', $DB['dpass'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $e) { $dbErr = $e->getMessage(); }

$redis = null; $redisErr = null;
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redis->connect($RED['host'] ?? '127.0.0.1', (int)($RED['port'] ?? 6379), 2.0);
        if (!empty($RED['password'])) { @$redis->auth($RED['password']); }
    } catch (Throwable $e) { $redisErr = $e->getMessage(); $redis = null; }
} else {
    $redisErr = 'php redis extension not loaded';
}

// ---------- helpers ----------
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Collect USSD session bases from redis keys (ignores other apps' keys). */
function ussd_sessions($redis) {
    $sessions = [];
    if (!$redis) return $sessions;
    $suffixes = ['_current_state', '_input_values', '_events', '_event_categories', '_event_tickets'];
    $keys = $redis->keys('*');
    foreach ($keys as $k) {
        if (!preg_match('/^\d{6,}_/', $k)) continue;       // must start with an msisdn
        $base = $k;
        foreach ($suffixes as $s) {
            if (substr($k, -strlen($s)) === $s) { $base = substr($k, 0, -strlen($s)); break; }
        }
        $sessions[$base] = true;
    }
    $out = [];
    foreach (array_keys($sessions) as $base) {
        $data  = $redis->hGetAll($base) ?: [];
        $state = $redis->hGetAll($base . '_current_state') ?: [];
        $rawIv = $redis->get($base . '_input_values');
        $inputs = [];
        if ($rawIv) { $u = @unserialize($rawIv); if (is_array($u)) $inputs = $u; }
        $out[] = [
            'base'    => $base,
            'ttl'     => $redis->ttl($base),
            'data'    => $data,
            'state'   => $state,
            'inputs'  => $inputs,
        ];
    }
    // newest-ish first (by ttl remaining desc as a proxy for recency)
    usort($out, fn($a, $b) => ($b['ttl'] <=> $a['ttl']));
    return $out;
}

function log_files() {
    $dir = __DIR__ . '/systemlog/tmp';
    $files = glob($dir . '/*.txt') ?: [];
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    return $files;
}

function tail_file($file, $maxLines = 400, $filter = '') {
    if (!is_file($file)) return [];
    $lines = @file($file, FILE_IGNORE_NEW_LINES) ?: [];
    if ($filter !== '') {
        $lines = array_values(array_filter($lines, fn($l) => stripos($l, $filter) !== false));
    }
    return array_slice($lines, -$maxLines);
}

$tab     = $_GET['tab']    ?? 'sessions';
$refresh = isset($_GET['refresh']) ? max(0, (int)$_GET['refresh']) : 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ($refresh > 0): ?><meta http-equiv="refresh" content="<?= $refresh ?>"><?php endif; ?>
<title>Palmkash USSD — Dashboard</title>
<style>
  :root{
    --bg:#0d1117; --panel:#161b22; --panel2:#1c2330; --border:#2a3139;
    --text:#e6edf3; --muted:#8b949e; --accent:#2f81f7; --green:#3fb950;
    --amber:#d29922; --red:#f85149; --mono:'Cascadia Code',Consolas,'Courier New',monospace;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font:14px/1.5 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
  header{background:var(--panel);border-bottom:1px solid var(--border);padding:14px 20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;position:sticky;top:0;z-index:10}
  header h1{font-size:16px;margin:0;font-weight:600;letter-spacing:.3px}
  header h1 .k{color:var(--accent)}
  .pill{font-size:11px;padding:2px 8px;border-radius:20px;border:1px solid var(--border)}
  .pill.ok{color:var(--green);border-color:#1f4a2b}
  .pill.bad{color:var(--red);border-color:#5a2221}
  nav{display:flex;gap:4px;margin-left:auto;flex-wrap:wrap}
  nav a{color:var(--muted);text-decoration:none;padding:6px 14px;border-radius:6px;font-weight:500}
  nav a.active{background:var(--accent);color:#fff}
  nav a:hover:not(.active){background:var(--panel2);color:var(--text)}
  .wrap{padding:20px;max-width:1200px;margin:0 auto}
  .card{background:var(--panel);border:1px solid var(--border);border-radius:10px;margin-bottom:18px;overflow:hidden}
  .card h2{font-size:13px;margin:0;padding:12px 16px;border-bottom:1px solid var(--border);color:var(--muted);text-transform:uppercase;letter-spacing:.5px;font-weight:600}
  .card .body{padding:14px 16px}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th,td{text-align:left;padding:8px 12px;border-bottom:1px solid var(--border);vertical-align:top}
  th{color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.4px}
  tr:last-child td{border-bottom:none}
  td.mono,.mono{font-family:var(--mono);font-size:12px}
  .badge{display:inline-block;padding:1px 7px;border-radius:5px;font-size:11px;font-family:var(--mono);background:var(--panel2);border:1px solid var(--border)}
  .badge.state{color:var(--accent)}
  .muted{color:var(--muted)}
  .empty{color:var(--muted);padding:24px;text-align:center;font-style:italic}
  pre.log{margin:0;padding:14px 16px;background:#010409;border-radius:8px;overflow:auto;max-height:620px;font-family:var(--mono);font-size:12px;line-height:1.55;white-space:pre-wrap;word-break:break-word}
  pre.log .err{color:var(--red)} pre.log .warn{color:var(--amber)}
  form.inline{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
  input,select{background:var(--panel2);border:1px solid var(--border);color:var(--text);padding:7px 10px;border-radius:6px;font-size:13px}
  input[type=text]{min-width:220px}
  button{background:var(--accent);border:0;color:#fff;padding:7px 14px;border-radius:6px;cursor:pointer;font-weight:600}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px}
  .sess{background:var(--panel2);border:1px solid var(--border);border-radius:8px;padding:12px 14px}
  .sess .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
  .sess .msisdn{font-weight:700;font-family:var(--mono)}
  .kv{display:flex;gap:8px;font-size:12px;margin:2px 0}
  .kv .k{color:var(--muted);min-width:96px}
  .timeline{margin-top:8px;border-top:1px dashed var(--border);padding-top:8px}
  .timeline .step{font-family:var(--mono);font-size:11.5px;padding:2px 0}
  .counts{display:flex;flex-wrap:wrap;gap:8px}
  .count{background:var(--panel2);border:1px solid var(--border);border-radius:8px;padding:8px 12px;min-width:120px}
  .count .n{font-size:20px;font-weight:700}
  .count .l{font-size:11px;color:var(--muted);font-family:var(--mono)}
  a.link{color:var(--accent);text-decoration:none}
</style>
</head>
<body>
<header>
  <h1>Palm<span class="k">kash</span> USSD · Dashboard</h1>
  <span class="pill <?= $pdo ? 'ok' : 'bad' ?>">MySQL <?= $pdo ? 'connected' : 'down' ?></span>
  <span class="pill <?= $redis ? 'ok' : 'bad' ?>">Redis <?= $redis ? 'connected' : 'down' ?></span>
  <nav>
    <a href="?tab=sessions" class="<?= $tab==='sessions'?'active':'' ?>">Live Sessions</a>
    <a href="?tab=logs"     class="<?= $tab==='logs'?'active':'' ?>">Trace Logs</a>
    <a href="?tab=data"     class="<?= $tab==='data'?'active':'' ?>">Database</a>
  </nav>
</header>
<div class="wrap">

<?php if ($dbErr): ?><div class="card"><div class="body" style="color:var(--red)">MySQL: <?= h($dbErr) ?></div></div><?php endif; ?>
<?php if ($redisErr): ?><div class="card"><div class="body" style="color:var(--amber)">Redis: <?= h($redisErr) ?></div></div><?php endif; ?>

<?php /* ============================= SESSIONS ============================= */
if ($tab === 'sessions'):
    $sessions = ussd_sessions($redis);
?>
  <form class="inline" method="get">
    <input type="hidden" name="tab" value="sessions">
    <label class="muted">Auto-refresh</label>
    <select name="refresh" onchange="this.form.submit()">
      <?php foreach ([0=>'off',5=>'5s',10=>'10s',30=>'30s'] as $v=>$lbl): ?>
        <option value="<?= $v ?>" <?= $refresh==$v?'selected':'' ?>><?= $lbl ?></option>
      <?php endforeach; ?>
    </select>
    <span class="muted"><?= count($sessions) ?> active session(s) · TTL from SESSION_ID_EXP=<?= h($RED['session_id_expiry'] ?? '?') ?>s</span>
  </form>

  <?php if (!$sessions): ?>
    <div class="card"><div class="empty">No active USSD sessions in Redis right now.<br>Sessions expire after <?= h($RED['session_id_expiry'] ?? '?') ?>s. Dial in to see them appear here.</div></div>
  <?php else: ?>
    <div class="grid">
    <?php foreach ($sessions as $s):
        $parts = explode('_', $s['base'], 2);
        $msisdn = $parts[0]; $sid = $parts[1] ?? '';
        $cur = $s['state']['current_state'] ?? '—';
        $prev = $s['state']['previous_state'] ?? '—';
        $lang = $s['data']['session_language_pref'] ?? ($s['data']['language'] ?? '—');
        $route = $s['data']['routing_key'] ?? '';
    ?>
      <div class="sess">
        <div class="top">
          <span class="msisdn"><?= h($msisdn) ?></span>
          <span class="badge">TTL <?= (int)$s['ttl'] ?>s</span>
        </div>
        <div class="kv"><span class="k">session</span><span class="mono"><?= h($sid) ?></span></div>
        <div class="kv"><span class="k">state</span><span><span class="badge state">now: <?= h($cur) ?></span> <span class="badge">prev: <?= h($prev) ?></span></span></div>
        <div class="kv"><span class="k">language</span><span><?= h($lang) ?></span></div>
        <?php if ($route): ?><div class="kv"><span class="k">routing_key</span><span><?= h($route) ?></span></div><?php endif; ?>
        <?php if ($s['inputs']): ?>
          <div class="timeline">
            <div class="muted" style="font-size:11px;margin-bottom:4px">INPUT TIMELINE (<?= count($s['inputs']) ?>)</div>
            <?php foreach ($s['inputs'] as $iv): ?>
              <div class="step">
                <span class="muted"><?= h($iv['date'] ?? '') ?></span>
                · st<?= h($iv['state_id'] ?? '?') ?>
                · <span class="muted"><?= h($iv['input_name'] ?? '') ?></span>=<b><?= h($iv['input_value'] ?? '') ?></b>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php /* ============================= LOGS ============================= */
elseif ($tab === 'logs'):
    $files = log_files();
    $sel   = $_GET['file'] ?? ($files[0] ?? '');
    $filter= trim($_GET['q'] ?? '');
    $sel   = in_array($sel, $files, true) ? $sel : ($files[0] ?? '');
    $lines = $sel ? tail_file($sel, 500, $filter) : [];
?>
  <form class="inline" method="get">
    <input type="hidden" name="tab" value="logs">
    <select name="file">
      <?php foreach ($files as $f): ?>
        <option value="<?= h($f) ?>" <?= $f===$sel?'selected':'' ?>><?= h(basename($f)) ?> (<?= round(filesize($f)/1024,1) ?> KB)</option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="q" placeholder="filter (msisdn, sessionId, text)…" value="<?= h($filter) ?>">
    <button type="submit">Filter</button>
    <label class="muted">refresh</label>
    <select name="refresh" onchange="this.form.submit()">
      <?php foreach ([0=>'off',5=>'5s',10=>'10s'] as $v=>$lbl): ?>
        <option value="<?= $v ?>" <?= $refresh==$v?'selected':'' ?>><?= $lbl ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php if (!$files): ?>
    <div class="card"><div class="empty">No trace log files yet in <span class="mono">systemlog/tmp/</span>.<br>Set <span class="mono">application_log = trace</span> in conf/logging.ini and generate some traffic.</div></div>
  <?php else: ?>
    <div class="card">
      <h2><?= h(basename($sel)) ?> · last <?= count($lines) ?> line(s)<?= $filter?(' · filter “'.h($filter).'”'):'' ?></h2>
      <pre class="log"><?php
        foreach ($lines as $l) {
            $cls = '';
            if (stripos($l, 'error') !== false || stripos($l, 'exception') !== false || stripos($l, 'Curl error') !== false) $cls = 'err';
            elseif (stripos($l, 'warn') !== false || stripos($l, 'fail') !== false) $cls = 'warn';
            echo $cls ? '<span class="'.$cls.'">'.h($l)."</span>\n" : h($l)."\n";
        }
        if (!$lines) echo '<span class="muted">(no matching lines)</span>';
      ?></pre>
    </div>
  <?php endif; ?>

<?php /* ============================= DATA ============================= */
elseif ($tab === 'data'):
    $tables = ['palm_user_account','palm_ussd_states','palm_ussd_choices','palm_ussd_response_codes',
               'palm_ussd_merchant','palm_test_account','palm_log_session_data','palm_log_session_activity',
               'palm_log_session_input_values','palm_log_gas_reference','b_stations_start','b_stations_end',
               'b_route_times','data_mapper'];
    $counts = [];
    if ($pdo) foreach ($tables as $t) {
        try { $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
        catch (Throwable $e) { $counts[$t] = '—'; }
    }
?>
  <div class="card">
    <h2>Table row counts</h2>
    <div class="body"><div class="counts">
      <?php foreach ($counts as $t=>$n): ?>
        <div class="count"><div class="n"><?= h($n) ?></div><div class="l"><?= h($t) ?></div></div>
      <?php endforeach; ?>
    </div></div>
  </div>

  <div class="card">
    <h2>Registrations · palm_user_account (latest 50)</h2>
    <?php
      $rows = [];
      if ($pdo) { try { $rows = $pdo->query("SELECT account_id,msisdn,first_name,last_name,language,status FROM palm_user_account ORDER BY account_id DESC LIMIT 50")->fetchAll(); } catch (Throwable $e) {} }
      if ($rows): ?>
      <table>
        <tr><th>ID</th><th>MSISDN</th><th>First</th><th>Last</th><th>Lang</th><th>Status</th></tr>
        <?php foreach ($rows as $r): ?>
          <tr><td class="mono"><?= h($r['account_id']) ?></td><td class="mono"><?= h($r['msisdn']) ?></td>
              <td><?= h($r['first_name']) ?></td><td><?= h($r['last_name']) ?></td>
              <td><span class="badge"><?= h($r['language']) ?></span></td><td><?= h($r['status']) ?></td></tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?><div class="empty">No registrations yet.</div><?php endif; ?>
  </div>

  <div class="card">
    <h2>Payment / booking references (b_route_times · palm_log_gas_reference, latest 30)</h2>
    <?php
      $refRows = [];
      if ($pdo) {
        try {
          $refRows = $pdo->query("
            SELECT 'route' AS kind, msisdn, CONCAT(route_type,' @',time) AS detail, price AS amount, session_id FROM b_route_times
            UNION ALL
            SELECT 'gas', msisdn, CONCAT(gas_name,' x',order_type), gas_price, session_id FROM palm_log_gas_reference
            ORDER BY amount DESC LIMIT 30")->fetchAll();
        } catch (Throwable $e) {}
      }
      if ($refRows): ?>
      <table>
        <tr><th>Kind</th><th>MSISDN</th><th>Detail</th><th>Amount</th><th>Session</th></tr>
        <?php foreach ($refRows as $r): ?>
          <tr><td><span class="badge"><?= h($r['kind']) ?></span></td><td class="mono"><?= h($r['msisdn']) ?></td>
              <td><?= h($r['detail']) ?></td><td class="mono"><?= h(number_format((float)$r['amount'])) ?></td>
              <td class="mono muted"><?= h($r['session_id']) ?></td></tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?><div class="empty">No booking/payment references stored yet. These populate when a user runs a transport or gas flow.</div><?php endif; ?>
  </div>

<?php endif; ?>

<div class="muted" style="text-align:center;font-size:11px;margin-top:24px">
  Local dev tool · localhost only · reads Redis + MySQL + systemlog/tmp · <?= h(date('Y-m-d H:i:s')) ?>
</div>
</div>
</body>
</html>
