<?php

use PHPUnit\Framework\TestCase;

class ConfigFormTest extends TestCase
{
    private $backupExisted;
    private $backup;

    protected function setUp(): void
    {
        $this->backupExisted = is_file(PARAMS_INI_FILE_PATH);
        $this->backup = $this->backupExisted ? file_get_contents(PARAMS_INI_FILE_PATH) : null;
    }

    protected function tearDown(): void
    {
        if ($this->backupExisted) {
            file_put_contents(PARAMS_INI_FILE_PATH, $this->backup);
        } else {
            @unlink(PARAMS_INI_FILE_PATH);
        }
    }

    public function testIsUnconfiguredWhenFileMissing()
    {
        @unlink(PARAMS_INI_FILE_PATH);
        $this->assertTrue(ConfigForm::isUnconfigured());
    }

    public function testIsUnconfiguredWhenFileExistsButEmpty()
    {
        file_put_contents(PARAMS_INI_FILE_PATH, '');
        $this->assertTrue(ConfigForm::isUnconfigured());
    }

    public function testIsConfiguredAfterSave()
    {
        $model = new ConfigForm;
        $model->load();
        $model->save();

        $this->assertFalse(ConfigForm::isUnconfigured());
    }
}
