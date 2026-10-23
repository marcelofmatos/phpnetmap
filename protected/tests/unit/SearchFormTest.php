<?php

use PHPUnit\Framework\TestCase;

class SearchFormTest extends TestCase
{
    const IP = '127.0.0.66'; // no agent here: unseeded walks just time out fast

    private $templateId;
    private $hostId;
    private $previousCache;

    protected function setUp(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }, E_ALL);
        // PNMSnmp::walk() serves results from APCu when params['cache'] is
        // on — the tests seed it with walk results instead of a live agent.
        $this->previousCache = Yii::app()->params['cache'];
        Yii::app()->params['cache'] = true;
        apcu_clear_cache();

        $template = new SnmpTemplate;
        $template->name = 'search-form-test';
        $template->version = '2c';
        $template->community = 'public';
        $template->timeout = 10000;
        $template->retries = 0;
        $this->assertTrue($template->save(false));
        $this->templateId = $template->id;

        $host = new Host();
        $host->name = 'search-form-test-switch';
        $host->ip = self::IP;
        $host->type = Host::TYPE_SWITCH;
        $host->snmp_template_id = $template->id;
        $this->assertTrue($host->save(false));
        $this->hostId = $host->id;
    }

    protected function tearDown(): void
    {
        restore_error_handler();
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

    private function search($type, $query)
    {
        $model = new SearchForm;
        $model->type = $type;
        $model->hosts = array($this->hostId);
        $model->query = $query;
        $model->exact_match = 0;
        $model->exclude_link_ports = 0;
        return $model->searchResult();
    }

    // A CAM row whose VLAN isn't registered got vlan = null, and the result
    // grid read ->tag off it (notice on PHP 7, warning on PHP 8). Falls back
    // to an unsaved Vlan carrying the tag, as host/camTable does.
    public function testCamTableResultWithUnregisteredVlanCarriesUnsavedVlan()
    {
        self::seedWalk('.1.3.6.1.2.1.17.1.4.1.2', array());
        self::seedWalk('.1.3.6.1.2.1.17.7.1.2.2.1.2', array(
            '.1.3.6.1.2.1.17.7.1.2.2.1.2.4093.170.187.204.221.238.255' => 'INTEGER: 5',
        ));

        $result = $this->search('camtable:mac', 'aa:bb');

        $this->assertCount(1, $result);
        $this->assertInstanceOf('Vlan', $result[0]['vlan']);
        $this->assertTrue($result[0]['vlan']->isNewRecord);
        $this->assertEquals(4093, $result[0]['vlan']->tag);
    }

    // BRIDGE-MIB fallback without a PVID for the port leaves vlan_tag null;
    // a non-exact search passed it to strpos() (deprecated on PHP 8.1+).
    public function testCamTableVlanTagSearchToleratesNullVlanTag()
    {
        self::seedWalk('.1.3.6.1.2.1.17.1.4.1.2', array());
        self::seedWalk('.1.3.6.1.2.1.17.7.1.2.2.1.2', array());
        self::seedWalk('.1.3.6.1.2.1.17.4.3.1.2', array(
            '.1.3.6.1.2.1.17.4.3.1.2.170.187.204.221.238.255' => 'INTEGER: 5',
        ));
        self::seedWalk('.1.3.6.1.2.1.17.7.1.4.5.1.1', array());
        self::seedWalk('.1.3.6.1.4.1.1916.1.2.1.2.1.10', array());

        $this->assertSame(array(), $this->search('camtable:vlan_tag', '1'));
    }

    // An empty ifAlias ("") parses to null; a non-exact search passed it to
    // strpos() (deprecated on PHP 8.1+).
    public function testPortsInfoSearchToleratesEmptyIfAlias()
    {
        self::seedWalk('.1.3.6.1.2.1.31.1.1.1.18', array(
            '.1.3.6.1.2.1.31.1.1.1.18.1' => '""',
            '.1.3.6.1.2.1.31.1.1.1.18.2' => 'STRING: "uplink to core"',
        ));

        $result = $this->search('portsinfo:ifAlias', 'core');

        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]['port']);
        $this->assertSame('uplink to core', $result[0]['info']);
    }
}
