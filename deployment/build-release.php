<?php

declare(strict_types=1);

$project = dirname(__DIR__);
$delivery = dirname($project).'/delivery';
$stage = $delivery.'/ExamPractice-Hostinger';
$mode = $argv[1] ?? 'prepare';
if (! in_array($mode, ['prepare', 'pack'], true)) {
    throw new RuntimeException('Use prepare or pack.');
}
if (! is_dir($delivery)) {
    mkdir($delivery, 0755, true);
}
if ($mode === 'prepare') {
    if (! is_dir($stage)) {
        mkdir($stage, 0755, true);
    }
    $copy = function (string $source, string $destination) use (&$copy): void {
        if (is_link($source)) {
            return;
        }
        if (is_dir($source)) {
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }
            foreach (new DirectoryIterator($source) as $file) {
                if ($file->isDot() || in_array($file->getFilename(), ['database.sqlite', '.git', 'hot', 'storage'], true) || str_ends_with($file->getFilename(), '.log')) {
                    continue;
                }
                if (str_ends_with(str_replace('\\', '/', $source), 'bootstrap/cache') && $file->getFilename() !== '.gitignore') {
                    continue;
                }
                $copy($file->getPathname(), $destination.'/'.$file->getFilename());
            }
        } else {
            copy($source, $destination);
        }
    };
    foreach (['app', 'bootstrap', 'config', 'database', 'lang', 'resources', 'routes'] as $folder) {
        $copy($project.'/'.$folder, $stage.'/exampractice/'.$folder);
    }
    foreach (['artisan', 'composer.json', 'composer.lock', 'cron.php', '.env.example', 'README.md', 'PROJECT_SCOPE.md'] as $file) {
        $copy($project.'/'.$file, $stage.'/exampractice/'.$file);
    }
    foreach (['storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $folder) {
        if (! is_dir($stage.'/exampractice/'.$folder)) {
            mkdir($stage.'/exampractice/'.$folder, 0755, true);
        }
    }
    $copy($project.'/public', $stage.'/public_html');
    copy(__DIR__.'/index.php', $stage.'/public_html/index.php');
    copy(__DIR__.'/setup.php', $stage.'/public_html/setup.php');
    $token = bin2hex(random_bytes(20));
    file_put_contents($stage.'/exampractice/setup-token.php', "<?php\nreturn '".hash('sha256', $token)."';\n");
    $readme = "EXAMPRACTICE SYSTEM - PAKET SIAP UNGGAH HOSTINGER\n\n";
    $readme .= "Kode pemasangan: $token\nSimpan kode ini untuk membuka installer.\n\n";
    $readme .= "1. Di hPanel, aktifkan SSL dan pilih PHP 8.4 (atau versi yang lebih baru dan kompatibel).\n";
    $readme .= "2. Buat database MySQL baru beserta user database. Catat host, nama database, username, dan password.\n";
    $readme .= "3. Unggah dan ekstrak paket pada folder domain, satu tingkat DI ATAS public_html. Hasilnya:\n   domains/domain-anda.com/exampractice/\n   domains/domain-anda.com/public_html/\n   Hanya isi public_html yang boleh menjadi folder publik. Jangan menaruh folder exampractice di dalam public_html.\n";
    $readme .= "   Jika ZIP terlanjur diekstrak di dalam public_html, pindahkan folder exampractice satu tingkat ke atas dan pindahkan isi folder public_html hasil ekstrak ke public_html utama.\n";
    $readme .= "4. Buka https://domain-anda.com/setup.php, masukkan kode pemasangan, data database, dan akun admin pilihan Anda.\n";
    $readme .= "5. Setelah selesai, masuk ke website. Installer otomatis terkunci. Hapus public_html/setup.php melalui File Manager bila sudah selesai.\n\n";
    $readme .= "Tidak perlu menjalankan Composer, npm, atau perintah terminal di hosting. Vendor dan aset hasil build sudah ada.\n";
    $readme .= "Sistem produksi tidak memakai akun/password demo. Anda membuat akun admin sendiri saat instalasi.\n\n";
    $readme .= "CRON (direkomendasikan)\nTambahkan Cron Job tipe PHP di hPanel, setiap menit, menuju:\n/home/USER/domains/DOMAIN/exampractice/cron.php\nGunakan PHP 8.4+. Installer menampilkan path sebenarnya setelah pemasangan.\nTimer peserta tetap otomatis mengumpulkan jawaban saat halaman aktif. Cron menyelesaikan sesi saat browser ditutup; tanpa cron, sesi selesai saat diakses kembali.\n\n";
    $readme .= "PERSYARATAN\nHosting PHP dengan MySQL/MariaDB, SSL, mod_rewrite/LiteSpeed rewrite, PHP 8.4+, ekstensi pdo_mysql, mbstring, intl, zip, gd, curl, fileinfo, openssl, dan XML. Installer memeriksanya.\nFolder storage dan bootstrap/cache harus bisa ditulis PHP. Tidak perlu izin 777.\nGunakan domain atau subdomain khusus dengan direktori web kosong. Paket ini untuk pemasangan baru, bukan menimpa instalasi yang sudah berisi data.\n\n";
    $readme .= "FITUR\nAdmin/student login; program/module/package; soal A-D manual dan CSV/XLSX; materi Markdown; hak akses; timer sticky dan auto-submit; nilai dan riwayat; review/ekspor hasil; perangkat dan reset; bahasa EN/JA/ID.\n\n";
    $readme .= "BATASAN\nPenguncian perangkat mengenali browser, bukan identitas perangkat fisik. Screenshot tidak bisa dicegah sepenuhnya. Tidak ada watermark atau peringatan pindah tab.\n\n";
    $readme .= "PANDUAN HOSTINGER\nhttps://www.hostinger.com/support/6152127-how-to-deploy-laravel-8-at-hostinger/\nhttps://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/\n\nSetelah aktif, backup database dan folder exampractice/.env secara privat. Jangan mengganti APP_KEY pada instalasi yang sudah dipakai.\n";
    file_put_contents($stage.'/START-HERE-ID.txt', $readme);
    echo "Prepared: $stage\nInstall production Composer dependencies in $stage/exampractice, then run this script with pack.\n";
} else {
    if (! is_file($stage.'/exampractice/vendor/autoload.php')) {
        throw new RuntimeException('Production dependencies are missing.');
    }
    if (is_file($stage.'/exampractice/.env') || is_file($stage.'/exampractice/storage/installed.lock')) {
        throw new RuntimeException('Refusing to package an installed system or environment secrets.');
    }
    $zipPath = $delivery.'/ExamPractice-Hostinger.zip';
    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot create ZIP.');
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($stage) + 1));
        if ($file->isDir()) {
            $zip->addEmptyDir($relative);
        } else {
            $zip->addFile($file->getPathname(), $relative);
        }
    }
    $zip->close();
    echo "Release: $zipPath\nSHA256: ".hash_file('sha256',$zipPath)."\n";
}
