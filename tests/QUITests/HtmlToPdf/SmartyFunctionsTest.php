<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\SmartyFunctions;
use QUI\Projects\Media\Image;
use RuntimeException;
use Smarty_Internal_Template;

use function base64_encode;
use function file_exists;
use function file_put_contents;
use function rename;
use function tempnam;
use function unlink;

require_once __DIR__ . '/LogIsolationTrait.php';

class SmartyFunctionsTest extends TestCase
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
    public function invalidImageParametersReturnEmptyString(): void
    {
        $smarty = $this->createStub(Smarty_Internal_Template::class);

        $this->assertSame('', SmartyFunctions::imageBase64([], $smarty));
        $this->assertSame('', SmartyFunctions::imageBase64(['image' => 123], $smarty));
        $this->assertSame(
            '',
            SmartyFunctions::imageBase64(
                ['image' => sys_get_temp_dir() . '/htmltopdf-missing-' . uniqid()],
                $smarty
            )
        );
    }

    #[Test]
    public function readableFileIsEmbeddedAsDataUri(): void
    {
        $imageFile = tempnam(sys_get_temp_dir(), 'htmltopdf-image-');
        $this->assertNotFalse($imageFile);
        file_put_contents($imageFile, 'plain image content');

        try {
            $result = SmartyFunctions::imageBase64(
                ['image' => $imageFile],
                $this->createStub(Smarty_Internal_Template::class)
            );

            $this->assertStringStartsWith('data:text/plain;base64,', $result);
            $this->assertStringEndsWith(base64_encode('plain image content'), $result);
        } finally {
            unlink($imageFile);
        }
    }

    #[Test]
    public function mediaImageResizeIsEmbeddedWithItsMimeType(): void
    {
        $imageFile = tempnam(sys_get_temp_dir(), 'htmltopdf-image-');
        $this->assertNotFalse($imageFile);
        file_put_contents($imageFile, 'resized image content');

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
            ->willReturn('image/jpeg');
        $image->method('getFullPath')
            ->willReturn($imageFile);
        $image->method('getWidth')
            ->willReturn(800);
        $image->method('getHeight')
            ->willReturn(600);
        $image->expects($this->once())
            ->method('createResizeCache')
            ->with(320, 240)
            ->willReturn($imageFile);

        try {
            $result = SmartyFunctions::imageBase64(
                [
                    'image' => $image,
                    'width' => 320,
                    'height' => 240
                ],
                $this->createStub(Smarty_Internal_Template::class)
            );

            $this->assertSame(
                'data:image/jpeg;base64,' . base64_encode('resized image content'),
                $result
            );
        } finally {
            unlink($imageFile);
        }
    }

    #[Test]
    public function mediaImageWithoutMimeTypeIsRejected(): void
    {
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();
        $image->method('getAttribute')
            ->with('mime_type')
            ->willReturn(null);

        $this->assertSame(
            '',
            SmartyFunctions::imageBase64(
                ['image' => $image],
                $this->createStub(Smarty_Internal_Template::class)
            )
        );
    }

    #[Test]
    public function mediaImageResizeExceptionReturnsEmptyString(): void
    {
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getAttribute',
                'getFullPath',
                'getWidth'
            ])
            ->getMock();
        $image->method('getAttribute')
            ->with('mime_type')
            ->willReturn('image/jpeg');
        $image->method('getFullPath')
            ->willReturn('/tmp/image.jpg');
        $image->method('getWidth')
            ->willThrowException(new RuntimeException('Expected resize failure.'));

        $this->assertSame(
            '',
            SmartyFunctions::imageBase64(
                ['image' => $image],
                $this->createStub(Smarty_Internal_Template::class)
            )
        );
    }

    #[Test]
    public function failedResizeFallsBackToOriginalImage(): void
    {
        $imageFile = tempnam(sys_get_temp_dir(), 'htmltopdf-image-');
        $this->assertNotFalse($imageFile);
        file_put_contents($imageFile, 'original image content');

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
            ->willReturn('image/jpeg');
        $image->method('getFullPath')
            ->willReturn($imageFile);
        $image->method('getWidth')
            ->willReturn(800);
        $image->method('getHeight')
            ->willReturn(600);
        $image->method('createResizeCache')
            ->willReturn(false);

        try {
            $this->assertSame(
                'data:image/jpeg;base64,' . base64_encode('original image content'),
                SmartyFunctions::imageBase64(
                    ['image' => $image],
                    $this->createStub(Smarty_Internal_Template::class)
                )
            );
        } finally {
            unlink($imageFile);
        }
    }

    #[Test]
    public function unreadableResizeResultIsRejected(): void
    {
        $missingImage = sys_get_temp_dir() . '/htmltopdf-missing-image-' . uniqid();
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
            ->willReturn('image/jpeg');
        $image->method('getFullPath')
            ->willReturn($missingImage);
        $image->method('getWidth')
            ->willReturn(800);
        $image->method('getHeight')
            ->willReturn(600);
        $image->method('createResizeCache')
            ->willReturn($missingImage);

        $this->assertSame(
            '',
            SmartyFunctions::imageBase64(
                ['image' => $image],
                $this->createStub(Smarty_Internal_Template::class)
            )
        );
    }

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
