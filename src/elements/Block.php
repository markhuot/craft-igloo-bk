<?php

namespace markhuot\igloo\elements;

use craft\base\Element;

class Block extends Element {

    static function displayName(): string
    {
        return 'Block';
    }

    static function pluralDisplayName(): string
    {
        return 'Blocks';
    }

    static function hasContent(): bool
    {
        return true;
    }

}
