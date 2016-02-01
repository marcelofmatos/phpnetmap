<?php
/* @var $this HostController */
/* @var $model Host */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('TbActiveForm', array(
	'id'=>'host-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
        <?php echo $form->labelEx($model, 'name'); ?>
        <?php echo $form->textField($model, 'name'); ?>
        <?php echo $form->error($model, 'name'); ?>
	</div>

	<div class="row">
        <?php echo $form->labelEx($model, 'type'); ?>
        <?php echo $form->dropDownList($model, 'type', $model->getTypes()); ?>
        <?php echo $form->error($model, 'type'); ?>
	</div>

	<div class="row">
        <?php echo $form->labelEx($model, 'mac'); ?>
        <?php echo $form->textField($model, 'mac'); ?>
        <?php echo $form->error($model, 'mac'); ?>
	</div>

	<div class="row">
        <?php echo $form->labelEx($model, 'ip'); ?>
        <?php echo $form->textField($model, 'ip'); ?>
        <?php echo $form->error($model, 'ip'); ?>
	</div>

	<div class="row">
        <?php echo $form->labelEx($model, 'snmp_template_id'); ?>
        <?php
        echo $form->dropDownList(
                $model, 'snmp_template_id', CHtml::listData(SnmpTemplate::model()->findAll(), 'id', 'name'), array('empty' => ''));
        ?>
        <?php echo $form->error($model, 'snmp_template_id'); ?>
	</div>

	<div class="row">
        <?php echo $form->labelEx($model, 'host_face_id'); ?>
        <?php
        echo $form->dropDownList(
                $model, 'host_face_id', CHtml::listData(HostFace::model()->findAll(), 'id', 'name'), array('empty' => ''));
        ?>
            <?php echo CHtml::link('Create host face', array( 'hostFace/create' )) ?>
        <?php echo $form->error($model, 'host_face_id'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>


    <?php if (!$model->isNewRecord): ?>
    <hr />
    <h3>Connections</h3>
        <table class="host-connection-list">
            <tr><th width="20">Port</th><th>Host</th><th>on port</th><th>Type</th><th></th></tr>
            <?php foreach ($model->getConnections() as $connection): ?>
                <tr>
                    <td><?php echo $connection->host_src_port; ?></td>
                    <td><?php echo CHtml::link($connection->hostDst->name, array( 'host/viewByName/'.$connection->hostDst->name )) ?></td>
                    <td><?php echo $connection->host_dst_port; ?></td>
                    <td><?php echo $connection->type; ?></td>
                    <td>
                        <?php
                        //echo CHtml::ajaxLink('Delete', array('/hostConnection/delete','id' => $connection->id), array('method'=>'POST'),array('confirm' => 'Are you sure?'));
                        echo CHtml::link('Update', array('/connection/update?id=' . $connection->id));
                        ?>
                        <?php
                        //echo CHtml::ajaxLink('Delete', array('/hostConnection/delete','id' => $connection->id), array('method'=>'POST'),array('confirm' => 'Are you sure?'));
                        echo CHtml::link('Delete', "#", array("submit" => array('/connection/delete', 'id' => $connection->id), 'confirm' => 'Are you sure?', 'csrf' => true));
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
               
            <tr>
                <td colspan="5" style="text-align: center">
                    <?php echo CHtml::link('New', array('/connection/create?host_src_id=' . $model->id)); ?>
                </td>
            </tr>
        </table>
    <?php endif; ?>

<?php $this->endWidget(); ?>

</div><!-- form -->