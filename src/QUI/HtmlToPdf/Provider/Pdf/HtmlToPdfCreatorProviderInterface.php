<?php

namespace QUI\HtmlToPdf\Provider\Pdf;

use QUI\Locale;
use QUI\HtmlToPdf\Provider\Pdf\Exception\HtmlToPdfRequirementsNotMetException;

interface HtmlToPdfCreatorProviderInterface
{
    public function getTitle(?Locale $locale = null): string;
    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface;

    /**
     * Check if all requirements are met for this creator provider.
     *
     * @throws HtmlToPdfRequirementsNotMetException
     */
    public function checkRequirements(): void;
}
