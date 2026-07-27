<?php

namespace QUI\HtmlToPdf\Provider\Pdf\ChromeHeadless;

use QUI;
use QUI\HtmlToPdf\Provider\Pdf\Exception\HtmlToPdfRequirementsNotMetException;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorInterface;
use QUI\HtmlToPdf\Provider\Pdf\HtmlToPdfCreatorProviderInterface;
use QUI\Locale;
use QUI\Utils\System\File;
use Throwable;

use function file_exists;
use function filter_var;
use function is_executable;
use function is_null;
use function is_writable;
use function trim;

use const PHP_OS_FAMILY;

class Provider implements HtmlToPdfCreatorProviderInterface
{
    private const MACOS_DEFAULT_EXECUTABLE = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

    public function getTitle(?Locale $locale = null): string
    {
        if (is_null($locale)) {
            $locale = QUI::getLocale();
        }

        return $locale->get('quiqqer/htmltopdf', 'provider.ChromeHeadless.title');
    }

    /**
     * @throws HtmlToPdfRequirementsNotMetException
     */
    public function getHtmlToPdfCreator(): HtmlToPdfCreatorInterface
    {
        // Read Chrome executable path from settings
        $chromePath = $this->getGoogleChromeExecutablePath();

        if (empty($chromePath)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.executable_not_found',
                [
                    'path' => 'google-chrome'
                ]
            ]);
        }

        $this->checkExecutableAccess($chromePath);

        return new Creator(
            $chromePath,
            $this->getConfigFlag('no_sandbox', true),
            $this->getConfigFlag('ignore_certificate_errors')
        );
    }

    /**
     * @inheritDoc
     */
    public function checkRequirements(): void
    {
        $executablePath = $this->getGoogleChromeExecutablePath();

        if (is_null($executablePath)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.executable_not_found',
                [
                    'path' => 'google-chrome'
                ]
            ]);
        }

        $this->checkExecutableAccess($executablePath);

        $creator = new Creator(
            $executablePath,
            $this->getConfigFlag('no_sandbox', true),
            $this->getConfigFlag('ignore_certificate_errors')
        );

        try {
            $creator->checkBrowserStartup();
        } catch (Throwable $exception) {
            QUI\System\Log::writeDebugException($exception);

            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.start_failed',
                [
                    'path' => $executablePath,
                    'exitCode' => $exception->getCode() ?: 'unknown'
                ]
            ]);
        }
    }

    /**
     * @throws HtmlToPdfRequirementsNotMetException
     */
    private function checkExecutableAccess(string $executablePath): void
    {
        if (!file_exists($executablePath)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.executable_not_found',
                [
                    'path' => $executablePath
                ]
            ]);
        }

        if (!is_executable($executablePath)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.not_executable',
                [
                    'path' => $executablePath
                ]
            ]);
        }

        $chromeHome = QUI::getPackage('quiqqer/htmltopdf')->getVarDir() . 'chrome-home/';

        if (!File::mkdir($chromeHome) || !is_writable($chromeHome)) {
            throw new HtmlToPdfRequirementsNotMetException([
                'quiqqer/htmltopdf',
                'exception.Provider.GoogleChrome.checkRequirements.home_not_writable',
                [
                    'path' => $chromeHome
                ]
            ]);
        }
    }

    private function getGoogleChromeExecutablePath(): ?string
    {
        $executablePath = null;

        try {
            $conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();

            if (!is_null($conf)) {
                $executablePath = $conf->get('chrome_headless', 'executable');
            } else {
                QUI\System\Log::addError("Cannot read / build config for quiqqer/htmltopdf.");
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return null;
        }

        if (empty($executablePath)) {
            $executablePath = trim(shell_exec('which google-chrome') ?: '');

            if (empty($executablePath)) {
                $executablePath = $this->getMacOsChromeExecutablePath();

                if (is_null($executablePath)) {
                    QUI\System\Log::addWarning(
                        "Google Chrome executable path not set in config and no system executable was found."
                    );
                    return null;
                }

                QUI\System\Log::addWarning(
                    "Google Chrome executable path not set in config."
                    . " Using the macOS default (= $executablePath) instead."
                );
            } else {
                QUI\System\Log::addWarning(
                    "Google Chrome executable path not set in config."
                    . " Using `which google-chrome` (= $executablePath) instead."
                );
            }
        }

        $executablePath = trim($executablePath);

        return empty($executablePath) ? null : $executablePath;
    }

    private function getMacOsChromeExecutablePath(
        string $osFamily = PHP_OS_FAMILY,
        string $defaultExecutable = self::MACOS_DEFAULT_EXECUTABLE
    ): ?string {
        if ($osFamily !== 'Darwin' || !file_exists($defaultExecutable)) {
            return null;
        }

        return $defaultExecutable;
    }

    private function getConfigFlag(string $name, bool $default = false): bool
    {
        try {
            $conf = QUI::getPackage('quiqqer/htmltopdf')->getConfig();

            if (is_null($conf)) {
                QUI\System\Log::addError("Cannot read / build config for quiqqer/htmltopdf.");
                return $default;
            }

            $value = $conf->get('chrome_headless', $name);

            if ($value === false || $value === null || $value === '') {
                return $default;
            }

            return filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return $default;
        }
    }
}
