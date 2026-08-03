<?php

use PHPUnit\Framework\TestCase;

class ConfigFormMcpTest extends TestCase
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

    public function testMcpEnabledDefaultsToFalse()
    {
        $model = new ConfigForm;
        $this->assertFalse($model->mcpEnabled);
    }

    public function testMcpEnabledRoundTripsThroughIniFile()
    {
        $model = new ConfigForm;
        $model->load();
        $model->mcpEnabled = true;
        $model->save();

        $reloaded = new ConfigForm;
        $reloaded->load();

        $this->assertTrue($reloaded->mcpEnabled);
    }

    public function testMcpModeIsNoLongerAConfigFormProperty()
    {
        $model = new ConfigForm;
        $this->assertObjectNotHasAttribute('mcpMode', $model);
    }
}
