<?php

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);
define('QUIQQER_BACKEND', true);

require_once dirname(__FILE__, 5) . '/header.php';

use QUI\HtmlToPdf\Document;
use QUI\HtmlToPdf\Handler;

/**
 * This script runs in a hidden iframe, so errors are passed to the message handler of the parent window
 */
$errorOutput = function (string $message, int $statusCode): never {
    http_response_code($statusCode);
    header('Content-Type: text/html; charset=utf-8');

    $message = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    echo '
    <script>
    var parent = window.parent;

    if (typeof parent.require !== "undefined") {
        parent.require(["qui/QUI"], function(QUI) {
            QUI.getMessageHandler().then(function(MH) {
                MH.addError(' . $message . ');
            });
        });
    }
    </script>';
    exit;
};

$user = QUI::getUserBySession();

if (!$user->canUseBackend()) {
    $errorOutput(QUI::getLocale()->get('quiqqer/core', 'exception.no.permission'), 403);
}

$type = $_GET['type'] ?? 'pdf';

try {
    $document = new Document([
        'marginTop' => 40, // dies ist variabel durch quiqqerInvoicePdfCreate
//        'marginBottom'  => 10, // dies ist variabel durch quiqqerInvoicePdfCreate
        'filename' => 'test.pdf',
    ]);

    $document->setAttribute('marginBottom', 25);
    $document->setAttribute('marginLeft', 10);
    $document->setAttribute('marginRight', 10);

    $tplDir = OPT_DIR . 'quiqqer/htmltopdf/template/';
    $Engine = QUI::getTemplateManager()->getEngine();

    $Engine->assign([
        'headerImg' => OPT_DIR . 'quiqqer/htmltopdf/bin/images/Logo.jpg',
        'bodyImg' => OPT_DIR . 'quiqqer/htmltopdf/bin/images/Readme.jpg'
    ]);

    $document->setHeaderHTML(
        $Engine->fetch($tplDir . 'test.header.html')
    );

    $document->setContentHTML(
        $Engine->fetch($tplDir . 'test.body.html')
    );

    $document->setFooterHTML(
        $Engine->fetch($tplDir . 'test.footer.html')
    );

    $handler = new Handler();
    $pdfCreator = $handler->getPdfCreator();

    if ($type === 'image') {
        $imageFiles = $pdfCreator->createPdfAndConvertToImage($document);
        QUI\Utils\System\File::send($imageFiles[0], 0, 'test.jpg');
        foreach ($imageFiles as $imageFile) {
            unlink($imageFile);
        }
        exit;
    }

    $pdfCreator->createAndDownloadPdf($document);
} catch (QUI\Exception $Exception) {
    QUI\System\Log::writeException($Exception);
    $errorOutput($Exception->getMessage(), 500);
} catch (\Throwable $Exception) {
    QUI\System\Log::writeException($Exception);
    $errorOutput(QUI::getLocale()->get('quiqqer/htmltopdf', 'exception.document.pdf.conversion.failed'), 500);
}

exit;
