<?php

namespace QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless;

use QUI;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorProviderInterface;
use QUI\Locale;
use Throwable;

use function putenv;

class Provider implements HtmlToPdfCreatorProviderInterface
{
    public function getTitle(?Locale $locale = null): string
    {
        if (is_null($locale)) {
            $locale = QUI::getLocale();
        }

        return $locale->get('quiqqer/htmltopdf', 'provider.ChromeHeadless.title');
    }

    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface
    {
        // Read Chrome binary path from settings
        try {
            $config = QUI::getPackage('quiqqer/htmltopdf')->getConfig();
            $chromePath = $config?->get('chrome_headless', 'executable');

            if (!empty($chromePath)) {
                putenv("CHROME_PATH=" . $chromePath);
            } else {
                // Fallback to default Chrome path
                putenv("CHROME_PATH=/usr/bin/google-chrome");
            }
        } catch (Throwable $Exception) {
            QUI\System\Log::writeException($Exception);
            // Fallback to default Chrome path
            putenv("CHROME_PATH=/usr/bin/google-chrome");
        }

        return new Creator();
    }
}
