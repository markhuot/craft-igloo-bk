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
                'uid'         => $this->uid()->notNull(),
                'type'        => $this->string()->notNull(),
            ]
        );

        $this->createTable(
            '{{%igloo_block_structure}}',
            [
                'id'          => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid()->notNull(),
                'tree'        => $this->string()->notNull(),
                'slot'        => $this->string(),
                'lft'         => $this->integer()->unsigned()->notNull(),
                'rgt'         => $this->integer()->unsigned()->notNull(),
                ]
            );
            
        $this->createTable(
            '{{%igloo_block_attributes}}',
            [
                'id'          => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid()->notNull(),
                'data'        => $this->longText()->notNull(),
            ]
        );

        $this->createTable(
            '{{%igloo_content_text}}',
            [
                'id'          => $this->primaryKey(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid()->notNull(),
                'content'     => $this->text()->notNull(),
            ]
        );
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%igloo_blocks}}');
        $this->dropTableIfExists('{{%igloo_block_structure}}');
        $this->dropTableIfExists('{{%igloo_block_attributes}}');
        $this->dropTableIfExists('{{%igloo_content_text}}');
    }
}
