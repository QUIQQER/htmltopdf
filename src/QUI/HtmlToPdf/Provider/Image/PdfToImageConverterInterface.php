<?php

namespace QUI\HtmlToPdf\Provider\Image;

use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageConversionFailedException;

interface PdfToImageConverterInterface
{
    /**
     * Convert a PDF to an image.
     * @param string $pdfFile - Full path to a PDF file
     * @return string[] - Full path to the converted image file(s)
     * @throws PdfToImageConversionFailedException
     */
    public function convertPdfToImage(string $pdfFile): array;
}
