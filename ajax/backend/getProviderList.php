<?php

use QUI\HtmlToPdf\Provider\ProviderRepository;

QUI::$Ajax?->registerFunction(
    'package_quiqqer_htmltopdf_ajax_backend_getProviderList',
    /**
     * Get list of all available HtmlToPdfCreatorProviders.
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
