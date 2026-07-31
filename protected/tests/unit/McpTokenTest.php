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
}
