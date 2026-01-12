<?php

namespace QUI\HtmlToPdf\Provider\Pdf\Mpdf;

use QUI;
use QUI\HtmlToPdf\Provider\Pdf\Exception\HtmlToPdfRequirementsNotMetException;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorProviderInterface;
use QUI\Locale;

class Provider implements HtmlToPdfCreatorProviderInterface
{
    public function getTitle(?Locale $locale = null): string
    {
        if (is_null($locale)) {
            $locale = QUI::getLocale();
        }

        return $locale->get('quiqqer/htmltopdf', 'provider.Mpdf.title');
    }

    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface
    {
        return new Creator();
    }

    /**
     * @inheritDoc
     */
    public function checkRequirements(): void
    {
        // nothing to check
    }
}
