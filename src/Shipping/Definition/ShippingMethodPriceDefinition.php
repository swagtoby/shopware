<?php declare(strict_types=1);

namespace Shopware\Shipping\Definition;

use Shopware\Api\Entity\EntityDefinition;
use Shopware\Api\Entity\EntityExtensionInterface;
use Shopware\Api\Entity\Field\DateField;
use Shopware\Api\Entity\Field\FkField;
use Shopware\Api\Entity\Field\FloatField;
use Shopware\Api\Entity\Field\ManyToOneAssociationField;
use Shopware\Api\Entity\Field\UuidField;
use Shopware\Api\Entity\FieldCollection;
use Shopware\Api\Write\Flag\PrimaryKey;
use Shopware\Api\Write\Flag\Required;
use Shopware\Shipping\Collection\ShippingMethodPriceBasicCollection;
use Shopware\Shipping\Collection\ShippingMethodPriceDetailCollection;
use Shopware\Shipping\Event\ShippingMethodPrice\ShippingMethodPriceWrittenEvent;
use Shopware\Shipping\Repository\ShippingMethodPriceRepository;
use Shopware\Shipping\Struct\ShippingMethodPriceBasicStruct;
use Shopware\Shipping\Struct\ShippingMethodPriceDetailStruct;

class ShippingMethodPriceDefinition extends EntityDefinition
{
    /**
     * @var FieldCollection
     */
    protected static $primaryKeys;

    /**
     * @var FieldCollection
     */
    protected static $fields;

    /**
     * @var EntityExtensionInterface[]
     */
    protected static $extensions = [];

    public static function getEntityName(): string
    {
        return 'shipping_method_price';
    }

    public static function getFields(): FieldCollection
    {
        if (self::$fields) {
            return self::$fields;
        }

        self::$fields = new FieldCollection([
            (new UuidField('uuid', 'uuid'))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('shipping_method_uuid', 'shippingMethodUuid', ShippingMethodDefinition::class))->setFlags(new Required()),
            (new FloatField('quantity_from', 'quantityFrom'))->setFlags(new Required()),
            (new FloatField('price', 'price'))->setFlags(new Required()),
            (new FloatField('factor', 'factor'))->setFlags(new Required()),
            new DateField('created_at', 'createdAt'),
            new DateField('updated_at', 'updatedAt'),
            new ManyToOneAssociationField('shippingMethod', 'shipping_method_uuid', ShippingMethodDefinition::class, false),
        ]);

        foreach (self::$extensions as $extension) {
            $extension->extendFields(self::$fields);
        }

        return self::$fields;
    }

    public static function getRepositoryClass(): string
    {
        return ShippingMethodPriceRepository::class;
    }

    public static function getBasicCollectionClass(): string
    {
        return ShippingMethodPriceBasicCollection::class;
    }

    public static function getWrittenEventClass(): string
    {
        return ShippingMethodPriceWrittenEvent::class;
    }

    public static function getBasicStructClass(): string
    {
        return ShippingMethodPriceBasicStruct::class;
    }

    public static function getTranslationDefinitionClass(): ?string
    {
        return null;
    }

    public static function getDetailStructClass(): string
    {
        return ShippingMethodPriceDetailStruct::class;
    }

    public static function getDetailCollectionClass(): string
    {
        return ShippingMethodPriceDetailCollection::class;
    }
}