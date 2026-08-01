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

    public function testCredentialFieldsAreRedactedInAuditLog()
    {
        $token = McpToken::model()->findByPk($this->tokenId);
        McpToolRegistry::$classes = array('McpSnmpTemplateTools');

        McpToolRegistry::call('create_snmp_template', array(
            'name' => 'mcp-audit-redact-test',
            'community' => 'super-secret-community',
            'auth_passphrase' => 'super-secret-auth',
        ), 'readwrite', $token);

        $row = Yii::app()->db->createCommand('SELECT * FROM mcp_audit_log WHERE mcp_token_id = :id')
            ->bindValue(':id', $this->tokenId)
            ->queryRow();

        $this->assertNotFalse($row);
        $this->assertStringNotContainsString('super-secret-community', $row['params_json']);
        $this->assertStringNotContainsString('super-secret-auth', $row['params_json']);

        $params = json_decode($row['params_json'], true);
        $this->assertSame('[REDACTED]', $params['community']);
        $this->assertSame('[REDACTED]', $params['auth_passphrase']);

        Yii::app()->db->createCommand("DELETE FROM snmp_template WHERE name = 'mcp-audit-redact-test'")->execute();
    }

    public function testAuditLogRetainsTokenDescriptionAfterTokenDeleted()
    {
        $token = McpToken::model()->findByPk($this->tokenId);
        McpToolRegistry::call('fake_audited_write', array('foo' => 'bar'), 'readwrite', $token);

        Yii::app()->db->createCommand('DELETE FROM mcp_token WHERE id = :id')
            ->bindValue(':id', $this->tokenId)
            ->execute();

        $row = Yii::app()->db->createCommand('SELECT * FROM mcp_audit_log WHERE mcp_token_id = :id')
            ->bindValue(':id', $this->tokenId)
            ->queryRow();

        $this->assertNotFalse($row);
        $this->assertSame('audit test token', $row['token_description']);
    }
}
