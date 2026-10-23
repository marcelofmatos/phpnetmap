<?php

use PHPUnit\Framework\TestCase;

class RenderCapturingHostController extends HostController
{
    public $renderedView;
    public $renderedData;

    public function render($view, $data = null, $return = false)
    {
        $this->renderedView = $view;
        $this->renderedData = $data;
    }
}

class HostControllerPhp8Test extends TestCase
{
    private $hostIds = array();

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
        self::useTempAssetDir();
        // Grid sort links and forms build URLs from the current request.
        $_SERVER['REQUEST_URI'] = '/host/camTable';
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        unset($_SERVER['REQUEST_URI']);
        unset($_GET['id']);
        Yii::app()->setController(null);
        foreach ($this->hostIds as $id) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$id}")->execute();
        }
    }

    // Grid widgets publish assets next to the entry script (vendor/bin
    // under PHPUnit) — point the asset manager at a writable temp dir.
    private static function useTempAssetDir()
    {
        $dir = sys_get_temp_dir() . '/pnm-test-assets';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        Yii::app()->assetManager->basePath = $dir;
        Yii::app()->assetManager->baseUrl = '/assets';
    }

    private function saveHost($name, $type)
    {
        $host = new Host();
        $host->name = $name;
        $host->type = $type;
        $this->assertTrue($host->save(false));
        $this->hostIds[] = $host->id;
        return $host;
    }

    // An empty ARP table left $arp_table undefined: a notice on PHP 7, a
    // warning (HTTP 500 on host/arpTable) on PHP 8.
    public function testArpTableWithEmptyArpTableRendersWithoutWarning()
    {
        $host = $this->saveHost('arp-empty-test', Host::TYPE_UNKNOWN);
        $_GET['id'] = $host->id;

        $controller = new RenderCapturingHostController('host');
        $controller->actionArpTable();

        $this->assertSame('arpTable', $controller->renderedView);
        $this->assertSame(array(), $controller->renderedData['arp_table']);
    }

    // CAM rows for MACs not registered as hosts carry host/host_dst = null;
    // the grid expressions read ->name/->id off them (notice on PHP 7,
    // warning on PHP 8). The rows also have no 'id' key, which
    // CArrayDataProvider's default keyField read the same way.
    public function testCamTableGridRendersRowsWithoutRegisteredHosts()
    {
        $switch = $this->saveHost('cam-grid-test', Host::TYPE_SWITCH);
        $vlan = new Vlan();
        $vlan->tag = 1;
        $camTable = array(
            array('port' => 1, 'vlan_tag' => 1, 'mac' => 'aa:bb:cc:dd:ee:ff', 'host' => null,
                'host_dst' => null, 'port_name' => 'Gi0/1', 'vlan' => $vlan),
        );

        $controller = new HostController('host');
        $controller->setAction(new CInlineAction($controller, 'camTable'));
        Yii::app()->setController($controller);
        $html = $controller->renderPartial('/host/_camTable', array('model' => $switch, 'cam_table' => $camTable), true);

        $this->assertStringContainsString('aa:bb:cc:dd:ee:ff', $html);
        $this->assertStringContainsString('Gi0/1', $html);
    }

    public function testArpTableGridRendersRows()
    {
        $host = $this->saveHost('arp-grid-test', Host::TYPE_ROUTER);
        $arpTable = array(
            array('mac' => 'aa:bb:cc:dd:ee:ff', 'ip' => '10.0.0.9', 'host' => null),
        );

        $controller = new HostController('host');
        $controller->setAction(new CInlineAction($controller, 'arpTable'));
        Yii::app()->setController($controller);
        $html = $controller->renderPartial('/host/_arpTable', array('model' => $host, 'arp_table' => $arpTable), true);

        $this->assertStringContainsString('10.0.0.9', $html);
    }
}
