<?php

use QUI\HtmlToPdf\Provider\Pdf\ProviderRepository;

QUI::$Ajax?->registerFunction(
    'package_quiqqer_htmltopdf_ajax_backend_testProviderPdf',
    /**
     * Tests the current HTML to PDF provider.
     */
    function () {
        $error = false;

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
