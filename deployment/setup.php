<?php
declare(strict_types=1);
use App\Models\User;
use Database\Seeders\LearningContentSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__).'/exampractice';
$lockFile = $root.'/storage/installed.lock';
header('Cache-Control: no-store, private');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if (! is_dir($root) || ! is_file($root.'/setup-token.php') || is_file($lockFile)) {
    http_response_code(404);
    exit('Not found.');
}
$https = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if (! $https && ! $local) {
    http_response_code(400);
    exit('Buka halaman ini melalui HTTPS setelah SSL domain aktif.');
}
session_name('ep_setup');
session_start(['cookie_httponly' => true, 'cookie_secure' => $https, 'cookie_samesite' => 'Strict', 'use_strict_mode' => true]);
$_SESSION['setup_csrf'] ??= bin2hex(random_bytes(24));
$error = null;
$success = false;
$requiredExtensions = ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'hash', 'iconv', 'intl', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session', 'simplexml', 'tokenizer', 'xml', 'xmlreader', 'xmlwriter', 'zip', 'zlib'];
$checks = ['PHP 8.4+' => PHP_VERSION_ID >= 80400, 'Dependensi aplikasi' => is_file($root.'/vendor/autoload.php'), 'Folder storage' => is_writable($root.'/storage'), 'Folder bootstrap/cache' => is_writable($root.'/bootstrap/cache'), 'Konfigurasi aplikasi' => is_writable($root)];
foreach ($requiredExtensions as $extension) {
    $checks['PHP '.$extension] = extension_loaded($extension);
}
$ready = ! in_array(false, $checks, true);
$tokenHash = require $root.'/setup-token.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! hash_equals($_SESSION['setup_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Sesi formulir berakhir. Muat ulang halaman ini.';
    } elseif (isset($_POST['unlock'])) {
        $ratePath = $root.'/storage/setup-rate.json';
        $handle = null;
        try {
            $handle = @fopen($ratePath, 'c+');
            if ($handle === false) {
                throw new RuntimeException('Installer tidak dapat menulis exampractice/storage/setup-rate.json. Periksa apakah folder storage tersedia dan dapat ditulis oleh PHP, serta kepemilikan file melalui File Manager hosting. Tidak perlu menghapus database.');
            }
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('Installer tidak dapat mengunci file pemeriksaan kode. Hubungi dukungan hosting untuk memeriksa akses file pada folder exampractice/storage.');
            }
            $rate = json_decode(stream_get_contents($handle) ?: '{}', true) ?: [];
            if (($rate['until'] ?? 0) < time()) {
                $rate = ['count' => 0, 'until' => time() + 900];
            }
            if ($rate['count'] >= 10) {
                $error = 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.';
            } elseif (hash_equals($tokenHash, hash('sha256', trim((string) ($_POST['setup_code'] ?? ''))))) {
                if (! session_regenerate_id(true)) {
                    throw new RuntimeException('Sesi PHP tidak dapat diperbarui. Periksa konfigurasi session.save_path melalui dukungan hosting.');
                }
                $rate['count'] = 0;
            } else {
                $rate['count']++;
                $error = 'Kode pemasangan tidak sesuai.';
            }
            $encodedRate = json_encode($rate);
            if (! rewind($handle) || ! ftruncate($handle, 0) || fwrite($handle, $encodedRate) !== strlen($encodedRate) || ! fflush($handle)) {
                throw new RuntimeException('Status pemeriksaan kode tidak dapat disimpan. Periksa izin tulis dan ruang penyimpanan hosting.');
            }
            if ($error === null) {
                $_SESSION['setup_authorized'] = time();
            }
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } finally {
            if (is_resource($handle)) {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
    } elseif (isset($_POST['install']) && ($_SESSION['setup_authorized'] ?? 0) > time() - 1800 && $ready) {
        $mutex = @fopen($root.'/storage/setup-install.mutex', 'c+');
        if ($mutex === false) {
            $error = 'Installer tidak dapat menulis file pengunci pada exampractice/storage. Periksa izin tulis folder dan ruang penyimpanan hosting.';
        } elseif (! flock($mutex, LOCK_EX | LOCK_NB)) {
            $error = 'Pemasangan sedang berjalan. Tunggu sampai selesai.';
            fclose($mutex);
        } else {
            $createdEnvironment = false;
            try {
                if (is_file($lockFile)) {
                    throw new RuntimeException('Sistem sudah terpasang.');
                }
                $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
                $port = filter_var($_POST['db_port'] ?? 3306, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
                $database = trim((string) ($_POST['db_database'] ?? ''));
                $dbUser = trim((string) ($_POST['db_username'] ?? ''));
                $dbPassword = (string) ($_POST['db_password'] ?? '');
                $url = rtrim(trim((string) ($_POST['app_url'] ?? '')), '/');
                $name = trim((string) ($_POST['name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $timezone = (string) ($_POST['timezone'] ?? 'Asia/Tokyo');
                if (! preg_match('/^[a-zA-Z0-9.\-]+$/', $host) || ! $port || ! preg_match('/^[a-zA-Z0-9_\-]{1,128}$/', $database) || ! preg_match('/^[a-zA-Z0-9_\-]{1,128}$/', $dbUser)) {
                    throw new RuntimeException('Periksa host, port, nama database, dan username database.');
                }
                $urlParts = parse_url($url);
                if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array($urlParts['scheme'] ?? '', $local ? ['http', 'https'] : ['https'], true) || ! empty($urlParts['path']) || isset($urlParts['query']) || isset($urlParts['fragment']) || isset($urlParts['user'])) {
                    throw new RuntimeException('URL harus berupa domain atau subdomain utama, misalnya https://latihan.domain.com.');
                }
                if ($name === '' || mb_strlen($name) > 100 || ! preg_match('/^[A-Za-z0-9_-]{3,50}$/', $username) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
                    throw new RuntimeException('Lengkapi nama, ID admin, dan email yang valid.');
                }
                if (strlen($password) < 10 || strlen($password) > 200 || ! preg_match('/[a-zA-Z]/', $password) || ! preg_match('/[0-9]/', $password) || $password !== ($_POST['password_confirmation'] ?? '')) {
                    throw new RuntimeException('Kata sandi minimal 10 karakter, berisi huruf dan angka. Konfirmasi harus sama.');
                }
                if (! in_array($timezone, ['Asia/Tokyo', 'Asia/Jakarta', 'UTC'], true)) {
                    throw new RuntimeException('Zona waktu tidak valid.');
                }
                foreach ([$dbPassword, $name, $email, $password] as $value) {
                    if (str_contains($value, "\n") || str_contains($value, "\r") || str_contains($value, "\0")) {
                        throw new RuntimeException('Isian tidak boleh mengandung baris baru.');
                    }
                }
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $dbUser, $dbPassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
                $progressPath = $root.'/storage/setup-progress.json';
                $progress = is_file($progressPath) ? json_decode(file_get_contents($progressPath), true) : null;
                $target = ['host' => $host, 'port' => $port, 'database' => $database];
                $sameTarget = $progress && ($progress['target'] ?? null) === $target;
                if (! $progress && is_file($root.'/.env')) {
                    throw new RuntimeException('Konfigurasi aplikasi sudah ada. Installer tidak akan menimpa instalasi yang ada.');
                }
                $tableCount = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
                if (! $sameTarget && $tableCount > 0) {
                    throw new RuntimeException('Gunakan database baru yang kosong. Database ini sudah berisi tabel.');
                }
                $appKey = $progress['app_key'] ?? 'base64:'.base64_encode(random_bytes(32));
                $progress = ['target' => $target, 'app_key' => $appKey];
                if (file_put_contents($progressPath, json_encode($progress), LOCK_EX) === false) {
                    throw new RuntimeException('Tidak dapat menyimpan status pemasangan.');
                }
                @chmod($progressPath, 0600);
                $env = [
                    'APP_NAME' => 'ExamPractice System', 'APP_ENV' => 'production', 'APP_KEY' => $appKey, 'APP_DEBUG' => 'false', 'APP_URL' => $url,
                    'APP_TIMEZONE' => $timezone, 'APP_PUBLIC_PATH' => str_replace('\\', '/', __DIR__), 'APP_LOCALE' => 'en', 'APP_FALLBACK_LOCALE' => 'en',
                    'APP_MAINTENANCE_DRIVER' => 'file', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => $host, 'DB_PORT' => (string) $port, 'DB_DATABASE' => $database,
                    'DB_USERNAME' => $dbUser, 'DB_PASSWORD' => $dbPassword, 'SESSION_DRIVER' => 'database', 'SESSION_LIFETIME' => '360',
                    'SESSION_SECURE_COOKIE' => $https ? 'true' : 'false', 'CACHE_STORE' => 'database', 'QUEUE_CONNECTION' => 'sync',
                    'LOG_CHANNEL' => 'daily', 'LOG_LEVEL' => 'warning', 'MAIL_MAILER' => 'log', 'BCRYPT_ROUNDS' => '12',
                ];
                $envText = '';
                foreach ($env as $key => $value) {
                    $encoded = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);
                    $envText .= $key.'="'.$encoded.'"'."\n";
                }
                if (file_put_contents($root.'/.env.installing', $envText, LOCK_EX) === false || ! rename($root.'/.env.installing', $root.'/.env')) {
                    throw new RuntimeException('Tidak dapat menyimpan konfigurasi database.');
                }
                @chmod($root.'/.env', 0600);
                require $root.'/vendor/autoload.php';
                $app = require $root.'/bootstrap/app.php';
                $app->usePublicPath(__DIR__);
                $kernel = $app->make(Kernel::class);
                $kernel->bootstrap();
                if ($kernel->call('migrate', ['--force' => true]) !== 0) {
                    file_put_contents($root.'/storage/logs/installer.log', str_replace([$dbPassword, $password], '[redacted]', $kernel->output()), FILE_APPEND | LOCK_EX);
                    throw new RuntimeException('Tabel database belum berhasil dibuat. Periksa storage/logs/installer.log. Setelah penyebab diperbaiki, gunakan database baru yang kosong; database sebelumnya tidak dihapus.');
                }
                DB::transaction(function () use ($name, $username, $email, $password) {
                    if (User::exists()) {
                        throw new RuntimeException('Akun sudah ada. Installer tidak akan mengganti akun yang ada.');
                    }
                    User::create(['name' => $name, 'username' => $username, 'email' => $email, 'password' => $password, 'role' => 'admin', 'active' => true, 'locale' => 'en']);
                    if (isset($_POST['sample_content'])) {
                        (new LearningContentSeeder)->run();
                    }
                });
                if (file_put_contents($lockFile, date(DATE_ATOM), LOCK_EX) === false) {
                    throw new RuntimeException('Tidak dapat mengunci installer. Batasi akses setup.php melalui File Manager.');
                }
                @chmod($lockFile, 0600);
                @unlink($progressPath);
                @unlink($root.'/setup-token.php');
                $_SESSION = [];
                session_destroy();
                $success = true;
                $cronPath = str_replace('\\', '/', $root.'/cron.php');
            } catch (PDOException $exception) {
                $error = 'Koneksi database gagal. Periksa data MySQL dari hPanel dan izin pengguna database.';
            } catch (Throwable $exception) {
                $error = $exception instanceof RuntimeException && ! ($exception instanceof QueryException) ? $exception->getMessage() : 'Pemasangan belum selesai. Periksa log privat storage/logs dan konfigurasi hosting, lalu coba lagi.';
                error_log('ExamPractice setup: '.get_class($exception).' (code '.$exception->getCode().')');
            } finally {
                flock($mutex, LOCK_UN);
                fclose($mutex);
            }
        }
    }
}
function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function oldValue(string $name, string $default = ''): string
{
    return h($_POST[$name] ?? $default);
}
$authorized = ($_SESSION['setup_authorized'] ?? 0) > time() - 1800;
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pemasangan | ExamPractice</title><style>
*{box-sizing:border-box}body{font:14px/1.7 'Segoe UI',sans-serif;color:#26384a;background:#f5f8fc;margin:0}header{border-bottom:1px solid #e0e7ef;background:#fff;padding:20px 28px;font-weight:700;font-size:20px}header span{color:#2260d4}main{max-width:820px;margin:35px auto;padding:0 22px}h1{font-size:28px;line-height:1.4}h2{font-size:18px;margin:28px 0 15px}p{color:#718298}label{display:block;font-weight:600;font-size:12px;margin-bottom:17px}input,select{font:14px 'Segoe UI',sans-serif;width:100%;padding:11px;border:1px solid #cbd6e4;border-radius:5px;margin-top:6px;background:#fff;color:#26384a}input:focus,select:focus{outline:2px solid #2260d455}input[type=checkbox]{width:16px;margin:0 9px 0 0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 20px}.wide{grid-column:1/-1}.button{display:inline-block;background:#2260d4;color:#fff;border:0;border-radius:5px;padding:12px 20px;text-decoration:none;font-weight:600;cursor:pointer;font-size:14px}.alert{padding:15px 19px;background:#fff1f1;border:1px solid #eac4cd;color:#b34257;border-radius:5px;margin:20px 0}.success{background:#eaf7ef;color:#238365;border-color:#c1e4cd}details{margin:18px 0;padding:15px 18px;background:#fff;border:1px solid #dde5ef;border-radius:5px}summary{cursor:pointer;font-weight:600}.check{display:flex;justify-content:space-between;border-bottom:1px solid #edf0f5;padding:5px}.ok{color:#16876b}.fail{color:#c34458}code{display:block;overflow-wrap:anywhere;padding:14px;background:#e9eff7;border-radius:5px}small{font-weight:400;color:#8091a4}form{margin:20px 0 40px}footer{margin:35px 0;font-size:11px;color:#9aa8b7}button:disabled{opacity:.5;cursor:wait}@media(max-width:600px){.grid{grid-template-columns:1fr}main{margin:25px auto}h1{font-size:24px}}
</style></head><body><header><span>Exam</span>Practice System</header><main>
<?php if ($success) { ?><h1>Sistem berhasil dipasang</h1><div class="alert success">Database dan akun admin sudah siap. Installer telah dikunci dan tidak dapat dipakai ulang.</div><p>Masuk menggunakan ID admin yang baru dibuat. Akun demo dengan kata sandi bawaan tidak dibuat di hosting.</p><a class="button" href="<?= h($url)?>/login">Buka halaman login</a><h2>Penjadwalan sesi yang waktunya habis</h2><p>Di hPanel, tambahkan Cron Job tipe PHP untuk file berikut, setiap menit. Pilih PHP 8.4 atau lebih baru.</p><code><?= h($cronPath)?></code><p>Timer di halaman latihan sudah otomatis mengumpulkan jawaban. Cron memastikan sesi juga diselesaikan saat browser peserta ditutup. Tanpa cron, sesi tersebut diselesaikan saat diakses kembali.</p><p>Anda boleh menghapus <strong>public_html/setup.php</strong> melalui File Manager setelah selesai.</p>
<?php } else { ?><h1>Pasang ExamPractice</h1><p>Konfigurasi awal untuk hosting Anda.</p><?php if ($error) { ?><div class="alert" role="alert"><?= h($error)?></div><?php } ?>
<?php if (! $authorized) { ?><form method="POST"><input type="hidden" name="csrf" value="<?= h($_SESSION['setup_csrf'])?>"><label>Kode pemasangan<input name="setup_code" required autocomplete="off" autofocus><small>Kode tersedia di file START-HERE-ID.txt dalam paket ZIP.</small></label><button class="button" name="unlock" value="1">Buka pemasangan</button></form>
<?php } else { ?><details <?= ! $ready ? 'open' : ''?>><summary><?= $ready ? 'Persyaratan server terpenuhi' : 'Ada persyaratan server yang belum terpenuhi'?></summary><?php foreach ($checks as $label => $ok) { ?><div class="check"><span><?= h($label)?></span><b class="<?= $ok ? 'ok' : 'fail'?>"><?= $ok ? 'OK' : 'Perlu diaktifkan'?></b></div><?php } ?></details>
<?php if ($ready) { ?><form method="POST" onsubmit="this.querySelector('button[type=submit]').textContent='Memasang, mohon tunggu...'; this.querySelector('button[type=submit]').disabled=true;"><input type="hidden" name="csrf" value="<?= h($_SESSION['setup_csrf'])?>"><input type="hidden" name="install" value="1"><h2>1. Website</h2><div class="grid"><label class="wide">URL website<input type="url" name="app_url" required value="<?= oldValue('app_url', ($https ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? ''))?>"></label><label>Zona waktu<select name="timezone"><?php foreach (['Asia/Tokyo', 'Asia/Jakarta', 'UTC'] as $zone) { ?><option <?= ($_POST['timezone'] ?? 'Asia/Tokyo') === $zone ? 'selected' : ''?>><?= h($zone)?></option><?php } ?></select></label></div><h2>2. Database MySQL</h2><p>Buat database baru dan pengguna database di hPanel, lalu masukkan detailnya.</p><div class="grid"><label>Host database<input name="db_host" value="<?= oldValue('db_host', 'localhost')?>" required></label><label>Port<input type="number" name="db_port" value="<?= oldValue('db_port', '3306')?>" min="1" max="65535" required></label><label>Nama database<input name="db_database" value="<?= oldValue('db_database')?>" required></label><label>Username database<input name="db_username" value="<?= oldValue('db_username')?>" required></label><label class="wide">Password database<input type="password" name="db_password" autocomplete="new-password"></label></div><h2>3. Akun administrator</h2><div class="grid"><label>Nama lengkap<input name="name" value="<?= oldValue('name')?>" maxlength="100" required></label><label>ID admin<input name="username" value="<?= oldValue('username')?>" pattern="[A-Za-z0-9_-]{3,50}" required></label><label class="wide">Email<input type="email" name="email" value="<?= oldValue('email')?>" required></label><label>Password<input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" required><small>Minimal 10 karakter, huruf dan angka.</small></label><label>Konfirmasi password<input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label></div><label><input type="checkbox" name="sample_content" value="1" checked>Isi contoh program, materi, dan soal untuk portofolio</label><button class="button" type="submit">Pasang sistem</button></form><?php } ?><?php } ?><?php } ?><footer>ExamPractice System · <?= date('Y')?></footer></main></body></html>
