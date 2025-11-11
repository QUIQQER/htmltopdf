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

    public function getPdfToImageConverter(): PdfToImageConverterInterface
    {
        return new Converter($this->getConvertBinaryPath());
    }

    /**
     * @throws PdfToImageRequirementsNotMetException
     */
    public function checkRequirements(): void
    {
        $binaryPath = $this->getConvertBinaryPath();

        if (is_null($binaryPath) || !file_exists($binaryPath)) {
            throw new PdfToImageRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.ImageMagick.checkRequirements.convert_binary_not_found'
            ]);
        }

        if (!is_executable($binaryPath)) {
            throw new PdfToImageRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.ImageMagick.checkRequirements.convert_binary_not_executable',
                [
                    'path' => $binaryPath
                ]
            ]);
        }
    }

    /**
     * Get path to the ImageMagick `convert` command
     *
     * @return string|null
     */
    private function getConvertBinaryPath(): ?string
    {
        $binaryPath = null;

        try {
            $conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();

            if (!is_null($conf)) {
                $binaryPath = $conf->get('image_magick', 'convert_binary');
            } else {
                QUI\System\Log::addError("Cannot read / build config for quiqqer/htmltopdf.");
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return null;
        }

        if (empty($binaryPath)) {
            $binaryPath = `which convert`;

            if (empty($binaryPath)) {
                QUI\System\Log::addWarning(
                    "ImageMagick convert binary path not set in config. `which convert` produced empty result."
                    ." ImageMagick / convert seems to be not installed."
                );
                return null;
            }

            QUI\System\Log::addWarning(
                "ImageMagick convert binary path not set in config. Using `which convert` (= $binaryPath) instead."
            );
        }

        $binaryPath = trim($binaryPath);

        return empty($binaryPath) ? null : $binaryPath;
    }
}
