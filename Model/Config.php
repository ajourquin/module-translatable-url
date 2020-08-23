<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const IS_MODULE_ENABLE_PATH = 'web/url/url_translation';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null $scopeCode
     * @return bool
     */
    public function isEnabled(?int $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::IS_MODULE_ENABLE_PATH,
            ScopeInterface::SCOPE_STORES,
            $scopeCode
        );
    }
}
