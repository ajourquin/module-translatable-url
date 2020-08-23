<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreSwitcherInterface;

class Switcher implements StoreSwitcherInterface
{
    private const XML_LOCALE_PATH = 'general/locale/code';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var TranslatableUrlResolver */
    private $translatableUrlResolver;

    /** @var Config */
    private $config;

    /**
     * Switcher constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param TranslatableUrlResolver $translatableUrlResolver
     * @param Config $config
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TranslatableUrlResolver $translatableUrlResolver,
        Config $config
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->translatableUrlResolver = $translatableUrlResolver;
        $this->config = $config;
    }

    /**
     * @param StoreInterface $fromStore
     * @param StoreInterface $targetStore
     * @param string $redirectUrl
     * @return string
     * @throws LocalizedException
     */
    public function switch(StoreInterface $fromStore, StoreInterface $targetStore, string $redirectUrl): string
    {
        if ($this->config->isEnabled() && $this->config->isEnabled((int) $targetStore->getId())) {
            $toLocale = $this->scopeConfig->getValue(
                self::XML_LOCALE_PATH,
                ScopeInterface::SCOPE_STORES,
                $targetStore->getCode()
            );
            $fromLocale = $this->scopeConfig->getValue(
                self::XML_LOCALE_PATH,
                ScopeInterface::SCOPE_STORES,
                $fromStore->getCode()
            );

            $redirectUrl = $this->translatableUrlResolver->resolveUrl($redirectUrl, $toLocale, $fromLocale);
        }

        return $redirectUrl;
    }
}
