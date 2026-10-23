<?php

use PHPUnit\Framework\TestCase;

class HostTableViewsTest extends TestCase
{
    private $hostIds = array();
    private $savedWebState;
    private $assetDir;

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
        $this->useTempWebState('/host/camTable');
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        $this->restoreWebState();
        foreach ($this->hostIds as $id) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$id}")->execute();
        }
    }

    // Grid widgets publish assets next to the entry script (vendor/bin under
    // PHPUnit) and build sort links from the request URI — point both at
    // test values, keeping the previous state to put back afterwards.
    private function useTempWebState($requestUri)
    {
        $am = Yii::app()->assetManager;
        $request = Yii::app()->request;
        $this->savedWebState = array(
            'server' => array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : null,
            'assets' => Closure::bind(function () { return array($this->_basePath, $this->_baseUrl, $this->_published); }, $am, 'CAssetManager')(),
            'request' => Closure::bind(function () { return $this->_requestUri; }, $request, 'CHttpRequest')(),
        );
        $this->assetDir = sys_get_temp_dir() . '/pnm-test-assets-' . uniqid();
        mkdir($this->assetDir);
        $am->basePath = $this->assetDir;
        $am->baseUrl = '/assets';
        $_SERVER['REQUEST_URI'] = $requestUri;
    }

    private function restoreWebState()
    {
        $state = $this->savedWebState;
        Closure::bind(function ($s) { list($this->_basePath, $this->_baseUrl, $this->_published) = $s; }, Yii::app()->assetManager, 'CAssetManager')($state['assets']);
        Closure::bind(function ($s) { $this->_requestUri = $s; }, Yii::app()->request, 'CHttpRequest')($state['request']);
        if ($state['server'] === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $state['server'];
        }
        Yii::app()->setController(null);
        CFileHelper::removeDirectory($this->assetDir);
    }

    private function renderPartial($view, $action, $data)
    {
        $controller = new HostController('host');
        $controller->setAction(new CInlineAction($controller, $action));
        Yii::app()->setController($controller);
        return $controller->renderPartial($view, $data, true);
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

        $html = $this->renderPartial('/host/_camTable', 'camTable', array('model' => $switch, 'cam_table' => $camTable));

        $this->assertStringContainsString('aa:bb:cc:dd:ee:ff', $html);
        $this->assertStringContainsString('Gi0/1', $html);
    }

    // Same missing-'id' keyField read as above, on the ARP grid.
    public function testArpTableGridRendersRows()
    {
        $host = $this->saveHost('arp-grid-test', Host::TYPE_ROUTER);
        $arpTable = array(
            array('mac' => 'aa:bb:cc:dd:ee:ff', 'ip' => '10.0.0.9', 'host' => null),
        );

        $html = $this->renderPartial('/host/_arpTable', 'arpTable', array('model' => $host, 'arp_table' => $arpTable));

        $this->assertStringContainsString('10.0.0.9', $html);
    }
}
