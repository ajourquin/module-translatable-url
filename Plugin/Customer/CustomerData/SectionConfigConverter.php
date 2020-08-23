<?php declare(strict_types = 1);

/**
 * @author    Aurélien Jourquin <aurelien@growzup.com>
 * @link      http://www.ajourquin.com
 */

namespace Ajourquin\TranslatableUrl\Plugin\Customer\CustomerData;

use Ajourquin\TranslatableUrl\Model\Config;
use Ajourquin\TranslatableUrl\Model\UrlTranslate;
use Magento\Framework\Config\ConverterInterface;
use Magento\Framework\Exception\LocalizedException;

class SectionConfigConverter
{
    /** @var UrlTranslate */
    private $urlTranslate;

    /** @var Config */
    private $config;

    /**
     * SectionConfigConverter constructor.
     * @param UrlTranslate $urlTranslate
     * @param Config $config
     */
    public function __construct(
        UrlTranslate $urlTranslate,
        Config $config
    ) {
        $this->urlTranslate = $urlTranslate;
        $this->config = $config;
    }

    /**
     * @param ConverterInterface $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     */
    public function afterConvert(ConverterInterface $subject, array $result): array
    {
        if ($this->config->isEnabled()) {
            $translations = $this->urlTranslate->loadUrlData()->getData();
            $sections = \array_keys($result['sections']);

            foreach ($sections as $section) {
                if ($section !== '*') {
                    $partFrom = [];
                    $partTo = [];
                    $parts = \explode('/', $section);

                    foreach ($parts as &$part) {
                        if (\array_key_exists($part, $translations)) {
                            $partFrom[] = $part;
                            $partTo[] = $translations[$part];
                        }
                    }

                    if (\count($partFrom) > 0) {
                        $translatedKey = \str_replace($partFrom, $partTo, $section);
                        $result['sections'][$translatedKey] = $result['sections'][$section];
                        unset($result['sections'][$section]);
                    }
                }
            }
        }

        return $result;
    }
}
