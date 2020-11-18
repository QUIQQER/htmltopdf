<?php

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);

require_once dirname(__FILE__, 4).'/header.php';

use QUI\HtmlToPdf\Document;

$User = QUI::getUserBySession();

if (!$User->canUseBackend()) {
    exit;
}

try {
    $Document = new Document([
        'marginTop' => 30, // dies ist variabel durch quiqqerInvoicePdfCreate
        'filename'  => 'test.pdf',
//        'marginBottom' => 80,  // dies ist variabel durch quiqqerInvoicePdfCreate,
//            'pageNumbersPrefix' => $Locale->get('quiqqer/htmltopdf', 'footer.page.prefix')
    ]);

    $tplDir = OPT_DIR.'quiqqer/htmltopdf/template/';
    $Engine = QUI::getTemplateManager()->getEngine();

    $Engine->assign([
        'headerImg' => OPT_DIR.'quiqqer/htmltopdf/bin/images/Logo.jpg',
        'bodyImg'   => OPT_DIR.'quiqqer/htmltopdf/bin/images/Readme.jpg'
    ]);

    $Document->setHeaderHTML(
        $Engine->fetch($tplDir.'test.header.html')
    );

    $Document->setContentHTML(
        $Engine->fetch($tplDir.'test.body.html')
    );

    $Document->setFooterHTML(
        $Engine->fetch($tplDir.'test.footer.html')
    );

    $Document->download();
} catch (\Exception $Exception) {
    QUI\System\Log::writeException($Exception);
}
