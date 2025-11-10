<?php

namespace QUI\HtmlToPdf\Provider\Pdf;

interface ProviderRepositoryInterface
{
    /**
     * Get the HtmlToPdfCreator provider that is currently set up as default for this system.
     */
    public function getCurrentProvider(): HtmlToPdfCreatorProviderInterface;

    /**
     * @return HtmlToPdfCreatorProviderInterface[]
     */
    public function getAllProviders(): array;
}
