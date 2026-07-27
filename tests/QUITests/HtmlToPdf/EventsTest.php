<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\Config;
use QUI\HtmlToPdf\Events;
use QUI\Package\Package;
use Smarty;

class EventsTest extends TestCase
{
    #[Test]
    public function legacyImageMagickConfigurationIsMigrated(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(
                static fn(string $section, string $key): string|false => match ([$section, $key]) {
                    ['settings', 'binary_convert'] => '/usr/bin/convert',
                    ['image_magick', 'convert_executable'] => false
                }
            );
        $config->expects($this->once())
            ->method('set')
            ->with('image_magick', 'convert_executable', '/usr/bin/convert');
        $config->expects($this->once())
            ->method('save');

        $package = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();
        $package->method('getConfig')
            ->willReturn($config);

        Events::onPackageSetup($package);
    }

    #[Test]
    public function existingImageMagickConfigurationIsKept(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->willReturnCallback(
                static fn(string $section, string $key): string => match ([$section, $key]) {
                    ['settings', 'binary_convert'] => '/legacy/convert',
                    ['image_magick', 'convert_executable'] => '/configured/convert'
                }
            );
        $config->expects($this->never())
            ->method('set');
        $config->expects($this->never())
            ->method('save');

        $package = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfig'])
            ->getMock();
        $package->method('getConfig')
            ->willReturn($config);

        Events::onPackageSetup($package);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function smartyFunctionIsRegistered(): void
    {
        $smarty = $this->createMock(Smarty::class);
        $smarty->expects($this->once())
            ->method('registerPlugin')
            ->with(
                'function',
                'imageBase64',
                $this->isCallable()
            );

        Events::onSmartyInit($smarty);
    }
}
