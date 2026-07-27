<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Cron;

use function file_exists;
use function file_put_contents;
use function touch;
use function unlink;

require_once __DIR__ . '/LogIsolationTrait.php';

class CronTest extends TestCase
{
    use LogIsolationTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->isolateQuiqqerLog();
    }

    protected function tearDown(): void
    {
        $this->restoreQuiqqerLog();
        parent::tearDown();
    }

    #[Test]
    public function missingRetentionPeriodDoesNotRunCleanup(): void
    {
        Cron::cleanFiles([]);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function filesOlderThanRetentionPeriodAreDeleted(): void
    {
        $varDir = \QUI::getPackage('quiqqer/htmltopdf')->getVarDir();
        $oldFile = $varDir . 'phpunit-old-file-' . uniqid() . '.pdf';

        file_put_contents($oldFile, 'old');
        touch($oldFile, 1);

        try {
            Cron::cleanFiles([
                'days' => 20_000
            ]);

            $this->assertFileDoesNotExist($oldFile);
        } finally {
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
    }
}
