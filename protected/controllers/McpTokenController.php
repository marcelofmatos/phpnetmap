<?php

class McpTokenController extends Controller
{
    public $layout = '//layouts/column2';

    public function filters()
    {
        return array(
            'accessControl',
            'postOnly + delete',
        );
    }

    public function accessRules()
    {
        return array(
            array('allow',
                'actions' => array('admin', 'create', 'delete'),
                'users' => array('admin'),
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    public function actionAdmin()
    {
        $dataProvider = new CActiveDataProvider('McpToken', array(
            'sort' => array('defaultOrder' => 'id DESC'),
        ));
        $this->render('admin', array('dataProvider' => $dataProvider));
    }

    public function actionCreate()
    {
        $model = new McpToken;

        if (isset($_POST['McpToken'])) {
            $model->attributes = $_POST['McpToken'];
            $rawToken = $model->generateToken();
            if ($model->save()) {
                Yii::app()->user->setFlash(
                    'mcpTokenCreated',
                    'Token created — copy it now, it will not be shown again: ' . $rawToken
                );
                $this->redirect(array('admin'));
            }
        }

        $this->render('create', array('model' => $model));
    }

    public function actionDelete($id)
    {
        $this->loadModel($id)->delete();
        if (!isset($_GET['ajax'])) {
            $this->redirect(array('admin'));
        }
    }

    public function loadModel($id)
    {
        $model = McpToken::model()->findByPk($id);
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        return $model;
    }
}
