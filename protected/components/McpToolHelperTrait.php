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
}
