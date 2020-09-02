<?php

namespace markhuot\igloo\models;

use markhuot\igloo\base\Block;

class Split extends Block {

    /**
     * The number of columns
     * 
     * @var int
     */
    public $count = 2;

    /**
     * The CSS layout to size the columns
     * 
     * @var string
     */
    public $template = "1fr 1fr";

    /** 
     * The actual column contents are boxes containing arbitrary content
     * 
     * @var Box[]
     */
    public $columns = [];

    /**
     * @inheritdoc
     */
    public $slots = [
        'columns',
    ];

}