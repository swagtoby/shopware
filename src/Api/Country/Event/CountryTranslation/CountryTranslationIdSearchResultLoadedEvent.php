<?php declare(strict_types=1);

namespace Shopware\Api\Country\Event\CountryTranslation;

use Shopware\Api\Entity\Search\IdSearchResult;
use Shopware\Context\Struct\ShopContext;
use Shopware\Framework\Event\NestedEvent;

class CountryTranslationIdSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'country_translation.id.search.result.loaded';

    /**
     * @var IdSearchResult
     */
    protected $result;

    public function __construct(IdSearchResult $result)
    {
        $this->result = $result;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ShopContext
    {
        return $this->result->getContext();
    }

    public function getResult(): IdSearchResult
    {
        return $this->result;
    }
}