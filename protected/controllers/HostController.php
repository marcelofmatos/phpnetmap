<?php

class HostController extends Controller {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow authenticated user to perform 'index', 'view', 'create', 'update' and telemetry actions
                'actions' => array('index', 'view', 'create', 'update', 'viewByName', 'loadPortStatus', 'loadPortTraffic', 'loadPortInfo', 'loadSystemInfo', 'camTable', 'arpTable', 'traffic', 'connections'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete', 'fillMacFromArp', 'setSNMP'),
                'users' => array('admin'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $model = $this->loadModel($id);
        $this->menu = $this->buildOperationsMenu($model);
        $this->render('view', array(
            'model' => $model,
        ));
    }

    /**
     * Displays a particular model.
     * @param string $name the Name of the model to be displayed
     */
    public function actionViewByName($name = null, $ip = null, $mac = null) {
        try {
            $model = $this->loadModelByName($name, $ip, $mac);
            $this->menu = $this->buildOperationsMenu($model);
            $this->render('view', array(
                'model' => $model,
            ));
        } catch (CHttpException $e) {

            $params = array();
            if (!is_null($name))
                $params['name'] = trim($name);
            if (!is_null($ip))
                $params['ip'] = trim($ip);
            if (!is_null($mac))
                $params['mac'] = trim($mac);

            // Completa o quanto der pra descobrir via SNMP, pra pré-preencher
            // o botão "Create Host" desta página com mais do que só o que já
            // veio na URL (ex.: um clique no mapa só tinha o mac — dá pra
            // chutar o tipo pelo prefixo do mac e, se faltar, completar o ip
            // pela tabela ARP do gateway).
            if (!empty($mac)) {
                $macGuess = new Host();
                $macGuess->mac = trim($mac);
                $macGuess->setTypeByMAC();
                $params['type'] = $macGuess->type;

                if (empty($ip)) {
                    $gateway = Host::model()->findByPk(Yii::app()->params['hostGatewayId']);
                    $guessedIp = ($gateway instanceof Host) ? $gateway->getIpInArpTable(trim($mac)) : null;
                    if ($guessedIp) {
                        $params['ip'] = $guessedIp;
                    }
                }
            }

            $this->render('addHostNotFound', $params);
        }
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate($name = null, $ip = null, $mac = null, $type = null, $snmp_template_id = null) {
        $model = new Host;

        /* Set attributes by _GET */
        if (!is_null($name) && !isset($_POST['Host'])) {
            $model->name = (string) trim($name);
        }
        if (!is_null($ip) && !isset($_POST['Host'])) {
            $model->ip = (string) trim($ip);
        }
        if (!is_null($mac) && !isset($_POST['Host'])) {
            $model->mac = (string) trim($mac);
        }
        if (!is_null($type) && !isset($_POST['Host'])) {
            $model->type = (string) trim($type);
        }
        if (!is_null($snmp_template_id) && !isset($_POST['Host'])) {
            $model->snmp_template_id = (int) $snmp_template_id;
        }

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Host'])) {
            $model->attributes = $_POST['Host'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->id));
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Host'])) {
            $model->attributes = $_POST['Host'];
            if ($model->save())
                $this->redirect(array('viewByName', 'name' => $model->name));
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Host');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new Host('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Host']))
            $model->attributes = $_GET['Host'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Preenche o mac dos hosts que já têm ip mas não têm mac, buscando na
     * tabela ARP do gateway configurado (Configuration > Gateway). Mostra
     * uma prévia do que seria alterado antes de gravar — só grava de fato
     * quando o formulário de confirmação é enviado (POST).
     */
    public function actionFillMacFromArp() {
        $gateway = Host::model()->findByPk(Yii::app()->params['hostGatewayId']);

        if (!$gateway instanceof Host || !$gateway->snmp_template_id) {
            $this->render('fillMacFromArpNoGateway');
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->addCondition("(ip IS NOT NULL AND ip <> '') AND (mac IS NULL OR mac = '')");
        $candidates = Host::model()->findAll($criteria);

        $matches = array();
        $unmatched = array();
        foreach ($candidates as $host) {
            $mac = $gateway->getMacInArpTable($host->ip);
            if ($mac) {
                $matches[] = array('host' => $host, 'mac' => $mac);
            } else {
                $unmatched[] = $host;
            }
        }

        if (Yii::app()->request->isPostRequest && isset($_POST['confirm'])) {
            $updated = 0;
            foreach ($matches as $match) {
                $match['host']->mac = $match['mac'];
                if ($match['host']->save()) {
                    $updated++;
                }
            }
            $this->render('fillMacFromArpDone', array(
                'updated' => $updated,
                'total' => count($matches),
            ));
            return;
        }

        $this->render('fillMacFromArp', array(
            'gateway' => $gateway,
            'matches' => $matches,
            'unmatched' => $unmatched,
        ));
    }

    /**
     * Itens da sidebar "Operations" (layout column2) pra host/view — mesmas
     * ações que estavam no dropdown "Actions" do cabeçalho fixo (ver
     * host/_header.php), só que no padrão de menu das outras views.
     * @param Host $model
     * @return array
     */
    protected function buildOperationsMenu($model) {
        return array(
            array('label' => 'Web Config', 'url' => 'http://' . $model->ip, 'linkOptions' => array('target' => '_blank')),
            array('label' => 'Update Host', 'url' => array('update', 'id' => $model->id)),
            '<li class="divider"></li>',
            array('label' => 'Delete Host', 'url' => '#', 'linkOptions' => array(
                'submit' => array('delete', 'id' => $model->id),
                'confirm' => 'Are you sure you want to delete this item?',
            )),
        );
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Host the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = Host::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Returns the data model based on the name given in the GET variable.
     * If the data model is not found, will show screen for create a new model for name. 
     * Otherwise, an HTTP exception will be raised.
     * @param string $name the Name of the model to be loaded
     * @return Host the loaded model
     * @throws CHttpException
     */
    public function loadModelByName($name, $ip = null, $mac = null) {

        $model = Host::model()->findByAttributes(array('name' => $name));

        // Tenta ip e mac de forma independente (não mais exclusiva): um link
        // pode trazer os dois, e um host que só bate pelo mac não pode ser
        // ignorado só porque o ip também veio preenchido e não bateu.
        if ($model == null && !empty($ip)) {
            $model = Host::model()->findByAttributes(array('ip' => $ip));
        }
        if ($model == null && !empty($mac)) {
            $model = Host::model()->findByAttributes(array('mac' => $mac));
        }

        if ($model == null) {
            throw new CHttpException(404, 'Model name ' . $name . ' does not exist.');
        }

        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Host $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'host-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
     * Show Interface Status in JSON format
     */
    public function actionLoadPortStatus($id) {
        $this->layout = '//layouts/json';

        try {
            if (!is_null($id)) {

                $model = $this->loadModel($id);

                $model->loadPortsInfo(array('ifOperStatus', 'ifAdminStatus', 'dot1dStpPortState'));
            }
            $this->render('jsonPortsStatus', array(
                'model' => $model,
                    )
            );
        } catch (Exception $exc) {
            $this->render('jsonError', array(
                'error' => $exc->getMessage(),
                    )
            );
        }
    }

    /**
     * Show Interface Status in JSON format
     */
    public function actionLoadPortTraffic($id) {
        $this->layout = '//layouts/json';

        try {
            if (!is_null($id)) {

                $model = $this->loadModel($id);

                // ifSpeed é um Gauge32 de 32 bits em bps — satura em
                // ~4.29 Gbps (o equipamento reporta o valor máximo como
                // sentinela), então mostra escala errada pra porta de 10G/
                // 40G/100G. ifHighSpeed (Mbps) não tem esse limite. Os
                // contadores de octeto também têm a mesma limitação — os de
                // 32 bits (ifInOctets/ifOutOctets) dão a volta em poucos
                // segundos numa porta rápida saturada; os HC (64 bits)
                // praticamente nunca dão. Carrega os dois pares — o cliente
                // (_traffic.php) prefere o de 64 bits/ifHighSpeed quando
                // disponível, com fallback pro clássico pra equipamento sem
                // ifXTable.
                //
                // cacheTtl=0 (sem cache): esses valores alimentam um cálculo
                // de taxa no cliente (bytes/segundo entre duas leituras) e o
                // timestamp que ele usa é sempre "agora" — servir uma
                // leitura de até alguns segundos atrás com timestamp de
                // agora faz a taxa calculada ficar errada (trava em zero
                // enquanto serve do cache, depois pula pra um valor inflado
                // quando a leitura fresca chega). O loadPortsInfo() sempre
                // cacheou por 2s (desde o commit inicial em 2016) sem essa
                // ressalva — endurece esse ponto por precaução (ex.: mais de
                // uma aba/pessoa vendo o tráfego do mesmo host ao mesmo
                // tempo), mesmo não sendo a causa do problema investigado
                // no host "sirius" (esse foi corrigido em _traffic.php).
                $model->loadPortsInfo(array('ifInOctets', 'ifOutOctets', 'ifHCInOctets', 'ifHCOutOctets', 'ifSpeed', 'ifHighSpeed'), 0);
            }
            $this->render('jsonPortsTraffic', array(
                'model' => $model,
                    )
            );
        } catch (Exception $exc) {
            $this->render('jsonError', array(
                'error' => $exc->getMessage(),
                    )
            );
        }
    }

    /**
     * Show Port Information
     */
    public function actionLoadPortInfo($id, $includeNeighbors = false) {
        $this->layout = '//layouts/json';

        try {
            if (!is_null($id)) {

                $model = $this->loadModel($id);

                $model->loadPortsInfo(array('ifDescr', 'ifAlias'));

                // Pra porta sem Connection formal (hasConnection), mostra os
                // hosts aprendidos pela tabela CAM nessa porta agora —
                // cadastrados ou não — mesmo padrão usado nas tabelas
                // CAM/ARP, aqui no painel "Link to:" da porta e no form de
                // Connection (conferir quem está na porta antes de criar o
                // link). Só quando pedido explicitamente ($includeNeighbors)
                // — essa mesma action é compartilhada com o combo de porta
                // do Connection, o editor de Host Face e o gráfico de
                // tráfego, que na maioria das vezes só precisam de
                // ifDescr/ifAlias e não devem pagar o custo de outro walk
                // SNMP completo pela tabela CAM.
                if ($includeNeighbors) {
                    $model->loadCamTable();
                    $gateway = Host::model()->findByPk(Yii::app()->params['hostGatewayId']);
                    foreach ($model->ports as $portIndex => &$port) {
                        if (!empty($port['hasConnection'])) {
                            continue;
                        }
                        $discoveredHosts = array();
                        foreach ($model->cam_table as $ctItem) {
                            if ($ctItem['port'] != $portIndex || empty($ctItem['mac'])) {
                                continue;
                            }
                            $ctHost = Host::model()->findByAttributes(array('mac' => $ctItem['mac']));
                            $discoveredHosts[] = array(
                                'vlanTag' => $ctItem['vlan_tag'],
                                'mac' => $ctItem['mac'],
                                'ip' => ($ctHost instanceof Host) ? $ctHost->ip : (($gateway instanceof Host) ? $gateway->getIpInArpTable($ctItem['mac']) : null),
                                'host' => ($ctHost instanceof Host) ? array('id' => $ctHost->id, 'name' => $ctHost->name, 'type' => $ctHost->type) : null,
                            );
                        }
                        if ($discoveredHosts) {
                            $port['discoveredHosts'] = $discoveredHosts;
                        }
                    }
                    unset($port);
                }
            }
            $this->render('jsonPortsInfo', array(
                'model' => $model,
                    )
            );
        } catch (Exception $exc) {
            $this->render('jsonError', array(
                'error' => $exc->getMessage(),
                    )
            );
        }
    }

    /**
     * Returns sysDescr/sysName/ip for a host via SNMP, as a reference for
     * the operator (e.g. to look up a product photo) — not persisted.
     */
    public function actionLoadSystemInfo($id) {
        $this->layout = '//layouts/json';

        try {
            $model = $this->loadModel($id);
            $model->loadSystemInfo();
            $this->render('jsonSystemInfo', array(
                'model' => $model,
                    )
            );
        } catch (Exception $exc) {
            $this->render('jsonError', array(
                'error' => $exc->getMessage(),
                    )
            );
        }
    }

    /**
     * Displays connections of Host.
     */
    public function actionCamTable() {
        try {
            if (isset($_GET['id'])) {
                $model = $this->loadModel((int) $_GET['id']);
            } else if (isset($_GET['name'])) {
                $model = $this->loadModelByName((string) $_GET['name']);
            }

            $model->loadCamTable();
            // Nome da interface (ex.: "GigabitEthernet0/0/1") pra exibir na
            // coluna "port" da tabela CAM em vez do ifIndex cru — mesma
            // fonte (ifDescr por ifIndex) já usada na Host Face.
            $model->loadPortsInfo(array('ifDescr'));

            // TODO: associar roteadores aos switches para pegar a
            // tabela ARP dos gateways da rede
            //
            $gateway = Host::model()->findByPk(Yii::app()->params['hostGatewayId']);

            $cam_table = array();

            foreach ($model->cam_table as $ctItem) {
                $mac = $ctItem['mac'];
                $ctItem['host'] = ($ctItem['mac']) ? Host::model()->findByAttributes(array('mac' => $mac)) : null;
                if (!$ctItem['host'] instanceof Host) {
                    $ip = ($gateway instanceof Host) ? $gateway->getIpInArpTable($mac) : null;
                    if ($ip) {
                        $ctItem['host'] = new Host();
                        $ctItem['host']->mac = $mac;
                        $ctItem['host']->ip = $ip;
                        $ctItem['host']->name = ($ip) ? $ip : $mac;
                    }
                }
                $ctItem['host_dst'] = ($ctItem['host'] instanceof Host) ? $model->getHostOnPort($ctItem['port']) : null;
                $ctItem['port_name'] = isset($model->ports[$ctItem['port']]['ifDescr']) ? $model->ports[$ctItem['port']]['ifDescr'] : null;
                $ctItem['vlan'] = Vlan::model()->findByAttributes(array('tag' => $ctItem['vlan_tag']));
                if (!$ctItem['vlan'] instanceof Vlan) {
                    $ctItem['vlan'] = new Vlan();
                    $ctItem['vlan']->tag = $ctItem['vlan_tag'];
                }
                $cam_table[] = $ctItem;
            }

            $this->render('camTable', array(
                'model' => $model,
                'cam_table' => $cam_table,
            ));
        } catch (CHttpException $e) {
            if (isset($_GET['name'])) {
                $name = (string) trim($_GET['name']);
                $this->render('addHostNotFound', array('name' => $name));
            } else {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }
    }

    /**
     * Displays connections of Host.
     */
    public function actionArpTable() {
        try {
            
            if (isset($_GET['id'])) {
                $model = $this->loadModel((int) $_GET['id']);
            } else if (isset($_GET['name'])) {
                $model = $this->loadModelByName((string) $_GET['name']);
            }

            $model->loadArpTable();

            foreach ($model->arp_table as $mac => $ip) {
                $atItem['mac'] = $mac;
                $atItem['ip'] = $ip;
                $atItem['host'] = ($mac) ? Host::model()->findByAttributes(array('mac' => $mac)) : null;
                $arp_table[] = $atItem;
            }

            $this->render('arpTable', array(
                'model' => $model,
                'arp_table' => $arp_table,
            ));
        } catch (CHttpException $e) {
            if (isset($_GET['name'])) {
                $name = (string) trim($_GET['name']);
                $this->render('addHostNotFound', array('name' => $name));
            } else {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }
    }

    /**
     * Displays traffic of Host.
     */
    public function actionTraffic() {
        try {
            if (isset($_GET['id'])) {
                $model = $this->loadModel((int) $_GET['id']);
            } else if (isset($_GET['name'])) {
                $model = $this->loadModelByName((string) $_GET['name']);
            }

            $this->render('traffic', array(
                'model' => $model,
            ));
        } catch (CHttpException $e) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
    }

    /**
     * Displays connections Table of Host.
     */
    public function actionConnections() {
        try {
            if (isset($_GET['id'])) {
                $model = $this->loadModel((int) $_GET['id']);
            } else if (isset($_GET['name'])) {
                $model = $this->loadModelByName((string) $_GET['name']);
            }

            $this->render('connections', array(
                'model' => $model,
            ));
        } catch (CHttpException $e) {
            if (isset($_GET['name'])) {
                $name = (string) trim($_GET['name']);
                $this->render('addHostNotFound', array('name' => $name));
            } else {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }
    }

    /**
     * change SNMP value of Host
     */
    public function actionSetSNMP() {
        try {
            $name = $_POST['name'];

            switch ($_POST['key']) {
                case "ifAlias":
                    $type = 's';
                    break;
                default:
                    $type = 'i';
            }

            $model = $this->loadModelByName($name);

            if ($model) {
                $key = PNMSnmp::getOid($_POST['key']);
                $index = (int) $_POST['index'];
                $value = $_POST['value'];
                $oid = $key . '.' . $index;
                $result = $model->setSNMPValue($oid, $type, $value);
            }

            $this->layout = '//layouts/json';

            $this->render('jsonSetStatus', array(
                'result' => $result,
                    )
            );
            
        } catch (Exception $exc) {
            $this->render('jsonError', array(
                'error' => "actionSetSNMP error:". $exc->getMessage(),
                )
            );
        }

    }

}
