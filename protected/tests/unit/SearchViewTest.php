<?php

use PHPUnit\Framework\TestCase;

class SearchViewTest extends TestCase
{
    private $hostId;

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
        self::useTempAssetDir();
        // Grid sort links and forms build URLs from the current request.
        $_SERVER['REQUEST_URI'] = '/search/index';
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        unset($_SERVER['REQUEST_URI']);
        Yii::app()->setController(null);
        if ($this->hostId) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$this->hostId}")->execute();
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
