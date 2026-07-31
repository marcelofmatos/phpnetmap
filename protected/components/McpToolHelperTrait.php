<?php

trait McpToolHelperTrait
{
    private static function errorsToMessage($model)
    {
        $messages = array();
        foreach ($model->getErrors() as $errors) {
            $messages[] = implode(' ', $errors);
        }
        return implode(' ', $messages);
    }

    private static function idSchema()
    {
        return array('type' => 'object', 'properties' => array('id' => array('type' => 'integer')), 'required' => array('id'));
    }

    /**
     * Safely extracts an integer id from tool arguments — rejects
     * non-numeric values (arrays, objects, strings) rather than letting
     * PHP's (int) cast silently coerce them (e.g. a non-empty array casts
     * to 1, which would target an unrelated record).
     */
    private static function extractId($arguments, $key = 'id')
    {
        if (!isset($arguments[$key]) || !is_numeric($arguments[$key])) {
            return null;
        }
        return (int) $arguments[$key];
    }
}
