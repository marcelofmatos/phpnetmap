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

    public function testLoadIgnoresObsoleteKeysFromAnOlderParamsIniFile()
    {
        // A deploy upgrading straight from a pre-per-token-mode release
        // (mcpMode used to live directly on ConfigForm, see
        // ConfigFormMcpTest::testMcpModeIsNoLongerAConfigFormProperty) still
        // has this key sitting in its persisted params.ini. load() used to
        // assign every ini key straight onto $this, which threw
        // 'Property "ConfigForm.mcpMode" is not defined.' on that property
        // and broke both /config and /mcp (McpController::actionIndex()
        // calls load() too) for anyone who hadn't rewritten the file since.
        file_put_contents(PARAMS_INI_FILE_PATH, 'adminEmail = "ops@example.org"' . "\r\n" . 'mcpMode = "readonly"');

        $model = new ConfigForm;
        $model->load();

        $this->assertSame('ops@example.org', $model->adminEmail);
    }
}
