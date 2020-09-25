<?php

namespace markhuot\igloo\base;

use markhuot\igloo\valueobjects\Attributes as AttributesVO;

trait Attributes {

    /** @var AttributesVO */
    public $attributes;

    function initAttributes()
    {
        $config = [];

        if (!empty($this->attributes)) {
            $config = $this->attributes;
        }
        
        $this->attributes = new AttributesVO($config);
    }

    function serializeAttributes()
    {
        $record = ['{{%igloo_block_attributes}}' => []];
        $data = $this->attributes->toArray();
        
        if (empty($data)) {
            return $record;
        }

        $record['{{%igloo_block_attributes}}']['data'] = json_encode($data);

        return $record;
    }

    /**
     * Unserialize raw data from the persistent storage in to the model
     * 
     * @var array $config
     */
    function unserializeAttributes($config)
    {
        $json = json_decode($config['{{%igloo_block_attributes}}']['data'] ?? "{}", true);
        $this->setAttributes($json);
    }
    
    /**
     * Fluently set the styles
     *
     * @param array $styles
     * @return static
     */
    function setAttributes($attributes, $safeOnly = true)
    {
        foreach ($attributes as $key => $value) {
            $this->attributes->set($key, $value);
        }

        return $this;
    }
    
    

}