<?php

namespace QUI\HtmlToPdf\Provider;

use QUI;
use Throwable;

use function class_exists;
use function is_a;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function __construct(
        private ?QUI\Package\Manager $quiPackageManager = null
    ) {
        if (is_null($this->quiPackageManager)) {
            $this->quiPackageManager = QUI::getPackageManager();
        }
    }

    /**
     * @return HtmlToPdfCreatorProviderInterface
     * @throws QUI\Exception
     */
    public function getCurrentProvider(): HtmlToPdfCreatorProviderInterface
    {
        $config = $this->quiPackageManager->getInstalledPackage('quiqqer/htmltopdf')->getConfig();
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
     * @return HtmlToPdfCreatorProviderInterface[]
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

                    try {
                        $providers[] = new $class();
                    } catch (Throwable $e) {
                        QUI\System\Log::writeException($e);
                    }
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $providers;
    }
}
