<?php

use QUI\HtmlToPdf\Provider\Image\ProviderRepository;

QUI::$Ajax?->registerFunction(
    'package_quiqqer_htmltopdf_ajax_backend_testProviderImage',
    /**
     * Tests the current PDF to image provider.
     *
     * @return null|string - NULL if everything is ok; error message otherwise
     */
    function () {
        $error = null;

        try {
            $repository = new ProviderRepository();
            $provider = $repository->getCurrentProvider();
            $provider->checkRequirements();
        } catch (Throwable $exception) {
            QUI\System\Log::writeDebugException($exception);
            $error = $exception->getMessage();
        }

        return $error;
    },
    [],
    'Permission::checkAdminUser'
);
