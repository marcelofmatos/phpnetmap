<?php

class McpJsonRpc
{
    /**
     * @param string $rawBody
     * @return array{id: mixed, method: string, params: array}|null
     */
    public static function parseRequest($rawBody)
    {
        $request = json_decode($rawBody, true);
        if (!is_array($request) || !isset($request['method'])) {
            return null;
        }
        return array(
            'id' => isset($request['id']) ? $request['id'] : null,
            'method' => $request['method'],
            'params' => isset($request['params']) ? $request['params'] : array(),
        );
    }

    public static function result($id, $result)
    {
        return array('jsonrpc' => '2.0', 'id' => $id, 'result' => $result);
    }

    public static function error($id, $code, $message)
    {
        return array('jsonrpc' => '2.0', 'id' => $id, 'error' => array('code' => $code, 'message' => $message));
    }

    /**
     * @param string $authorizationHeader
     * @return string|null the raw token, or null if the header isn't a
     *  well-formed "Bearer <token>" value
     */
    public static function extractBearerToken($authorizationHeader)
    {
        if (stripos($authorizationHeader, 'Bearer ') !== 0) {
            return null;
        }
        $token = substr($authorizationHeader, 7);
        return $token !== '' ? $token : null;
    }
}
