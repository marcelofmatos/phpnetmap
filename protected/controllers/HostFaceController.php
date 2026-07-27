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
	 * @param integer $hostId host sem face ainda (ex.: vindo do link "create new"
	 *   na aba Overview do host) — pré-seleciona esse host como fonte SNMP do
	 *   editor e já marca ele na lista de hosts a associar.
	 */
	public function actionCreate($hostId=null)
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

		$preselectedHost = (!isset($_POST['HostFace']) && $hostId) ? Host::model()->findByPk((int) $hostId) : null;

		$this->render('create',array(
			'model'=>$model,
			'preselectedHost'=>$preselectedHost,
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
			$this->render('jsonError', array('error' => 'Invalid URL.'));
			return;
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);
		if ($scheme !== 'http' && $scheme !== 'https') {
			$this->render('jsonError', array('error' => 'Only http/https URLs are allowed.'));
			return;
		}

		// Proteção básica contra SSRF: recusa endereços privados/reservados
		// (ex.: 127.0.0.1, 10.0.0.0/8, 169.254.169.254 — metadata endpoint
		// de provedores de nuvem) antes de fazer a requisição.
		$host = parse_url($url, PHP_URL_HOST);
		$ip = $host ? gethostbyname($host) : false;
		if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			$this->render('jsonError', array('error' => 'This URL points to a disallowed address.'));
			return;
		}

		// follow_location desligado de propósito: seguir redirect faria a
		// checagem de IP acima (feita só na URL original) não valer pro
		// destino final, reabrindo a mesma brecha de SSRF. O wrapper https://
		// do PHP também lê essas opções daqui (a chave 'http' vale pros dois
		// esquemas) — não existe uma seção 'https' separada pra isso.
		$context = stream_context_create(array(
			'http' => array('timeout' => 10, 'follow_location' => 0),
		));

		$maxBytes = 5 * 1024 * 1024;
		$fp = @fopen($url, 'rb', false, $context);

		if ($fp === false) {
			$this->render('jsonError', array('error' => 'Could not download the image from this URL.'));
			return;
		}

		// Lê em pedaços e aborta assim que passar do limite, em vez de
		// baixar o corpo inteiro (sem limite) pra só then descartar — evita
		// que uma resposta lenta/gigante prenda memória sem necessidade.
		$imageData = '';
		while (!feof($fp)) {
			$chunk = fread($fp, 8192);
			if ($chunk === false) {
				break;
			}
			$imageData .= $chunk;
			if (strlen($imageData) > $maxBytes) {
				fclose($fp);
				$this->render('jsonError', array('error' => 'The image is larger than 5MB.'));
				return;
			}
		}
		fclose($fp);

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = $finfo->buffer($imageData);

		// Allowlist de formatos raster — não image/svg+xml: é a foto do
		// painel do switch, nunca precisa ser vetorial, e SVG pode conter
		// conteúdo com script embutido.
		$allowedMimeTypes = array('image/png', 'image/jpeg', 'image/gif', 'image/webp');
		if (!in_array($mimeType, $allowedMimeTypes)) {
			$this->render('jsonError', array('error' => 'This URL does not point to a PNG/JPEG/GIF/WEBP image (detected type: ' . $mimeType . ').'));
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
