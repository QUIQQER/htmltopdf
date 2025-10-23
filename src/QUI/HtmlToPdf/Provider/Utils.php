<?php

namespace QUI\HtmlToPdf\Provider;

use function preg_match;
use function preg_replace;

class Utils
{
    /**
     * Extract element content from string containing HTML.
     *
     * @param string $html
     * @param string $element
     * @return string - Everything inside <$element>
     */
    public static function extractContentFromHtmlElement(string $html, string $element = 'body'): string
    {
        // Extract content between <body> tags if present
        if (preg_match('/<' . $element . '[^>]*>(.*?)<\/' . $element . '>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }


    /**
     * Remove element (including everything inside) from  HTML string.
     *
     * @param string $html
     * @param string $element
     * @return string
     */
    public static function removeElementFromHtml(string $html, string $element): string
    {
        return preg_replace('/<' . $element . '[^>]*>.*?<\/' . $element . '>/', '', $html);
    }

    /**
     * Put a given string inside and at the end of a given HTML element.
     *
     * @param string $html
     * @param string $element - Element name without <> (e.g. "body")
     * @param string $string - HTML to be wrappted by $element
     * @return string
     */
    public static function appendStringInHtmlElement(string $html, string $element, string $string): string
    {
        $matches = [];
        preg_match('/<(' . $element . '[^>]*)>(.*?)<\/' . $element . '>/', $html, $matches);
        if (count($matches) > 0) {
            return str_replace(
                $matches[0],
                '<' . $element . ' ' . $matches[1] . '>' . $string . '</' . $element . '>',
                $html
            );
        } else {
            return $html;
        }
    }

    /**
     * Put a given string inside and at the beginnging of a given HTML element.
     *
     * @param string $html
     * @param string $element - Element name without <> (e.g. "body")
     * @param string $string - HTML to be wrappted by $element
     * @return string
     */
    public static function prependStringInHtmlElement(string $html, string $element, string $string): string
    {
        return preg_replace(
            '/<(' . $element . '[^>]*)>(.*?)<\/' . $element . '>/',
            '<${1}>${2}' . $string . '</' . $element . '>',
            $html
        );
    }
}
