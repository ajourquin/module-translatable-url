<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Plugin\Framework;

use Ajourquin\TranslatableUrl\Model\Config;
use Magento\Framework\Url as MagentoUrl;

class Url
{
    /** @var Config */
    private $config;

    /**
     * Url constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param MagentoUrl $subject
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return array
     */
    public function beforeGetRouteUrl(MagentoUrl $subject, ?string $routePath, ?array $routeParams): array
    {
        if ($this->config->isEnabled() && $routeParams !== null && \array_key_exists('_notranslate', $routeParams)) {
            unset($routeParams['_notranslate']);
        }

        return [$routePath, $routeParams];
    }
}
