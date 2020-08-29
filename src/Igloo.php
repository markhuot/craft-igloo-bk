<?php

namespace markhuot\igloo;

use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use markhuot\igloo\fields\IglooField;
use yii\base\Event;

class Igloo extends Plugin {

    public function init()
    {
        parent::init();

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = IglooField::class;
            }
        );
    }

}
