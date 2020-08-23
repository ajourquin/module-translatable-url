<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model;

use Magento\Framework\App\Language\Dictionary;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Module;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\Translate;
use Magento\Framework\Translate\ResourceInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\FileSystem as ViewFileSystem;

class UrlTranslate extends Translate
{
    /** @var UrlDictionary */
    private $packUrlDictionary;

    /** @var array */
    private $dataUrl = [];

    /** @var string */
    private $originModuleFrontName;

    /** @var string */
    private $originActionPath;

    /** @var string */
    private $originActionName;

    /**
     * UrlTranslate constructor.
     * @param DesignInterface $viewDesign
     * @param FrontendInterface $cache
     * @param ViewFileSystem $viewFileSystem
     * @param ModuleList $moduleList
     * @param Reader $modulesReader
     * @param ScopeResolverInterface $scopeResolver
     * @param ResourceInterface $translate
     * @param ResolverInterface $locale
     * @param State $appState
     * @param Filesystem $filesystem
     * @param RequestInterface $request
     * @param Csv $csvParser
     * @param Dictionary $packDictionary
     * @param UrlDictionary $packUrlDictionary
     */
    public function __construct(
        DesignInterface $viewDesign,
        FrontendInterface $cache,
        ViewFileSystem $viewFileSystem,
        ModuleList $moduleList,
        Reader $modulesReader,
        ScopeResolverInterface $scopeResolver,
        ResourceInterface $translate,
        ResolverInterface $locale,
        State $appState,
        Filesystem $filesystem,
        RequestInterface $request,
        Csv $csvParser,
        Dictionary $packDictionary,
        UrlDictionary $packUrlDictionary
    ) {
        parent::__construct(
            $viewDesign,
            $cache,
            $viewFileSystem,
            $moduleList,
            $modulesReader,
            $scopeResolver,
            $translate,
            $locale,
            $appState,
            $filesystem,
            $request,
            $csvParser,
            $packDictionary
        );
        $this->packUrlDictionary = $packUrlDictionary;
    }

    /**
     * @param string|null $locale
     * @param string|null $area
     * @param bool $forceReload
     * @return UrlTranslate
     * @throws LocalizedException
     */
    public function loadUrlData(?string $locale = null, ?string $area = null, bool $forceReload = false): UrlTranslate
    {
        $this->dataUrl = [];
        $currentLocale = $this->getLocale();

        if ($locale !== null) {
            $this->setLocale($locale);
        }

        if ($area === null) {
            $area = $this->_appState->getAreaCode();
        }

        $this->setConfig([
            self::CONFIG_AREA_KEY => $area,
            self::CONFIG_THEME_KEY => $this->_viewDesign->getConfigurationDesignTheme()
        ]);

        if (!$forceReload) {
            $data = $this->_loadCache();

            if ($data !== false) {
                $this->dataUrl = $data;

                if ($locale !== null) {
                    $this->setLocale($currentLocale);
                }

                return $this;
            }
        }

        $this->_loadModuleTranslation();
        $this->loadPackTranslation();
        $this->loadThemeTranslation();

        if (!$forceReload) {
            $this->_saveCache();
        }

        if ($locale !== null) {
            $this->setLocale($currentLocale);
        }

        return $this;
    }

    /**
     * @param array $data
     * @return UrlTranslate
     */
    protected function _addData($data): Translate
    {
        foreach ($data as $key => $value) {
            if ($key === $value) {
                if (isset($this->dataUrl[$key])) {
                    unset($this->dataUrl[$key]);
                }

                continue;
            }

            $this->dataUrl[$key] = $value;
        }

        return $this;
    }

    /**
     * @return UrlTranslate
     */
    private function loadThemeTranslation(): Translate
    {
        $themeFiles = $this->getThemeTranslationFilesList($this->getLocale());

        foreach ($themeFiles as $file) {
            if ($file) {
                $this->_addData($this->_getFileData($file));
            }
        }

        return $this;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function loadPackTranslation(): void
    {
        $data = $this->packUrlDictionary->getDictionary($this->getLocale());

        if (\count($data) > 0) {
            $this->_addData($data);
        }
    }

    /**
     * @param string $moduleName
     * @param string $locale
     * @return string
     */
    protected function _getModuleTranslationFile($moduleName, $locale): string
    {
        $file = $this->_modulesReader->getModuleDir(Module\Dir::MODULE_I18N_DIR, $moduleName);
        $file .= '/routes/' . $locale . '.csv';

        return $file;
    }

    /**
     * @param string $locale
     * @return array
     */
    private function getThemeTranslationFilesList(string $locale): array
    {
        $translationFiles = [];

        /** @var \Magento\Framework\View\Design\ThemeInterface $theme */
        foreach ($this->getParentThemesList() as $theme) {
            $config = $this->_config;
            $config['theme'] = $theme->getCode();
            $translationFiles[] = $this->getThemeTranslationFileName($locale, $config);
        }

        $translationFiles[] = $this->getThemeTranslationFileName($locale, $this->_config);

        return $translationFiles;
    }

    /**
     * @param string|null $locale
     * @param array $config
     * @return string|null
     */
    private function getThemeTranslationFileName(?string $locale, array $config): ?string
    {
        $fileName = $this->_viewFileSystem->getLocaleFileName(
            'i18n/routes/' . $locale . '.csv',
            $config
        );

        return \is_string($fileName) ? $fileName : null;
    }

    /**
     * @return array
     */
    private function getParentThemesList(): array
    {
        $themes = [];

        $parentTheme = $this->_viewDesign->getDesignTheme()->getParentTheme();

        while ($parentTheme) {
            $themes[] = $parentTheme;
            $parentTheme = $parentTheme->getParentTheme();
        }

        $themes = \array_reverse($themes);

        return $themes;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        if ($this->dataUrl === null) {
            return [];
        }

        return $this->dataUrl;
    }

    /**
     * @param array $values
     */
    public function setOriginValues(array $values): void
    {
        $this->originModuleFrontName = $values['moduleFrontName'];
        $this->originActionPath = $values['actionPath'];
        $this->originActionName = $values['actionName'];
    }

    /**
     * @return array
     */
    public function getOriginValues(): array
    {
        return [
            'moduleFrontName' => $this->originModuleFrontName,
            'actionPath' => $this->originActionPath,
            'actionName' => $this->originActionName
        ];
    }
}
