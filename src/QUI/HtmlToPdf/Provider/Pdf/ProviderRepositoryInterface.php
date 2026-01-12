<?php

namespace QUI\HtmlToPdf\Provider\Pdf;

use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterProviderInterface;

interface ProviderRepositoryInterface
{
    /**
     * Get the HtmlToPdfCreator provider that is currently set up as default for this system.
     */
    public function getCurrentProvider(): HtmlToPdfCreatorProviderInterface;

    /**
     * @return HtmlToPdfCreatorProviderInterface[]
     */
    public function getAllProviders(): array;
}
