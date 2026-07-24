<?php

class HostFaceController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
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
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update','fetchImage'),
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

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new HostFace;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['HostFace']))
		{
			$model->attributes=$_POST['HostFace'];
			if($model->save()) {
                                $this->setHostFaceOnHosts($model,$_POST['HostFace']['hosts']);
				$this->redirect(array('view','id'=>$model->id));
                        }
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['HostFace']))
		{
			$model->attributes=$_POST['HostFace'];
			if($model->save()) {
                                $this->setHostFaceOnHosts($model,$_POST['HostFace']['hosts']);
				$this->redirect(array('view','id'=>$model->id));
                        }
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Baixa uma imagem a partir de uma URL informada pelo usuário e devolve
	 * como data URI base64 — feito no servidor (não no navegador) porque o
	 * navegador bloquearia por CORS ao tentar isso direto num domínio de
	 * terceiro (ex.: site do fabricante do switch).
	 */
	public function actionFetchImage()
	{
		$this->layout = '//layouts/json';

		$url = isset($_GET['url']) ? trim($_GET['url']) : '';

		if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			$this->render('jsonError', array('error' => 'URL inválida.'));
			return;
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);
		if ($scheme !== 'http' && $scheme !== 'https') {
			$this->render('jsonError', array('error' => 'Só URLs http/https são permitidas.'));
			return;
		}

		// Proteção básica contra SSRF: recusa endereços privados/reservados
		// (ex.: 127.0.0.1, 10.0.0.0/8, 169.254.169.254 — metadata endpoint
		// de provedores de nuvem) antes de fazer a requisição.
		$host = parse_url($url, PHP_URL_HOST);
		$ip = $host ? gethostbyname($host) : false;
		if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			$this->render('jsonError', array('error' => 'Essa URL aponta para um endereço não permitido.'));
			return;
		}

		// follow_location desligado de propósito: seguir redirect faria a
		// checagem de IP acima (feita só na URL original) não valer pro
		// destino final, reabrindo a mesma brecha de SSRF.
		$context = stream_context_create(array(
			'http' => array('timeout' => 10, 'follow_location' => 0),
			'https' => array('timeout' => 10),
		));

		$imageData = @file_get_contents($url, false, $context);

		if ($imageData === false) {
			$this->render('jsonError', array('error' => 'Não foi possível baixar a imagem dessa URL.'));
			return;
		}

		if (strlen($imageData) > 5 * 1024 * 1024) {
			$this->render('jsonError', array('error' => 'A imagem tem mais de 5MB.'));
			return;
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = $finfo->buffer($imageData);

		if (strpos($mimeType, 'image/') !== 0) {
			$this->render('jsonError', array('error' => 'Essa URL não aponta para uma imagem (tipo detectado: ' . $mimeType . ').'));
			return;
		}

		$dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

		$this->render('jsonFetchImage', array('dataUri' => $dataUri));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('HostFace');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new HostFace('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['HostFace']))
			$model->attributes=$_GET['HostFace'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return HostFace the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=HostFace::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param HostFace $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='host-face-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
        
        protected function setHostFaceOnHosts($model, $host_ids){
            /* unset old hosts */
            foreach ($model->hosts as $host) {
                if($host instanceof Host) {
                    $host->host_face_id = null;
                    $host->save();
                }
            }
            /* set new hosts */
            $hosts = Host::model()->findAllByPk($host_ids);
            foreach ($hosts as $host) {
                if($host instanceof Host) {
                    $host->host_face_id = $model->id;
                    $host->save();
                }
            }
            
        }
}
