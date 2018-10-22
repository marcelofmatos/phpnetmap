<?php

class MapController extends Controller {
    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
            return array(
                    array('allow',  // allow all users to perform 'index' and 'view' actions
                            'actions'=>array('index','view'),
                            'users'=>array('*'),
                    ),
                    array('allow', // allow authenticated user to perform 'create' and 'update' actions
                            'actions'=>array('create','update'),
                            'users'=>array('@'),
                    ),
                    array('allow', // allow admin user to perform 'admin' and 'delete' actions
                            'actions'=>array('admin','delete'),
                            'users'=>array('admin'),
                    ),
//			array('deny',  // deny all users
//				'users'=>array('*'),
//			),
            );
    }
        
    public function actionIndex() {
        $this->render('index');
    }

    public function actionListHosts() {

        $this->layout = '//layouts/json';

        try {

            if (isset($_GET['hostId'])) {

                $hostId = (int) $_GET['hostId'];

                // get root host
                // 
                $rootHost = $hosts[] = Host::model()->findbyPk($hostId);

                // get connections
                $hostConnections = $rootHost->getConnections();

                // get host from connections
                foreach ($hostConnections as $hosts_conn) {
                    array_push($hosts, $hosts_conn->hostDst);
                }


                // in switches, get hosts by CAM table
                if ($rootHost->type == Host::TYPE_SWITCH) {

                    $rootHost->loadCamTable();
                    
                    // TODO: associar roteadores aos switches para pegar a 
                    // tabela ARP dos gateways da rede
                    // 
                    $gateway = Host::model()->findByPk(Yii::app()->params['hostGatewayId']);
//                    $arpTable = array();

                    foreach ($rootHost->cam_table as $k => $camHost) {

                        // se existir conexao cadastrada para a porta, ignore o host
                        // TODO: usar tipo do link==backbone
                        $hostOnPort = $rootHost->getHostOnPort($camHost['port']);

                        if ( $hostOnPort instanceof Host && $hostOnPort->type != Host::TYPE_UNKNOWN) {
                            continue;
                        }

                        // havendo 2 ou + hosts numa porta sem conexao cadastrada, criar 'hub virtual'
                        $port = $camHost['port'];
                        $portLabelPrefix = 'port_';

                        if ($port == $rootHost->cam_table[$k + 1]['port'] || $sourcePort == $portLabelPrefix . $port) {

                            $sourcePort = $portLabelPrefix . $port;
                            
                            if (!isset($virtualHost[$sourcePort])) {
                                $virtualHost[$sourcePort] = new Host();
                                $virtualHost[$sourcePort]->name = $sourcePort;
                                $virtualHost[$sourcePort]->type = Host::TYPE_SUPPOSED_HUB;
                                array_push($hosts, $virtualHost[$sourcePort]);

                                $virtualConn[$sourcePort] = new Connection();
                                $virtualConn[$sourcePort]->hostSrc = $rootHost;
                                $virtualConn[$sourcePort]->hostDst = $virtualHost[$sourcePort];
                                $virtualConn[$sourcePort]->host_src_port = $camHost['port'];
                                $virtualConn[$sourcePort]->type = Connection::TYPE_SUPPOSED_LINK;
                                // TODO: especificar tipo do link
                                array_push($hostConnections, $virtualConn[$sourcePort]);
                            }
                            
                        } else {
                            unset($sourcePort);
                        }

                        // host
                        $host = Host::model()->findByAttributes(array('mac' => $camHost['mac']));
                        if($host instanceof Host){
                            $h = $host;
                        } else {
                            $h = new Host();
                            $h->mac = $camHost['mac'];
                            $h->setTypeByMAC();
                        }
                        $h->ip = ($gateway instanceof Host) ? $gateway->getIpInArpTable($camHost['mac']) : $h->ip;
                        $h->name = ($h->ip) ? $h->ip : $h->mac;
                        array_push($hosts, $h);

                        // connection
                        $c = new Connection();
                        $c->hostSrc = ($virtualHost[$sourcePort]) ? $virtualHost[$sourcePort] : $rootHost;
                        $c->hostDst = $h;
                        $c->host_src_port = $camHost['port'];
                        $c->type = Connection::TYPE_UNKNOWN;
                        
                        // vlan
                        $c->vlan = Vlan::model()->findByAttributes(array('tag' => ($camHost['vlan_tag'])));
                        if ( ! $c->vlan instanceof Vlan) {
                            $c->vlan = new Vlan();
                            $c->vlan->tag = $camHost['vlan_tag'];
                        }
                        
                        array_push($hostConnections, $c);
                    }
                }
            } else {
                $hosts = Host::model()->findAll(array('order'=>'id'));
                $hostConnections = Connection::model()->findAll();
            }

            $this->render('listHosts', array(
                'hosts' => $hosts,
                'hostConnections' => $hostConnections,
                )
            );
            
        } catch (Exception $exc) {
            $this->render('error', array(
                'error' => "listHosts error:". $exc->getMessage(),
                )
            );
        }
    }

}