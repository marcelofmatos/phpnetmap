<?php
/* @var $this McpTokenController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs = array(
    'MCP Tokens' => array('admin'),
    'Manage',
);

$this->menu = array(
    array('label' => 'Create MCP Token', 'url' => array('create')),
);
?>

<h1>MCP Tokens</h1>

<?php if (Yii::app()->user->hasFlash('mcpTokenCreated')): ?>
<div class="info">
    <?php echo CHtml::encode(Yii::app()->user->getFlash('mcpTokenCreated')); ?>
</div>
<?php endif; ?>

<?php $this->widget('bootstrap.widgets.TbGridView', array(
    'id' => 'mcp-token-grid',
    'dataProvider' => $dataProvider,
    'columns' => array(
        'id',
        'description',
        'token_prefix',
        'mode',
        'expires_at',
        'last_used_at',
        'created_at',
        array(
            'class' => 'CButtonColumn',
            'template' => '<span style="white-space:nowrap">{update} {delete}</span>',
        ),
    ),
)); ?>
