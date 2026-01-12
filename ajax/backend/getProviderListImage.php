<?php

use QUI\HtmlToPdf\Provider\Image\ProviderRepository;

QUI::$Ajax?->registerFunction(
    'package_quiqqer_htmltopdf_ajax_backend_getProviderListImage',
    /**
     * Get list of all available PdfToImageConverterProviders.
     * @return array<string,string>
     */
    function () {
        $repository = new ProviderRepository();
        $providers = [];

        foreach ($repository->getAllProviders() as $provider) {
            $providers[] = [
                'title' => $provider->getTitle(),
                'class' => get_class($provider)
            ];
        }

        return $providers;
    },
    [],
    'Permission::checkAdminUser'
);
