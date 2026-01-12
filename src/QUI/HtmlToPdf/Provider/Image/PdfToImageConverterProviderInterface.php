<?php

namespace QUI\HtmlToPdf\Provider\Image;

use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageRequirementsNotMetException;
use QUI\Locale;

interface PdfToImageConverterProviderInterface
{
    public function getTitle(?Locale $locale = null): string;
    public function getPdfToImageConverter(): PdfToImageConverterInterface;

    /**
     * Check if all requirements are met for this converter provider.
     *
     * @throws PdfToImageRequirementsNotMetException
     */
    public function checkRequirements(): void;
}
