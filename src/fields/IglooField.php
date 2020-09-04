<?php

namespace markhuot\igloo\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use markhuot\igloo\models\Blockquote;
use markhuot\igloo\models\Box;
use markhuot\igloo\models\Text;
use markhuot\igloo\services\Blocks;

class IglooField extends Field {

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $tree = $value ?? uniqid();

        $author = new Text('Shakespeare');
        $author->styles->color = 'red';
        
        $blockquote = new Blockquote;
        $blockquote->content = [new Text('To be or not to be...')];
        $blockquote->author = [$author];
        $blockquote->styles->borderLeft = '2px solid green';
        $blockquote->styles->paddingLeft = '0.5rem';
        $blockquote->styles->color = 'green';
        
        $box = (new Box())
            ->append(new Text('A preamble to the quote!'))
            ->append($blockquote)
            ->append(new Text('This is some postscript of our quote!'))
        ;
        $blocks = [$box];
        
        $tree = '6tfhju65ff';
        // (new Blocks())->saveBlock($box, $tree);
        $blocks = (new Blocks())->getTree($tree);
        //dd($blocks);

        return Craft::$app->view->renderTemplate('igloo/igloo', [
            'blocks' => $blocks,
            'tree' => $tree,
        ]);
    }

}
