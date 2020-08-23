<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Preference\Framework\App\Router;

use Ajourquin\TranslatableUrl\Model\Config;
use Ajourquin\TranslatableUrl\Model\TranslatableUrlResolverInterface;
use Ajourquin\TranslatableUrl\Model\UrlTranslate;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\Router\Base as BaseRouter;
use Magento\Framework\App\Router\PathConfigInterface;
use Magento\Framework\Code\NameBuilder;
use Magento\Framework\UrlInterface;

class Base extends BaseRouter
{
    /** @var TranslatableUrlResolverInterface */
    private $translatableUrlResolver;

    /** @var UrlTranslate */
    private $urlTranslate;

    /** @var Config */
    private $config;

    /**
     * Base constructor.
     * @param ActionList $actionList
     * @param ActionFactory $actionFactory
     * @param DefaultPathInterface $defaultPath
     * @param ResponseFactory $responseFactory
     * @param ConfigInterface $routeConfig
     * @param UrlInterface $url
     * @param NameBuilder $nameBuilder
     * @param PathConfigInterface $pathConfig
     * @param TranslatableUrlResolverInterface $translatableUrlResolver
     * @param UrlTranslate $urlTranslate
     * @param Config $config
     */
    public function __construct(
        ActionList $actionList,
        ActionFactory $actionFactory,
        DefaultPathInterface $defaultPath,
        ResponseFactory $responseFactory,
        ConfigInterface $routeConfig,
        UrlInterface $url,
        NameBuilder $nameBuilder,
        PathConfigInterface $pathConfig,
        TranslatableUrlResolverInterface $translatableUrlResolver,
        UrlTranslate $urlTranslate,
        Config $config
    ) {
        parent::__construct(
            $actionList,
            $actionFactory,
            $defaultPath,
            $responseFactory,
            $routeConfig,
            $url,
            $nameBuilder,
            $pathConfig
        );
        $this->translatableUrlResolver = $translatableUrlResolver;
        $this->urlTranslate = $urlTranslate;
        $this->config = $config;
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    protected function parseRequest(RequestInterface $request): array
    {
        $output = parent::parseRequest($request);

        if ($this->config->isEnabled()) {
            $output = $this->translatableUrlResolver->resolveRequest($output, $this->urlTranslate->getLocale());
            $this->urlTranslate->setOriginValues($output);
        }

        return $output;
    }
}
