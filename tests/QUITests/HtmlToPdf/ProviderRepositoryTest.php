<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\Config;
use QUI\Exception;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterProviderInterface;
use QUI\HtmlToPdf\Provider\Image\ProviderRepository as ImageProviderRepository;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorProviderInterface;
use QUI\HtmlToPdf\Provider\Pdf\Mpdf\Creator as MpdfCreator;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepository as PdfProviderRepository;
use QUI\Package\Manager;
use QUI\Package\Package;

class ProviderRepositoryTest extends TestCase
{
    #[Test]
    public function configuredProvidersCanBeResolved(): void
    {
        $this->assertInstanceOf(
            HtmlToPdfCreatorProviderInterface::class,
            (new PdfProviderRepository())->getCurrentProvider()
        );
        $this->assertInstanceOf(
            PdfToImageConverterProviderInterface::class,
            (new ImageProviderRepository())->getCurrentProvider()
        );
    }

    #[Test]
    public function registeredProvidersCanBeDiscovered(): void
    {
        $pdfProviders = (new PdfProviderRepository())->getAllProviders();
        $imageProviders = (new ImageProviderRepository())->getAllProviders();

        $this->assertContainsOnlyInstancesOf(
            HtmlToPdfCreatorProviderInterface::class,
            $pdfProviders
        );
        $this->assertContainsOnlyInstancesOf(
            PdfToImageConverterProviderInterface::class,
            $imageProviders
        );
        $this->assertNotEmpty($pdfProviders);
        $this->assertNotEmpty($imageProviders);
    }

    /**
     * @param class-string<PdfProviderRepository|ImageProviderRepository> $repositoryClass
     */
    #[Test]
    #[DataProvider('repositoryClasses')]
    public function missingConfigurationIsRejected(string $repositoryClass, string $_configKey): void
    {
        $package = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();
        $package->method('getConfig')
            ->willReturn(null);

        $repository = new $repositoryClass(
            $this->createPackageManager($package)
        );

        $this->expectException(Exception::class);
        $repository->getCurrentProvider();
    }

    /**
     * @param class-string<PdfProviderRepository|ImageProviderRepository> $repositoryClass
     */
    #[Test]
    #[DataProvider('repositoryClasses')]
    public function emptyProviderClassIsRejected(string $repositoryClass, string $configKey): void
    {
        $repository = new $repositoryClass(
            $this->createPackageManagerWithConfigValue($configKey, false)
        );

        $this->expectException(Exception::class);
        $repository->getCurrentProvider();
    }

    /**
     * @param class-string<PdfProviderRepository|ImageProviderRepository> $repositoryClass
     */
    #[Test]
    #[DataProvider('repositoryClasses')]
    public function nonexistentProviderClassIsRejected(string $repositoryClass, string $configKey): void
    {
        $repository = new $repositoryClass(
            $this->createPackageManagerWithConfigValue($configKey, 'NonexistentProviderClass')
        );

        $this->expectException(Exception::class);
        $repository->getCurrentProvider();
    }

    /**
     * @param class-string<PdfProviderRepository|ImageProviderRepository> $repositoryClass
     */
    #[Test]
    #[DataProvider('repositoryClasses')]
    public function classWithoutProviderInterfaceIsRejected(string $repositoryClass, string $configKey): void
    {
        $repository = new $repositoryClass(
            $this->createPackageManagerWithConfigValue($configKey, MpdfCreator::class)
        );

        $this->expectException(Exception::class);
        $repository->getCurrentProvider();
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function repositoryClasses(): array
    {
        return [
            'PDF repository' => [
                PdfProviderRepository::class,
                'html_to_pdf_creator_provider'
            ],
            'image repository' => [
                ImageProviderRepository::class,
                'pdf_to_image_converter_provider'
            ]
        ];
    }

    private function createPackageManager(Package $package): Manager
    {
        $packageManager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getInstalledPackage'])
            ->getMock();
        $packageManager->method('getInstalledPackage')
            ->with('quiqqer/htmltopdf')
            ->willReturn($package);

        return $packageManager;
    }

    private function createPackageManagerWithConfigValue(string $configKey, mixed $value): Manager
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('settings', $configKey)
            ->willReturn($value);

        $package = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();
        $package->method('getConfig')
            ->willReturn($config);

        return $this->createPackageManager($package);
    }
}
