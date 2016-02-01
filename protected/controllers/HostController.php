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
            array('allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array('index', 'view'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('create', 'update'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete'),
                'users' => array('admin'),
            ),
//			array('deny',  // deny all users
//				'users'=>array('*'),
//			),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $this->render('view', array(
            'model' => $this->loadModel($id),
        ));
    }

    /**
     * Displays a particular model.
     * @param string $name the Name of the model to be displayed
     */
    public function actionViewByName($name = null, $ip = null, $mac = null) {
        try {
            $this->render('view', array(
                'model' => $this->loadModelByName($name, $ip, $mac),
            ));
        } catch (CHttpException $e) {

            if (!is_null($name))
                $params['name'] = trim($name);
            if (!is_null($ip))
                $params['ip'] = trim($ip);
            if (!is_null($mac))
                $params['mac'] = trim($mac);

            $this->render('addHostNotFound', $params);
        }
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate($name = null, $ip = null, $mac = null) {
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
                $this->redirect(array('view', 'id' => $model->id));
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
        if ($model === null)
            $model = Host::model()->findByAttributes(array('ip' => $ip));
        if ($model === null)
            $model = Host::model()->findByAttributes(array('mac' => $mac));

        if ($model === null)
            throw new CHttpException(404, 'Model name ' . $name . ' does not exist.');

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

//                $model->loadPortsInfo(array('ifHCInOctets', 'ifHCOutOctets', 'ifSpeed'));
                $model->loadPortsInfo(array('ifInOctets', 'ifOutOctets', 'ifSpeed'));
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
    public function actionLoadPortInfo($id) {
        $this->layout = '//layouts/json';

        try {
            if (!is_null($id)) {

                $model = $this->loadModel($id);

                $model->loadPortsInfo(array('ifDescr', 'ifAlias'));
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
