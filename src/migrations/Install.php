<?php

namespace markhuot\igloo\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTable(
            '{{%igloo_content_text}}',
            [
                'id'          => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid(),
                'content'     => $this->text(),
            ]
        );

        $this->createTable(
            '{{%igloo_content_blockquote}}',
            [
                'id'          => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid(),
                'contentId'   => $this->integer()->unsigned(),
                'authorId'   => $this->integer()->unsigned(),
             ]
        );
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%igloo_text}}');
        $this->dropTableIfExists('{{%igloo_blockquote}}');
    }
}
