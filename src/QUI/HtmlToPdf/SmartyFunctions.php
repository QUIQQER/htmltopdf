<?php

namespace QUI\HtmlToPdf;

use QUI;
use Smarty_Internal_Template;

class SmartyFunctions
{
    public static function imageBase64(array $params, Smarty_Internal_Template $smarty): string
    {
        if (empty($params['image'])) {
            QUI\System\Log::addWarning(
                "\$params does not container 'image'"
            );
            return '';
        }

        $image = $params['image'];

        if (!($image instanceof QUI\Projects\Media\Image)) {
            QUI\System\Log::addWarning(
                "\$params does not contain an instance of QUI\Projects\Media\Image"
            );
            return '';
        }

        $mimeType = $image->getAttribute('mime_type');

        if (empty($mimeType)) {
            QUI\System\Log::addWarning(
                "\$params does not contain mime type information"
            );
            return '';
        }

        $fullImgPath = $image->getFullPath();

        try {
            $width = $image->getWidth();
            $height = $image->getHeight();

            if (!empty($params['width']) && $params['width'] < $width) {
                $width = $params['width'];
            }

            if (!empty($params['height']) && $params['height'] < $height) {
                $height = $params['height'];
            }

            $fullImgPath = $image->createResizeCache($width, $height);
//            $resizeData = $image->getResizeSize($width, $height);
        } catch (\Exception $exception) {
            QUI\System\Log::writeException($exception);
            return '';
        }


        // Convert SVG to PNG
        if (!empty($params['svgtopng']) && str_contains($fullImgPath, '.svg')) {
            $pngImage = $fullImgPath . '.png';
            $src = '';

            if (file_exists($pngImage)) {
                $src = $pngImage;
            } elseif (class_exists('\Imagick')) {
                $svg = file_get_contents($fullImgPath);

                try {
                    $im = new Imagick();
                    $im->readImageBlob($svg);
                    $im->setImageBackgroundColor(new ImagickPixel('transparent'));
                    $im->setImageFormat("png24");
                    $im->writeImage($pngImage);
                    $im->clear();
                    $im->destroy();

                    $src = $pngImage;
                    $mimeType = 'image/png';
                } catch (Exception $exception) {
                    QUI\System\Log::writeException($exception);
                    return '';
                }
            }

            $src = str_replace(CMS_DIR, URL_DIR, $src);
        } else {
            $src = file_get_contents($fullImgPath);
        }

        return "data:" . $mimeType . ";base64," . base64_encode($src);
    }
}
