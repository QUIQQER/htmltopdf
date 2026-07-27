<?php

namespace QUITests\HtmlToPdf;

use Monolog\Handler\NullHandler;
use Monolog\Logger as MonologLogger;
use QUI\Log\Logger;

trait LogIsolationTrait
{
    private ?MonologLogger $previousLogger = null;

    protected function isolateQuiqqerLog(): void
    {
        $this->previousLogger = Logger::getLogger();

        $logger = new MonologLogger('htmltopdf-tests');
        $logger->pushHandler(new NullHandler());
        Logger::$Logger = $logger;
    }

    protected function restoreQuiqqerLog(): void
    {
        if ($this->previousLogger !== null) {
            Logger::$Logger = $this->previousLogger;
        }
    }
}
