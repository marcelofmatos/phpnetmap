<style>   
    #yw1 {
        float: left;
    }
</style>
<style media="print">
    a:link:after, a:visited:after {content:"" !important;}
    .pagebreak {page-break-before: always;}
    #page {border-color: transparent !important;}
    #header, #sidebar {display:none}
</style>
<?php
$this->pageTitle = $model ." ". $this->pageTitle;

$this->breadcrumbs = array(
    'Hosts' => array('admin'),
    $model->name => array('viewByName', 'name' => $model->name),
    'ARP Table'
);

$this->menu = array(
    array('label' => 'Create Host Connection', 'url' => array('/connection/create?host_src_id='.$model->id)),
    array('label' => 'Update Host', 'url' => array('update', 'id' => $model->id)),
    array('label' => 'View Host', 'url' => array('viewByName', 'name' => $model->name)),
);
?>
<h3>ARP Table</h3>
<?php
        $this->renderPartial('/host/_arpTable', array('model' => $model, 'arp_table' => $arp_table));
?>
