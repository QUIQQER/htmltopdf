<?php

namespace QUI\HtmlToPdf\Provider\Image;

interface ProviderRepositoryInterface
{
    /**
     * Get the PdfToImageConverter provider that is currently set up as default for this system.
     */
    public function getCurrentProvider(): PdfToImageConverterProviderInterface;

    /**
     * @return PdfToImageConverterProviderInterface[]
     */
    public function getAllProviders(): array;
}
