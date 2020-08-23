<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model;

use Magento\Framework\App\Area;
use Magento\Framework\Url\RouteParamsPreprocessorInterface;
use Magento\Framework\Url\RouteParamsResolverInterface;

class RouteParamsPreprocessor implements RouteParamsPreprocessorInterface
{
    /** @var RouteParamsResolverInterface */
    private $routeParamsResolver;

    /** @var Config */
    private $config;

    /**
     * RouteParamsPreprocessor constructor.
     * @param RouteParamsResolverInterface $routeParamsResolver
     * @param Config $config
     */
    public function __construct(
        RouteParamsResolverInterface $routeParamsResolver,
        Config $config
    ) {
        $this->routeParamsResolver = $routeParamsResolver;
        $this->config = $config;
    }

    /**
     * @param string $areaCode
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return array|null
     */
    public function execute($areaCode, $routePath, $routeParams): ?array
    {
        if ($areaCode === Area::AREA_FRONTEND && $this->config->isEnabled()) {
            if (\is_array($routeParams) && \array_key_exists('_notranslate', $routeParams)) {
                $this->routeParamsResolver->setData('notranslate', true);
            }
        }

        return $routeParams;
    }
}
