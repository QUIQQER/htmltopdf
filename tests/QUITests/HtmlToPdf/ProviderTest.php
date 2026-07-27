<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\Config;
use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageRequirementsNotMetException;
use QUI\HtmlToPdf\Provider\Image\ImageMagick\Converter;
use QUI\HtmlToPdf\Provider\Image\ImageMagick\Provider as ImageMagickProvider;
use QUI\HtmlToPdf\Provider\Pdf\Exception\HtmlToPdfRequirementsNotMetException;
use QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless\Creator as ChromeHeadlessCreator;
use QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless\Provider as ChromeHeadlessProvider;
use QUI\HtmlToPdf\Provider\Pdf\Mpdf\Creator as MpdfCreator;
use QUI\HtmlToPdf\Provider\Pdf\Mpdf\Provider as MpdfProvider;
use ReflectionMethod;

use function chmod;
use function tempnam;
use function unlink;

class ProviderTest extends TestCase
{
    #[Test]
    public function mpdfProviderExposesItsTitleAndCreator(): void
    {
        $provider = new MpdfProvider();

        $this->assertNotSame('', $provider->getTitle());
        $this->assertInstanceOf(MpdfCreator::class, $provider->getHtmlToPdfCreator());
        $provider->checkRequirements();
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function imageMagickProviderExposesItsTitleAndConverter(): void
    {
        $provider = new ImageMagickProvider();

        $this->assertNotSame('', $provider->getTitle());
        $provider->checkRequirements();
        $this->assertInstanceOf(Converter::class, $provider->getPdfToImageConverter());
    }

    #[Test]
    public function chromeProviderExposesItsTitleAndCreatorWithoutStartingBrowser(): void
    {
        $provider = new ChromeHeadlessProvider();

        $this->assertNotSame('', $provider->getTitle());
        $this->assertInstanceOf(
            ChromeHeadlessCreator::class,
            $provider->getHtmlToPdfCreator()
        );
    }

    #[Test]
    public function missingImageMagickExecutableIsRejected(): void
    {
        $config = $this->getPackageConfig();
        $originalExecutable = $config->get('image_magick', 'convert_executable');
        $config->set('image_magick', 'convert_executable', '/path/to/missing-convert');

        try {
            $this->expectException(PdfToImageRequirementsNotMetException::class);
            (new ImageMagickProvider())->checkRequirements();
        } finally {
            $config->set('image_magick', 'convert_executable', $originalExecutable);
        }
    }

    #[Test]
    public function nonExecutableImageMagickBinaryIsRejected(): void
    {
        $executable = tempnam(sys_get_temp_dir(), 'htmltopdf-non-executable-');
        $this->assertNotFalse($executable);
        chmod($executable, 0600);

        $config = $this->getPackageConfig();
        $originalExecutable = $config->get('image_magick', 'convert_executable');
        $config->set('image_magick', 'convert_executable', $executable);

        try {
            $this->expectException(PdfToImageRequirementsNotMetException::class);
            (new ImageMagickProvider())->checkRequirements();
        } finally {
            $config->set('image_magick', 'convert_executable', $originalExecutable);
            unlink($executable);
        }
    }

    #[Test]
    public function missingChromeExecutableIsRejected(): void
    {
        $config = $this->getPackageConfig();
        $originalExecutable = $config->get('chrome_headless', 'executable');
        $missingExecutable = '/path/to/configured-but-missing-chrome';
        $config->set('chrome_headless', 'executable', $missingExecutable);

        try {
            try {
                (new ChromeHeadlessProvider())->getHtmlToPdfCreator();
                $this->fail('A missing Chrome executable was accepted.');
            } catch (HtmlToPdfRequirementsNotMetException $exception) {
                $this->assertSame(
                    $missingExecutable,
                    $exception->getContext()['locale'][2]['path']
                );
            }
        } finally {
            $config->set('chrome_headless', 'executable', $originalExecutable);
        }
    }

    #[Test]
    public function macOsChromeApplicationIsUsedAsPlatformFallback(): void
    {
        $executable = tempnam(sys_get_temp_dir(), 'htmltopdf-macos-chrome-');
        $this->assertNotFalse($executable);

        $method = new ReflectionMethod(
            ChromeHeadlessProvider::class,
            'getMacOsChromeExecutablePath'
        );
        $provider = new ChromeHeadlessProvider();

        try {
            $this->assertSame(
                $executable,
                $method->invoke($provider, 'Darwin', $executable)
            );
            $this->assertNull(
                $method->invoke($provider, 'Linux', $executable)
            );
            $this->assertNull(
                $method->invoke($provider, 'Darwin', $executable . '-missing')
            );
        } finally {
            unlink($executable);
        }
    }

    private function getPackageConfig(): Config
    {
        $config = \QUI::getPackage('quiqqer/htmltopdf')->getConfig();
        $this->assertInstanceOf(Config::class, $config);

        return $config;
    }
}
