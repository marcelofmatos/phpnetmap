<?php

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    public function testYiiApplicationBoots()
    {
        $this->assertInstanceOf('CWebApplication', Yii::app());
    }

    public function testDatabaseConnectionWorks()
    {
        $tableNames = Yii::app()->db->schema->getTableNames();
        $this->assertContains('host', $tableNames);
    }
}
