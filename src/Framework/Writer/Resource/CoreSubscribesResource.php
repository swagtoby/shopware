<?php declare(strict_types=1);

namespace Shopware\Framework\Write\Resource;

use Shopware\Framework\Write\Field\IntField;
use Shopware\Framework\Write\Field\StringField;
use Shopware\Framework\Write\Flag\Required;
use Shopware\Framework\Write\Resource;

class CoreSubscribesResource extends Resource
{
    protected const SUBSCRIBE_FIELD = 'subscribe';
    protected const TYPE_FIELD = 'type';
    protected const LISTENER_FIELD = 'listener';
    protected const PLUGINID_FIELD = 'pluginID';
    protected const POSITION_FIELD = 'position';

    public function __construct()
    {
        parent::__construct('s_core_subscribes');

        $this->fields[self::SUBSCRIBE_FIELD] = (new StringField('subscribe'))->setFlags(new Required());
        $this->fields[self::TYPE_FIELD] = (new IntField('type'))->setFlags(new Required());
        $this->fields[self::LISTENER_FIELD] = (new StringField('listener'))->setFlags(new Required());
        $this->fields[self::PLUGINID_FIELD] = new IntField('pluginID');
        $this->fields[self::POSITION_FIELD] = (new IntField('position'))->setFlags(new Required());
    }

    public function getWriteOrder(): array
    {
        return [
            \Shopware\Framework\Write\Resource\CoreSubscribesResource::class,
        ];
    }

    public static function createWrittenEvent(array $updates, array $errors = []): \Shopware\Framework\Event\CoreSubscribesWrittenEvent
    {
        $event = new \Shopware\Framework\Event\CoreSubscribesWrittenEvent($updates[self::class] ?? [], $errors);

        unset($updates[self::class]);

        if (!empty($updates[\Shopware\Framework\Write\Resource\CoreSubscribesResource::class])) {
            $event->addEvent(\Shopware\Framework\Write\Resource\CoreSubscribesResource::createWrittenEvent($updates));
        }

        return $event;
    }
}