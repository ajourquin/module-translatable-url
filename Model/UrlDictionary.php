<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Model;

use Magento\Framework\App\Language\Config;
use Magento\Framework\App\Language\ConfigFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Config\Dom\ValidationException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Directory\ReadFactory;

class UrlDictionary
{
    /** @var array */
    private $paths;

    /** @var ReadFactory */
    private $directoryReadFactory;

    /** @var ComponentRegistrar */
    private $componentRegistrar;

    /** @var ConfigFactory */
    private $configFactory;

    /** @var array */
    private $packList = [];

    /**
     * UrlDictionary constructor.
     * @param ReadFactory $directoryReadFactory
     * @param ComponentRegistrar $componentRegistrar
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        ReadFactory $directoryReadFactory,
        ComponentRegistrar $componentRegistrar,
        ConfigFactory $configFactory
    ) {
        $this->directoryReadFactory = $directoryReadFactory;
        $this->componentRegistrar = $componentRegistrar;
        $this->configFactory = $configFactory;
    }

    /**
     * @param string $languageCode
     * @return array
     * @throws LocalizedException
     */
    public function getDictionary(string $languageCode): array
    {
        $languages = [];
        $this->paths = $this->componentRegistrar->getPaths(ComponentRegistrar::LANGUAGE);

        foreach ($this->paths as $path) {
            $directoryRead = $this->directoryReadFactory->create($path);

            if ($directoryRead->isExist('language.xml')) {
                $xmlSource = $directoryRead->readFile('language.xml');

                try {
                    $languageConfig = $this->configFactory->create(['source' => $xmlSource]);
                } catch (ValidationException $e) {
                    throw new LocalizedException(
                        \__(
                            'The XML in file "%1" is invalid:' . "\n%2\nVerify the XML and try again.",
                            [$path . '/language.xml', $e->getMessage()]
                        ),
                        $e
                    );
                }

                $this->packList[$languageConfig->getVendor()][$languageConfig->getPackage()] = $languageConfig;

                if ($languageConfig->getCode() === $languageCode) {
                    $languages[] = $languageConfig;
                }
            }
        }

        // Collect the inherited packages with meta-information of sorting
        $packs = [];

        foreach ($languages as $languageConfig) {
            $this->collectInheritedPacks($languageConfig, $packs);
        }

        \uasort($packs, [$this, 'sortInherited']);

        // Merge all packages of translation to one dictionary
        $result = [];

        foreach ($packs as $packInfo) {
            /** @var Config $languageConfig */
            $languageConfig = $packInfo['language'];
            $dictionary = $this->readPackCsv($languageConfig->getVendor(), $languageConfig->getPackage());

            foreach ($dictionary as $key => $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param Config $languageConfig
     * @param array $result
     * @param int $level
     * @param array $visitedPacks
     * @return void
     */
    private function collectInheritedPacks(
        Config $languageConfig,
        array &$result,
        int $level = 0,
        array &$visitedPacks = []
    ): void {
        $packKey = \implode('|', [$languageConfig->getVendor(), $languageConfig->getPackage()]);

        if (!isset($visitedPacks[$packKey]) &&
            (!isset($result[$packKey]) || $result[$packKey]['inheritance_level'] < $level)
        ) {
            $visitedPacks[$packKey] = true;
            $result[$packKey] = [
                'inheritance_level' => $level,
                'sort_order'        => $languageConfig->getSortOrder(),
                'language'          => $languageConfig,
                'key'               => $packKey,
            ];

            foreach ($languageConfig->getUses() as $reuse) {
                if (isset($this->packList[$reuse['vendor']][$reuse['package']])) {
                    $parentLanguageConfig = $this->packList[$reuse['vendor']][$reuse['package']];
                    $this->collectInheritedPacks($parentLanguageConfig, $result, $level + 1, $visitedPacks);
                }
            }
        }
    }

    /**
     * @param array $current
     * @param array $next
     * @return int
     */
    private function sortInherited(array $current, array $next): int
    {
        if ($current['inheritance_level'] > $next['inheritance_level']) {
            return -1;
        } elseif ($current['inheritance_level'] < $next['inheritance_level']) {
            return 1;
        }

        if ($current['sort_order'] > $next['sort_order']) {
            return 1;
        } elseif ($current['sort_order'] < $next['sort_order']) {
            return -1;
        }

        return \strcmp($current['key'], $next['key']);
    }

    /**
     * @param string $vendor
     * @param string $package
     * @return array
     * @throws FileSystemException
     */
    private function readPackCsv(string $vendor, string $package): array
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::LANGUAGE, \strtolower($vendor . '_' . $package));
        $result = [];

        if (isset($path)) {
            $directoryRead = $this->directoryReadFactory->create($path);
            $foundCsvFiles = $directoryRead->search('*.csv', 'routes/');

            foreach ($foundCsvFiles as $foundCsvFile) {
                $file = $directoryRead->openFile($foundCsvFile);

                while (($row = $file->readCsv()) !== false) {
                    if (\is_array($row) && \count($row) > 1) {
                        $result[$row[0]] = $row[1];
                    }
                }
            }
        }

        return $result;
    }
}
