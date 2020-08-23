<?php

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model;

interface TranslatableUrlResolverInterface
{
    /**
     * @param string $part
     * @param string|null $fromLocale
     * @return array
     */
    public function resolve(string $part, ?string $fromLocale = null): string;

    /**
     * @param string $part
     * @param string|null $fromLocale
     * @return array
     */
    public function resolveRequest(array $parts, ?string $fromLocale = null): array;

    /**
     * @param string $url
     * @param string|null $toLocale
     * @param string|null $fromLocale
     * @return string
     */
    public function resolveUrl(string $url, ?string $toLocale = null, ?string $fromLocale = null): string;
}
