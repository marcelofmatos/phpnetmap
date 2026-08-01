<?php

use PHPUnit\Framework\TestCase;

class McpAuditFakeReadwriteTool
{
    public static function definitions()
    {
        return array(
            'fake_audited_write' => array(
                'mode' => 'readwrite',
                'description' => 'Fake readwrite tool for audit-log tests',
                'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
                'handler' => array(__CLASS__, 'write'),
            ),
        );
    }

    public static function write($arguments)
    {
        return array('written' => true);
    }
}

class McpToolRegistryAuditTest extends TestCase
{
    private $originalClasses;
    private $tokenId;

    protected function setUp(): void
    {
        $this->originalClasses = McpToolRegistry::$classes;
        McpToolRegistry::$classes = array('McpAuditFakeReadwriteTool');

        $token = new McpToken;
        $token->description = 'audit test token';
        $token->expires_at = date('Y-m-d', strtotime('+1 day'));
        $token->generateToken();
        $token->save();
        $this->tokenId = $token->id;
    }

    protected function tearDown(): void
    {
        McpToolRegistry::$classes = $this->originalClasses;
        Yii::app()->db->createCommand("DELETE FROM mcp_audit_log WHERE mcp_token_id = {$this->tokenId}")->execute();
        Yii::app()->db->createCommand("DELETE FROM mcp_token WHERE id = {$this->tokenId}")->execute();
    }

    public function testSuccessfulWriteToolCallIsAudited()
    {
        $token = McpToken::model()->findByPk($this->tokenId);
        McpToolRegistry::call('fake_audited_write', array('foo' => 'bar'), 'readwrite', $token);

        $row = Yii::app()->db->createCommand('SELECT * FROM mcp_audit_log WHERE mcp_token_id = :id')
            ->bindValue(':id', $this->tokenId)
            ->queryRow();

        $this->assertNotFalse($row);
        $this->assertSame('fake_audited_write', $row['tool_name']);
        $this->assertSame(array('foo' => 'bar'), json_decode($row['params_json'], true));
    }
}
