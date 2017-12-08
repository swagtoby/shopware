<?php declare(strict_types=1);

namespace Shopware\Plugin\Event\Plugin;

use Shopware\Context\Struct\TranslationContext;
use Shopware\Framework\Event\NestedEvent;
use Shopware\Plugin\Struct\PluginSearchResult;

class PluginSearchResultLoadedEvent extends NestedEvent
{
    const NAME = 'plugin.search.result.loaded';

    /**
     * @var PluginSearchResult
     */
    protected $result;

    public function __construct(PluginSearchResult $result)
    {
        $this->result = $result;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): TranslationContext
    {
        return $this->result->getContext();
    }
}