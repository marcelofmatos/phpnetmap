<?php

use PHPUnit\Framework\TestCase;

class SearchViewTest extends TestCase
{
    private $hostId;
    private $savedWebState;
    private $assetDir;

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
        $this->useTempWebState('/search/index');
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        $this->restoreWebState();
        if ($this->hostId) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$this->hostId}")->execute();
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

    private function render($type, $result)
    {
        $model = new SearchForm;
        $model->type = $type;
        $controller = new SearchController('search');
        $controller->setAction(new CInlineAction($controller, 'index'));
        Yii::app()->setController($controller); // CForm renders through the app's current controller
        $form = new CForm('application.views.search.form', $model);
        return $controller->renderPartial('index', array('form' => $form, 'searchModel' => $model, 'result' => $result), true);
    }

    // CAM search rows have no 'ip' key, which the "Create Host" button URL
    // read (notice on PHP 7, warning on PHP 8 -> HTTP 500 on search/index).
    public function testCamTableResultWithUnknownMacRendersWithoutWarning()
    {
        $switch = new Host();
        $switch->name = 'search-view-test';
        $switch->type = Host::TYPE_SWITCH;
        $this->assertTrue($switch->save(false));
        $this->hostId = $switch->id;
        $vlan = new Vlan();
        $vlan->tag = 1;

        $html = $this->render('camtable:mac', array(
            array('port' => 1, 'vlan_tag' => 1, 'mac' => 'aa:bb:cc:dd:ee:ff', 'host' => $switch, 'hostDst' => null, 'vlan' => $vlan),
        ));

        $this->assertStringContainsString('aa:bb:cc:dd:ee:ff', $html);
    }

    public function testArpTableResultRendersWithoutWarning()
    {
        $router = new Host();
        $router->name = 'search-view-test';
        $router->type = Host::TYPE_ROUTER;

        $html = $this->render('arptable:ip', array(
            array('mac' => 'aa:bb:cc:dd:ee:ff', 'ip' => '10.0.0.9', 'host' => $router, 'hostDst' => null),
        ));

        $this->assertStringContainsString('10.0.0.9', $html);
    }
}
