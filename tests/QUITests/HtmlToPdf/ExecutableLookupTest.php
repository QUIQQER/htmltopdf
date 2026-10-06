<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Config;
use QUI\HtmlToPdf\Provider\Image\ImageMagick\Provider as ImageMagickProvider;
use QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless\Provider as ChromeHeadlessProvider;
use ReflectionMethod;

require_once __DIR__ . '/LogIsolationTrait.php';

class ExecutableLookupTest extends TestCase
{
    use LogIsolationTrait;

    private string $directory;
    private string|false $previousPath;
    private mixed $previousEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->isolateQuiqqerLog();
        $this->directory = sys_get_temp_dir() . '/htmltopdf lookup ; ' . uniqid();
        mkdir($this->directory, 0700);
        $this->previousPath = getenv('PATH');
        $this->previousEnvPath = $_ENV['PATH'] ?? null;
        putenv('PATH=' . $this->directory);
        $_ENV['PATH'] = $this->directory;
    }

    protected function tearDown(): void
    {
        putenv($this->previousPath === false ? 'PATH' : 'PATH=' . $this->previousPath);

        if ($this->previousEnvPath === null) {
            unset($_ENV['PATH']);
        } else {
            $_ENV['PATH'] = $this->previousEnvPath;
        }

        if (file_exists($this->directory . '/which')) {
            unlink($this->directory . '/which');
        }

        rmdir($this->directory);
        $this->restoreQuiqqerLog();
        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function executables(): array
    {
        return [
            'ImageMagick' => ['convert'],
            'Chrome' => ['google-chrome']
        ];
    }

    #[DataProvider('executables')]
    public function testLookupPreservesSpacesAndMetacharacters(string $executable): void
    {
        $this->writeWhich(<<<'PHP'
if (count($argv) !== 2) {
    exit(1);
}

echo __DIR__ . '/bin ' . $argv[1] . ' ; literal' . PHP_EOL;
PHP);

        $this->assertSame(
            $this->directory . '/bin ' . $executable . ' ; literal',
            $this->lookup($executable)
        );
    }

    #[DataProvider('executables')]
    public function testConfiguredPathTakesPrecedence(string $executable): void
    {
        $this->writeWhich("throw new RuntimeException('Lookup must not run.');");
        $configuredPath = $this->directory . '/configured executable';

        $this->assertSame($configuredPath, $this->lookup($executable, $configuredPath));
    }

    #[DataProvider('executables')]
    public function testFailedLookupRejectsOutput(string $executable): void
    {
        $this->writeWhich("echo '/invalid/path'; exit(1);");

        $this->assertSame($this->fallback($executable), $this->lookup($executable));
    }

    #[DataProvider('executables')]
    public function testEmptyLookupUsesFallback(string $executable): void
    {
        $this->writeWhich('exit(0);');

        $this->assertSame($this->fallback($executable), $this->lookup($executable));
    }

    #[DataProvider('executables')]
    public function testMissingWhichUsesFallback(string $executable): void
    {
        $this->assertSame($this->fallback($executable), $this->lookup($executable));
    }

    #[DataProvider('executables')]
    public function testTimeoutUsesFallback(string $executable): void
    {
        $this->writeWhich("usleep(6_000_000); echo '/late/path';");

        $this->assertSame($this->fallback($executable), $this->lookup($executable));
    }

    private function writeWhich(string $body): void
    {
        file_put_contents($this->directory . '/which', '#!' . PHP_BINARY . "\n<?php\n" . $body);
        chmod($this->directory . '/which', 0700);
    }

    private function lookup(string $executable, string $configuredPath = ''): ?string
    {
        if ($executable === 'convert') {
            $provider = new ImageMagickProvider();
            $method = 'getConvertExecutablePath';
            $section = 'image_magick';
            $setting = 'convert_executable';
        } else {
            $provider = new ChromeHeadlessProvider();
            $method = 'getGoogleChromeExecutablePath';
            $section = 'chrome_headless';
            $setting = 'executable';
        }

        $config = \QUI::getPackage('quiqqer/htmltopdf')->getConfig();
        $this->assertInstanceOf(Config::class, $config);
        $previousValue = $config->get($section, $setting);
        $config->set($section, $setting, $configuredPath);

        try {
            return (new ReflectionMethod($provider, $method))->invoke($provider);
        } finally {
            $config->set($section, $setting, $previousValue);
        }
    }

    private function fallback(string $executable): ?string
    {
        if ($executable === 'convert') {
            return null;
        }

        $method = new ReflectionMethod(ChromeHeadlessProvider::class, 'getMacOsChromeExecutablePath');

        return $method->invoke(new ChromeHeadlessProvider());
    }
}
