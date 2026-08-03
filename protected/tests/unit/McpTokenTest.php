<?php

use PHPUnit\Framework\TestCase;

class McpTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        Yii::app()->db->createCommand('DELETE FROM mcp_token')->execute();
    }

    public function testGenerateTokenSetsHashAndPrefixAndReturnsRawToken()
    {
        $model = new McpToken;
        $model->description = 'test token';
        $model->expires_at = date('Y-m-d', strtotime('+30 days'));

        $raw = $model->generateToken();

        $this->assertNotEmpty($raw);
        $this->assertSame(substr($raw, 0, 8), $model->token_prefix);
        $this->assertSame(hash('sha256', $raw), $model->token_hash);
        $this->assertTrue($model->save());
    }

    public function testFindValidByRawTokenReturnsMatchingUnexpiredToken()
    {
        $model = new McpToken;
        $model->description = 'test token';
        $model->expires_at = date('Y-m-d', strtotime('+30 days'));
        $raw = $model->generateToken();
        $model->save();

        $found = McpToken::findValidByRawToken($raw);

        $this->assertNotNull($found);
        $this->assertSame($model->id, $found->id);
    }

    public function testFindValidByRawTokenReturnsNullForExpiredToken()
    {
        $model = new McpToken;
        $model->description = 'expired token';
        $model->expires_at = date('Y-m-d', strtotime('-1 day'));
        $raw = $model->generateToken();
        $model->save();

        $this->assertNull(McpToken::findValidByRawToken($raw));
    }

    public function testFindValidByRawTokenReturnsNullForUnknownToken()
    {
        $this->assertNull(McpToken::findValidByRawToken('not-a-real-token'));
    }

    public function testModeDefaultsToReadonly()
    {
        $model = new McpToken;
        $this->assertSame('readonly', $model->mode);
    }

    public function testModeAcceptsReadwrite()
    {
        $model = new McpToken;
        $model->description = 'readwrite token';
        $model->expires_at = date('Y-m-d', strtotime('+30 days'));
        $model->mode = 'readwrite';
        $model->generateToken();

        $this->assertTrue($model->save());
        $this->assertSame('readwrite', McpToken::model()->findByPk($model->id)->mode);
    }

    public function testModeRejectsInvalidValue()
    {
        $model = new McpToken;
        $model->description = 'bad mode token';
        $model->expires_at = date('Y-m-d', strtotime('+30 days'));
        $model->mode = 'delete-everything';
        $model->generateToken();

        $this->assertFalse($model->save());
        $this->assertArrayHasKey('mode', $model->getErrors());
    }
}
