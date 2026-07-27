<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageConversionFailedException;
use QUI\HtmlToPdf\Provider\Image\ImageMagick\Converter;

use function chmod;
use function file_exists;
use function file_put_contents;
use function tempnam;
use function unlink;

require_once __DIR__ . '/LogIsolationTrait.php';

class ConverterTest extends TestCase
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
    public function processFailureIsReportedAsConversionFailure(): void
    {
        $pdfFile = tempnam(sys_get_temp_dir(), 'htmltopdf-converter-');
        $this->assertNotFalse($pdfFile);

        try {
            $converter = new Converter('/path/to/nonexistent-convert');

            $this->expectException(PdfToImageConversionFailedException::class);
            $converter->convertPdfToImage($pdfFile);
        } finally {
            unlink($pdfFile);
        }
    }

    #[Test]
    public function missingProcessOutputIsReportedAsConversionFailure(): void
    {
        $pdfFile = tempnam(sys_get_temp_dir(), 'htmltopdf-converter-');
        $this->assertNotFalse($pdfFile);

        try {
            $converter = new Converter('/usr/bin/true');

            $this->expectException(PdfToImageConversionFailedException::class);
            $converter->convertPdfToImage($pdfFile);
        } finally {
            unlink($pdfFile);
        }
    }

    #[Test]
    public function numberedImageOutputIsReturnedInPageOrder(): void
    {
        $pdfFile = tempnam(sys_get_temp_dir(), 'htmltopdf-converter-');
        $fakeConvert = tempnam(sys_get_temp_dir(), 'htmltopdf-fake-convert-');
        $this->assertNotFalse($pdfFile);
        $this->assertNotFalse($fakeConvert);

        file_put_contents(
            $fakeConvert,
            <<<'PHP'
#!/usr/bin/env php
<?php
$output = $argv[count($argv) - 1];
$base = substr($output, 0, -4);
file_put_contents($base . '-0.jpg', 'page 1');
file_put_contents($base . '-1.jpg', 'page 2');
PHP
        );
        chmod($fakeConvert, 0700);

        $expectedImages = [
            $pdfFile . '-0.jpg',
            $pdfFile . '-1.jpg'
        ];

        try {
            $converter = new Converter($fakeConvert);
            $images = $converter->convertPdfToImage($pdfFile);

            $this->assertSame($expectedImages, $images);
            $this->assertSame('page 1', file_get_contents($images[0]));
            $this->assertSame('page 2', file_get_contents($images[1]));
        } finally {
            unlink($pdfFile);
            unlink($fakeConvert);

            foreach ($expectedImages as $image) {
                if (file_exists($image)) {
                    unlink($image);
                }
            }
        }
    }
}
