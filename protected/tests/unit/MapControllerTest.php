<?php

use PHPUnit\Framework\TestCase;

class MapControllerTest extends TestCase
{
    const IP = '127.0.0.67'; // no agent here: unseeded walks just time out fast

    private $templateId;
    private $hostId;
    private $previousCache;

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
        // PNMSnmp::walk() serves results from APCu when params['cache'] is
        // on — the test seeds the switch's CAM table there.
        $this->previousCache = Yii::app()->params['cache'];
        Yii::app()->params['cache'] = true;
        apcu_clear_cache();

        $template = new SnmpTemplate;
        $template->name = 'map-controller-test';
        $template->version = '2c';
        $template->community = 'public';
        $template->timeout = 10000;
        $template->retries = 0;
        $this->assertTrue($template->save(false));
        $this->templateId = $template->id;

        $host = new Host();
        $host->name = 'map-controller-test-switch';
        $host->ip = self::IP;
        $host->type = Host::TYPE_SWITCH;
        $host->snmp_template_id = $template->id;
        $this->assertTrue($host->save(false));
        $this->hostId = $host->id;
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        unset($_GET['hostId']);
        apcu_clear_cache();
        if ($this->previousCache === null) {
            Yii::app()->params->remove('cache');
        } else {
            Yii::app()->params['cache'] = $this->previousCache;
        }
        if ($this->hostId) {
            Yii::app()->db->createCommand("DELETE FROM host WHERE id = {$this->hostId}")->execute();
        }
        if ($this->templateId) {
            Yii::app()->db->createCommand("DELETE FROM snmp_template WHERE id = {$this->templateId}")->execute();
        }
    }

    private static function seedWalk($oid, $rows)
    {
        apcu_store('PNM_' . str_replace('.', '_', self::IP . $oid), $rows, 60);
    }

    // Walking the switch's CAM table read the next row past the last one and
    // the not-yet-set $sourcePort/$virtualHost (notices on PHP 7, warnings
    // on PHP 8 — the map of any switch with CAM entries failed). Port 1 has a
    // single MAC (linked straight to the switch); port 2 has two, which the
    // map groups behind a virtual hub.
    public function testListHostsForSwitchGroupsSharedPortBehindVirtualHub()
    {
        self::seedWalk('.1.3.6.1.2.1.17.1.4.1.2', array());
        self::seedWalk('.1.3.6.1.2.1.17.7.1.2.2.1.2', array(
            '.1.3.6.1.2.1.17.7.1.2.2.1.2.1.10.11.12.13.14.15' => 'INTEGER: 1',
            '.1.3.6.1.2.1.17.7.1.2.2.1.2.1.170.187.204.221.238.255' => 'INTEGER: 2',
            '.1.3.6.1.2.1.17.7.1.2.2.1.2.1.222.173.190.239.0.1' => 'INTEGER: 2',
        ));
        $_GET['hostId'] = $this->hostId;

        $controller = new class('map') extends MapController {
            public $rendered;

            public function render($view, $data = null, $return = false)
            {
                $this->rendered = array($view, $data);
            }
        };
        $controller->actionListHosts();

        $this->assertSame('listHosts', $controller->rendered[0]);
        $srcByDst = array();
        foreach ($controller->rendered[1]['hostConnections'] as $connection) {
            $srcByDst[$connection->hostDst->name] = $connection->hostSrc->name;
        }
        $this->assertSame(array(
            '0a:0b:0c:0d:0e:0f' => 'map-controller-test-switch',
            'port_2' => 'map-controller-test-switch',
            'aa:bb:cc:dd:ee:ff' => 'port_2',
            'de:ad:be:ef:00:01' => 'port_2',
        ), $srcByDst);
    }
}
