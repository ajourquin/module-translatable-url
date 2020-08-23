<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Route\Config as RouteConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Zend\Uri\Http as UriHttp;
use Zend\Uri\Uri;

class TranslatableUrlResolver implements TranslatableUrlResolverInterface
{
    /** @var UrlTranslate */
    private $urlTranslate;

    /** @var array */
    private $translations;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var UriHttp */
    private $uriHttp;

    /** @var RouteConfig */
    private $routeConfig;

    /** @var bool */
    private $isStoreCodeUsedInUrl;

    /**
     * TranslatableUrlResolver constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlTranslate $urlTranslate
     * @param UriHttp $uriHttp
     * @param RouteConfig $routeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlTranslate $urlTranslate,
        UriHttp $uriHttp,
        RouteConfig $routeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlTranslate = $urlTranslate;
        $this->uriHttp = $uriHttp;
        $this->routeConfig = $routeConfig;
    }

    /**
     * @param string $part
     * @param string|null $fromLocale
     * @return string
     * @throws LocalizedException
     */
    public function resolve(string $part, ?string $fromLocale = null): string
    {
        $this->getTranslations($fromLocale);

        return $this->translate($part);
    }

    /**
     * @param array $parts
     * @param string|null $fromLocale
     * @return array
     * @throws LocalizedException
     */
    public function resolveRequest(array $parts, ?string $fromLocale = null): array
    {
        $this->getTranslations($fromLocale);
        $parts['moduleFrontName'] = $this->translate($parts['moduleFrontName']);
        $parts['actionPath'] = $this->translate($parts['actionPath']);
        $parts['actionName'] = $this->translate($parts['actionName']);

        return $parts;
    }

    /**
     * @param string $url
     * @param string|null $toLocale
     * @param string|null $fromLocale
     * @return string
     * @throws LocalizedException
     */
    public function resolveUrl(string $url, ?string $toLocale = null, ?string $fromLocale = null): string
    {
        $fromTranslations = $this->getTranslations($fromLocale);
        $toTranslations = $this->getTranslations($toLocale, ['no_flip' => 1]);
        $storeCode = '';
        $uri = $this->uriHttp->parse($url);
        $parts = \explode('/', \trim($uri->getPath(), ' /'));

        if ($this->isStoreCodeUsedInUrl()) {
            $storeCode = $parts[0];
            unset($parts[0]);
            $parts = \array_values($parts);
        }

        // Revert applied translated url
        if ($fromLocale !== $toLocale) {
            foreach ($parts as &$part) {
                if (\array_key_exists($part, $fromTranslations)) {
                    $part = $fromTranslations[$part];
                }
            }
        }

        // Translate to destination locale
        if ($this->canTranslateUrl($url, $parts)) {
            foreach ($parts as &$part) {
                if (\array_key_exists($part, $toTranslations)) {
                    $part = $toTranslations[$part];
                }
            }

            $url = $this->buildUrl($uri, $storeCode, $parts);
        }

        return $url;
    }

    /**
     * @param string|null $input
     * @return string|null
     */
    private function translate(?string $input): ?string
    {
        if ($input !== null && \array_key_exists($input, $this->translations)) {
            $input = $this->translations[$input];
        }

        return $input;
    }

    /**
     * @param string|null $fromLocale
     * @param array $params
     * @return array
     * @throws LocalizedException
     */
    private function getTranslations(?string $fromLocale = null, array $params = []): array
    {
        $this->translations = $this->urlTranslate->loadUrlData($fromLocale)->getData();

        if (!\array_key_exists('no_flip', $params)) {
            $this->translations = \array_flip($this->translations);
        }

        return $this->translations;
    }

    /**
     * @return bool
     */
    private function isStoreCodeUsedInUrl(): bool
    {
        if ($this->isStoreCodeUsedInUrl === null) {
            $this->isStoreCodeUsedInUrl = $this->scopeConfig->isSetFlag(
                Store::XML_PATH_STORE_IN_URL,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }

        return $this->isStoreCodeUsedInUrl;
    }

    /**
     * @param Uri $uri
     * @param string $storeCode
     * @param array $parts
     * @return string
     */
    private function buildUrl(Uri $uri, string $storeCode, array $parts): string
    {
        $url = $uri->getScheme() . '://' . $uri->getHost() . '/';

        if ($storeCode !== '') {
            $url .= $storeCode . '/';
        }

        $url .= \implode('/', $parts);

        if ($uri->getQuery() !== null) {
            $url .= '?' . $uri->getQuery();
        }

        return $url;
    }

    /**
     * @param string $url
     * @param array $parts
     * @return bool
     */
    private function canTranslateUrl(string $url, array $parts): bool
    {
        if (\count($parts) === 0) {
            return false;
        }

        if (\preg_match('/(section\/load|\/rest\/|\/soap\/)/', $url)) {
            return false;
        }

        if ($this->routeConfig->getRouteByFrontName($parts[0], Area::AREA_FRONTEND) === false) {
            return false;
        }

        return true;
    }
}
