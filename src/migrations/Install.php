<?php

namespace markhuot\igloo\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTable(
            '{{%igloo_blocks}}',
            [
                'id'          => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid(),
                'tree'        => $this->string(),
                'type'        => $this->string(),
                'contentId'   => $this->integer()->unsigned(),
                'lft'         => $this->integer()->unsigned(),
                'rgt'         => $this->integer()->unsigned(),
            ]
        );

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
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%igloo_blocks}}');
        $this->dropTableIfExists('{{%igloo_text}}');
    }
}
