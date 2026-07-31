<?php
/* @var $this McpTokenController */
/* @var $model McpToken */

$this->breadcrumbs = array(
    'MCP Tokens' => array('admin'),
    'Create',
);

$this->menu = array(
    array('label' => 'Manage MCP Tokens', 'url' => array('admin')),
);
?>

<h1>Create MCP Token</h1>

<?php echo $this->renderPartial('_form', array('model' => $model)); ?>
