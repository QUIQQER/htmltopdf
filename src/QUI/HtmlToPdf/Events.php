<?php
/**
 * This file contains \QUI\HtmlToPdf\Events
 */

namespace QUI\HtmlToPdf;

use QUI;

/**
 * Document that receives HTML and outputs PDF
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class Events
{
    /**
     * Event: onPackageSetup
     *
     * @param QUI\Package\Package $Package
     * @throws QUI\Exception
     */
    public static function onPackageSetup($Package)
    {
        // check if wkhtmltopdf is executable
        $wkhtmltopdfExecutable = dirname(dirname(dirname(dirname(__FILE__)))) . '/lib/wkhtmltopdf/bin/wkhtmltopdf';

        if (!file_exists($wkhtmltopdfExecutable)) {
            throw new QUI\Exception(array(
                'pcsg/htmltopdf',
                'exception.events.onpackagesetup.missing.wkhtmltopdf'
            ));
        }

        if (!is_executable($wkhtmltopdfExecutable)) {
            exec('chmod +x ' . $wkhtmltopdfExecutable, $output, $exitStatus);

            if (!is_executable($wkhtmltopdfExecutable)) {
                throw new QUI\Exception(array(
                    'pcsg/htmltopdf',
                    'exception.events.onpackagesetup.wkhtmltopdf.not.executable',
                    array(
                        'path' => $wkhtmltopdfExecutable
                    )
                ));
            }
        }
    }
}
