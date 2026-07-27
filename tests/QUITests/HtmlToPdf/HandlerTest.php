<?php

namespace QUITests\HtmlToPdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\HtmlToPdf\Handler;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterProviderInterface;
use QUI\HtmlToPdf\Provider\Image\ProviderRepositoryInterface as ImageProviderRepositoryInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorProviderInterface;
use QUI\HtmlToPdf\Provider\Pdf\ProviderRepositoryInterface as PdfProviderRepositoryInterface;

class HandlerTest extends TestCase
{
    #[Test]
    public function pdfCreatorIsBuiltOnceWithoutInitializingImageConverter(): void
    {
        $htmlToPdfCreator = $this->createMock(HtmlToPdfCreatorInterface::class);

        $pdfProvider = $this->createMock(HtmlToPdfCreatorProviderInterface::class);
        $pdfProvider->expects($this->once())
            ->method('getHtmlToPdfCreator')
            ->willReturn($htmlToPdfCreator);

        $pdfProviderRepository = $this->createMock(PdfProviderRepositoryInterface::class);
        $pdfProviderRepository->expects($this->once())
            ->method('getCurrentProvider')
            ->willReturn($pdfProvider);

        $imageProvider = $this->createMock(PdfToImageConverterProviderInterface::class);
        $imageProvider->expects($this->never())
            ->method('getPdfToImageConverter');

        $imageProviderRepository = $this->createMock(ImageProviderRepositoryInterface::class);
        $imageProviderRepository->expects($this->once())
            ->method('getCurrentProvider')
            ->willReturn($imageProvider);

        $handler = new Handler($pdfProviderRepository, $imageProviderRepository);
        $pdfCreator = $handler->getPdfCreator();

        $this->assertSame($pdfCreator, $handler->getPdfCreator());
    }

    #[Test]
    public function repositoriesAreOptionalConstructorDependencies(): void
    {
        $this->assertInstanceOf(Handler::class, new Handler());
    }
}
