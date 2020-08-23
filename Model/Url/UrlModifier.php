<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model\Url;

use Ajourquin\TranslatableUrl\Model\Config;
use Ajourquin\TranslatableUrl\Model\TranslatableUrlResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Url\ModifierInterface;
use Magento\Framework\Url\RouteParamsResolverInterface;

class UrlModifier implements ModifierInterface
{
    /** @var Config */
    private $config;

    /** @var TranslatableUrlResolver */
    private $translatableUrlResolver;

    /** @var RouteParamsResolverInterface */
    private $routeParamsResolver;

    /**
     * UrlModifier constructor.
     * @param Config $config
     * @param TranslatableUrlResolver $translatableUrlResolver
     * @param RouteParamsResolverInterface $routeParamsResolver
     */
    public function __construct(
        Config $config,
        TranslatableUrlResolver $translatableUrlResolver,
        RouteParamsResolverInterface $routeParamsResolver
    ) {
        $this->config = $config;
        $this->translatableUrlResolver = $translatableUrlResolver;
        $this->routeParamsResolver = $routeParamsResolver;
    }

    /**
     * @param string $url
     * @param string $mode
     * @return string
     * @throws LocalizedException
     */
    public function execute($url, $mode = ModifierInterface::MODE_ENTIRE): string
    {
        if ($this->config->isEnabled() && !$this->routeParamsResolver->hasData('notranslate')) {
            $url = $this->translatableUrlResolver->resolveUrl($url);
        } else {
            $this->routeParamsResolver->unsetData('notranslate');
        }

        return $url;
    }
}
