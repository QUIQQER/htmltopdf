<?php

/**
 * Test a binary used in quiqqer/htmltopdf
 *
 * @param string $type - "pdf" or "image"
 * @return string|false - Error message or false if everything is alright
 */

use QUI\HtmlToPdf\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_htmltopdf_ajax_testBinary',
    function ($type) {
        $error = false;

        switch ($type) {
            case 'pdf':
                try {
                    Handler::checkPDFGeneratorBinary();
                } catch (Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                    $error = $Exception->getMessage();
                }
                break;

            case 'image':
                try {
                    Handler::checkConvertBinary();
                } catch (Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                    $error = $Exception->getMessage();
                }
                break;
        }

        return $error;
    },
    ['type'],
    'Permission::checkAdminUser'
);
