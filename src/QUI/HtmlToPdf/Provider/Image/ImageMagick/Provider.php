<?php

namespace QUI\HtmlToPdf\Provider\Image\ImageMagick;

use QUI;
use QUI\HtmlToPdf\Provider\Image\Exception\PdfToImageRequirementsNotMetException;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterInterface;
use QUI\HtmlToPdf\Provider\Image\PdfToImageConverterProviderInterface;
use QUI\Locale;

use function file_exists;
use function is_executable;
use function is_null;
use function trim;

class Provider implements PdfToImageConverterProviderInterface
{
    public function getTitle(?Locale $locale = null): string
    {
        if (is_null($locale)) {
            $locale = QUI::getLocale();
        }

        return $locale->get('quiqqer/htmltopdf', 'provider.ImageMagick.title');
    }

    /**
     * @throws PdfToImageRequirementsNotMetException
     */
    public function getPdfToImageConverter(): PdfToImageConverterInterface
    {
        $executablePath = $this->getConvertExecutablePath();

        if (empty($executablePath)) {
            throw new PdfToImageRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.ImageMagick.checkRequirements.convert_executable_not_found'
            ]);
        }

        return new Converter($executablePath);
    }

    /**
     * @throws PdfToImageRequirementsNotMetException
     */
    public function checkRequirements(): void
    {
        $executablePath = $this->getConvertExecutablePath();

        if (is_null($executablePath) || !file_exists($executablePath)) {
            throw new PdfToImageRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.ImageMagick.checkRequirements.convert_executable_not_found'
            ]);
        }

        if (!is_executable($executablePath)) {
            throw new PdfToImageRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.ImageMagick.checkRequirements.convert_executable_not_executable',
                [
                    'path' => $executablePath
                ]
            ]);
        }
    }

    /**
     * Get path to the ImageMagick `convert` command
     *
     * @return string|null
     */
    private function getConvertExecutablePath(): ?string
    {
        $executablePath = null;

        try {
            $conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();

            if (!is_null($conf)) {
                $executablePath = $conf->get('image_magick', 'convert_executable');
            } else {
                QUI\System\Log::addError("Cannot read / build config for quiqqer/htmltopdf.");
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return null;
        }

        if (empty($executablePath)) {
            $executablePath = `which convert`;

            if (empty($executablePath)) {
                QUI\System\Log::addWarning(
                    "ImageMagick convert executable path not set in config. `which convert` produced empty result."
                    . " ImageMagick / convert seems to be not installed."
                );
                return null;
            }

            QUI\System\Log::addWarning(
                "ImageMagick convert executable path not set in config. Using `which convert` (= $executablePath) instead."
            );
        }

        $executablePath = trim($executablePath);

        return empty($executablePath) ? null : $executablePath;
    }
}
