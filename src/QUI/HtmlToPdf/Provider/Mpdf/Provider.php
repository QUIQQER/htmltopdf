<?php

namespace QUI\HtmlToPdf\Provider\Mpdf;

use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\HtmlToPdfCreatorProviderInterface;
use QUI\Locale;
use QUI;

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
}
