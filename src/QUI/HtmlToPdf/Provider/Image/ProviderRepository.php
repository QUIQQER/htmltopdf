<?php

namespace QUI\HtmlToPdf\Provider\Image;

use QUI;

use function class_exists;
use function is_a;

class ProviderRepository implements ProviderRepositoryInterface
{
    private QUI\Package\Manager $quiPackageManager;

    public function __construct(
        ?QUI\Package\Manager $quiPackageManager = null
    ) {
        if (is_null($quiPackageManager)) {
            $quiPackageManager = QUI::getPackageManager();
        }

        $this->quiPackageManager = $quiPackageManager;
    }

    /**
     * @inheritDoc
     * @throws QUI\Exception
     */
    public function getCurrentProvider(): PdfToImageConverterProviderInterface
    {
        $config = $this->quiPackageManager->getInstalledPackage('quiqqer/htmltopdf')->getConfig();

        if (is_null($config)) {
            throw new QUI\Exception("Cannot read / build config for quiqqer/htmltopdf.");
        }

        $providerClass = $config->get('settings', 'pdf_to_image_converter_provider');

        if (empty($providerClass)) {
            throw new QUI\Exception("No quiqqer/htmltopdf PDF image converter provider set in config.");
        }

        if (!class_exists($providerClass)) {
            throw new QUI\Exception(
                "The quiqqer/htmltopdf PDF image converter provider class " . $providerClass . " does not exist."
            );
        }

        if (!is_a($providerClass, PdfToImageConverterProviderInterface::class, true)) {
            throw new QUI\Exception(
                "The quiqqer/htmltopdf PDF image converter provider class " . $providerClass . " does not implement"
                . " " . PdfToImageConverterProviderInterface::class
            );
        }

        return new $providerClass();
    }

    /**
     * @inheritDoc
     */
    public function getAllProviders(): array
    {
        $packages = $this->quiPackageManager->getInstalled();
        $providers = [];

        foreach ($packages as $installedPackage) {
            try {
                $Package = QUI::getPackage($installedPackage['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $packageProvider = $Package->getProvider();

                if (empty($packageProvider['pdfToImageConverter'])) {
                    continue;
                }

                foreach ($packageProvider['pdfToImageConverter'] as $class) {
                    if (!\class_exists($class)) {
                        continue;
                    }

                    if (!\is_a($class, PdfToImageConverterProviderInterface::class, true)) {
                        continue;
                    }

                    $providers[] = new $class();
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $providers;
    }
}
