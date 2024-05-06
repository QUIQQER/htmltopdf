<?php

namespace QUI\HtmlToPdf;

use DateTime;
use Exception;
use QUI;
use QUI\Utils\System\File;

class Cron
{
    /**
     * Clean old PDF and HTML files from var folder
     *
     * @param array $options - Cron options
     * @return void
     * @throws QUI\Exception
     * @throws Exception
     */
    public static function cleanFiles(array $options): void
    {
        if (empty($options['days'])) {
            QUI\System\Log::addWarning(
                self::class . ' :: cleanFiles() -> Cannot execute cron because no number of days have been specified.'
            );

            return;
        }

        $varDir = QUI::getPackage('quiqqer/htmltopdf')->getVarDir();
        $files = File::readDir($varDir, true);
        $days = (int)$options['days'];
        $DaysAgo = new DateTime('-' . $days . ' days');
        $daysAgoTimestamp = $DaysAgo->getTimestamp();

        foreach ($files as $filename) {
            $file = $varDir . $filename;

            if (filemtime($file) <= $daysAgoTimestamp) {
                unlink($file);
            }
        }
    }
}
