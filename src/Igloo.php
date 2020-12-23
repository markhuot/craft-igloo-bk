<?php

namespace markhuot\igloo;

use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Fields;
use craft\web\UrlManager;
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

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['POST igloo/blocks/upsert'] = 'igloo/block/upsert';
                $event->rules['DELETE igloo/blocks/<id:\d+>'] = 'igloo/block/delete';
                $event->rules['GET igloo/blocks/<id:\d+>/styles'] = 'igloo/block/styles';
                $event->rules['POST igloo/blocks/<id:\d+>/styles'] = 'igloo/block/store';
                $event->rules['GET igloo/blocks/<id:\d+>/actions'] = 'igloo/block/actions';
                $event->rules['GET igloo/tree/<tree:.+>/add-layer'] = 'igloo/tree/add-layer';
                $event->rules['POST igloo/tree/<tree:.+>/store-new-layer'] = 'igloo/tree/store-new-layer';
                $event->rules['POST igloo/tree/<tree:.+>/move/<blockId:\d+>'] = 'igloo/tree/move-layer';
            }
        );
    }

}
