<?php

namespace QUI\HtmlToPdf\Provider\Pdf;

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
    public function getCurrentProvider(): HtmlToPdfCreatorProviderInterface
    {
        $config = $this->quiPackageManager->getInstalledPackage('quiqqer/htmltopdf')->getConfig();

        if (is_null($config)) {
            throw new QUI\Exception("Cannot read / build config for quiqqer/htmltopdf.");
        }

        $providerClass = $config->get('settings', 'html_to_pdf_creator_provider');

        if (empty($providerClass)) {
            throw new QUI\Exception("No quiqqer/htmltopdf PDF Creator provider set in config.");
        }

        if (!class_exists($providerClass)) {
            throw new QUI\Exception(
                "The quiqqer/htmltopdf PDF Creator provider class " . $providerClass . " does not exist."
            );
        }

        if (!is_a($providerClass, HtmlToPdfCreatorProviderInterface::class, true)) {
            throw new QUI\Exception(
                "The quiqqer/htmltopdf PDF Creator provider class " . $providerClass . " does not implement the HtmlToPdfCreatorProviderInterface."
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

                if (empty($packageProvider['htmlToPdfCreator'])) {
                    continue;
                }

                foreach ($packageProvider['htmlToPdfCreator'] as $class) {
                    if (!\class_exists($class)) {
                        continue;
                    }

                    if (!\is_a($class, HtmlToPdfCreatorProviderInterface::class, true)) {
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
