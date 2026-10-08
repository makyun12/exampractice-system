<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InstallerUnlockTest extends TestCase
{
    public function test_unwritable_rate_file_shows_actionable_error_without_authorizing(): void
    {
        $output = $this->runInstaller(true, 'test-install-code');
        $this->assertStringContainsString('Installer tidak dapat menulis', $output);
        $this->assertStringContainsString('AUTHORIZED=no', $output);
        $this->assertStringNotContainsString('Fatal error', $output);
    }

    public function test_valid_code_unlocks_installer(): void
    {
        $this->assertStringContainsString('AUTHORIZED=yes', $this->runInstaller(false, 'test-install-code'));
    }

    public function test_invalid_code_does_not_unlock_installer(): void
    {
        $output = $this->runInstaller(false, 'incorrect-code');
        $this->assertStringContainsString('Kode pemasangan tidak sesuai.', $output);
        $this->assertStringContainsString('AUTHORIZED=no', $output);
    }

    private function runInstaller(bool $blocked, string $code): string
    {
        $base = sys_get_temp_dir().'/ep-unlock-'.bin2hex(random_bytes(8));
        mkdir($base.'/public_html', 0777, true);
        mkdir($base.'/exampractice/storage', 0777, true);
        mkdir($base.'/exampractice/bootstrap/cache', 0777, true);
        copy(dirname(__DIR__, 2).'/deployment/setup.php', $base.'/public_html/setup.php');
        file_put_contents($base.'/exampractice/setup-token.php', '<?php return '.var_export(hash('sha256', 'test-install-code'), true).';');
        if ($blocked) {
            mkdir($base.'/exampractice/storage/setup-rate.json');
        }
        $script = 'session_save_path('.var_export($base, true).'); session_name("ep_setup"); session_start(); $_SESSION["setup_csrf"]="test-csrf"; session_write_close();'
            .'$_SERVER["REQUEST_METHOD"]="POST"; $_SERVER["REMOTE_ADDR"]="127.0.0.1";'
            .'$_POST=["csrf"=>"test-csrf","unlock"=>"1","setup_code"=>'.var_export($code, true).'];'
            .'require '.var_export($base.'/public_html/setup.php', true).'; echo "AUTHORIZED=".(isset($_SESSION["setup_authorized"])?"yes":"no"); session_write_close();';
        try {
            $process = new Process([PHP_BINARY, '-r', $script]);
            $process->run();
            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());

            return $process->getOutput();
        } finally {
            $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($base);
        }
    }
}
