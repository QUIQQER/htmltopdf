<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\SmartyFunctions;
use QUI\Projects\Media\Image;
use Smarty_Internal_Template;

use function base64_encode;
use function file_exists;
use function file_put_contents;
use function rename;
use function tempnam;
use function unlink;

class SmartyFunctionsTest extends TestCase
{
    #[Test]
    public function cachedSvgConversionUsesPngMimeType(): void
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'htmltopdf-svg-');
        $this->assertNotFalse($temporaryFile);

        $svgFile = $temporaryFile . '.svg';
        $pngFile = $svgFile . '.png';
        $pngContent = "\x89PNG\r\n\x1a\ncached-png";

        rename($temporaryFile, $svgFile);
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($pngFile, $pngContent);

        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getAttribute',
                'getFullPath',
                'getWidth',
                'getHeight',
                'createResizeCache'
            ])
            ->getMock();
        $image->method('getAttribute')
            ->with('mime_type')
            ->willReturn('image/svg+xml');
        $image->method('getFullPath')
            ->willReturn($svgFile);
        $image->method('getWidth')
            ->willReturn(100);
        $image->method('getHeight')
            ->willReturn(100);
        $image->method('createResizeCache')
            ->with(100, 100)
            ->willReturn($svgFile);

        try {
            $result = SmartyFunctions::imageBase64(
                [
                    'image' => $image,
                    'svgtopng' => true
                ],
                $this->createStub(Smarty_Internal_Template::class)
            );

            $this->assertSame(
                'data:image/png;base64,' . base64_encode($pngContent),
                $result
            );
        } finally {
            if (file_exists($svgFile)) {
                unlink($svgFile);
            }

            if (file_exists($pngFile)) {
                unlink($pngFile);
            }
        }
    }
}
