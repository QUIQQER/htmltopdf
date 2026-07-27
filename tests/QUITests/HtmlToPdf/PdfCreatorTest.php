<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\PdfCreator;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterInterface;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterProviderInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use RuntimeException;

use function file_exists;
use function tempnam;
use function unlink;

class PdfCreatorTest extends TestCase
{
    #[Test]
    public function imageConverterIsNotCreatedForPdfGeneration(): void
    {
        $pdfFile = tempnam(sys_get_temp_dir(), 'htmltopdf-test-');
        $this->assertNotFalse($pdfFile);

        $htmlToPdfCreator = $this->createMock(HtmlToPdfCreatorInterface::class);
        $htmlToPdfCreator->expects($this->once())
            ->method('createPdf')
            ->willReturn($pdfFile);

        $imageConverterProvider = $this->createMock(PdfToImageConverterProviderInterface::class);
        $imageConverterProvider->expects($this->never())
            ->method('getPdfToImageConverter');

        $pdfCreator = new PdfCreator(
            $htmlToPdfCreator,
            pdfToImageConverterProvider: $imageConverterProvider
        );

        try {
            $this->assertSame($pdfFile, $pdfCreator->createPdf(new Document()));
        } finally {
            if (file_exists($pdfFile)) {
                unlink($pdfFile);
            }
        }
    }

    #[Test]
    public function imageConverterIsCreatedWhenImageConversionIsRequested(): void
    {
        $pdfFile = tempnam(sys_get_temp_dir(), 'htmltopdf-test-');
        $this->assertNotFalse($pdfFile);
        $expectedImages = ['/tmp/htmltopdf-test.jpg'];

        $htmlToPdfCreator = $this->createMock(HtmlToPdfCreatorInterface::class);
        $htmlToPdfCreator->expects($this->once())
            ->method('createPdf')
            ->willReturn($pdfFile);

        $imageConverter = $this->createMock(PdfToImageConverterInterface::class);
        $imageConverter->expects($this->once())
            ->method('convertPdfToImage')
            ->with($pdfFile)
            ->willReturn($expectedImages);

        $imageConverterProvider = $this->createMock(PdfToImageConverterProviderInterface::class);
        $imageConverterProvider->expects($this->once())
            ->method('getPdfToImageConverter')
            ->willReturn($imageConverter);

        $pdfCreator = new PdfCreator(
            $htmlToPdfCreator,
            pdfToImageConverterProvider: $imageConverterProvider
        );

        $this->assertSame(
            $expectedImages,
            $pdfCreator->createPdfAndConvertToImage(new Document())
        );
        $this->assertFileDoesNotExist($pdfFile);
    }

    #[Test]
    public function intermediatePdfIsDeletedWhenImageConversionFails(): void
    {
        $pdfFile = tempnam(sys_get_temp_dir(), 'htmltopdf-test-');
        $this->assertNotFalse($pdfFile);

        $htmlToPdfCreator = $this->createMock(HtmlToPdfCreatorInterface::class);
        $htmlToPdfCreator->method('createPdf')
            ->willReturn($pdfFile);

        $imageConverter = $this->createMock(PdfToImageConverterInterface::class);
        $imageConverter->method('convertPdfToImage')
            ->willThrowException(new RuntimeException('Expected conversion failure.'));

        $pdfCreator = new PdfCreator($htmlToPdfCreator, $imageConverter);

        try {
            $pdfCreator->createPdfAndConvertToImage(new Document());
            $this->fail('Image conversion unexpectedly succeeded.');
        } catch (RuntimeException $Exception) {
            $this->assertSame('Expected conversion failure.', $Exception->getMessage());
            $this->assertFileDoesNotExist($pdfFile);
        } finally {
            if (file_exists($pdfFile)) {
                unlink($pdfFile);
            }
        }
    }
}
