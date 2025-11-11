<?php

namespace QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless;

use QUI;
use QUI\HtmlToPdf\Provider\Pdf\Exception\HtmlToPdfRequirementsNotMetException;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorProviderInterface;
use QUI\Locale;

use function file_exists;
use function is_executable;
use function is_null;
use function putenv;
use function trim;

class Provider implements HtmlToPdfCreatorProviderInterface
{
    public function getTitle(?Locale $locale = null): string
    {
        if (is_null($locale)) {
            $locale = QUI::getLocale();
        }

        return $locale->get('quiqqer/htmltopdf', 'provider.ChromeHeadless.title');
    }

    /**
     * @throws HtmlToPdfRequirementsNotMetException
     */
    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface
    {
        // Read Chrome binary path from settings
        $chromePath = $this->getGoogleChromeExecutablePath();

        if (empty($chromePath)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.executable_not_found'
            ]);
        }

        putenv("CHROME_PATH=" . $chromePath);
        return new Creator();
    }

    /**
     * @inheritDoc
     */
    public function checkRequirements(): void
    {
        $executablePath = $this->getGoogleChromeExecutablePath();

        if (is_null($executablePath) || !file_exists($executablePath)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.executable_not_found'
            ]);
        }

        if (!is_executable($executablePath)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.not_executable',
                [
                    'path' => $executablePath
                ]
            ]);
        }
    }

    private function getGoogleChromeExecutablePath(): ?string
    {
        $executablePath = null;

        try {
            $conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();

            if (!is_null($conf)) {
                $executablePath = $conf->get('chrome_headless', 'executable');
            } else {
                QUI\System\Log::addError("Cannot read / build config for quiqqer/htmltopdf.");
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return null;
        }

        if (empty($executablePath)) {
            $executablePath = `which google-chrome`;

            if (empty($executablePath)) {
                QUI\System\Log::addWarning(
                    "Google Chrome exectuable path not set in config. `which google-chrome` produced empty result."
                    . " Google Chrome seems to be not installed."
                );
                return null;
            }

            QUI\System\Log::addWarning(
                "Google Chrome executable path not set in config."
                . " Using `which google-chrome` (= $executablePath) instead."
            );
        }

        $executablePath = trim($executablePath);

        return empty($executablePath) ? null : $executablePath;
    }
}
