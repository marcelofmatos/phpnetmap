<?php

class UserController extends Controller
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
                'actions' => array('admin', 'create', 'update', 'delete'),
                'users' => array('admin'),
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    public function actionAdmin()
    {
        $dataProvider = new CActiveDataProvider('User', array(
            'sort' => array('defaultOrder' => 'username'),
        ));
        $this->render('admin', array('dataProvider' => $dataProvider));
    }

    public function actionCreate()
    {
        $model = new User;

        if (isset($_POST['User'])) {
            $model->attributes = $_POST['User'];
            $password = isset($_POST['User']['password']) ? $_POST['User']['password'] : '';

            $valid = $model->validate();
            if ($password === '') {
                $model->addError('password', 'Password cannot be blank.');
                $valid = false;
            }

            if ($valid) {
                $model->setPassword($password);
                if ($model->save(false)) {
                    $this->redirect(array('admin'));
                }
            }
        }

        $this->render('create', array('model' => $model));
    }

    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        if (isset($_POST['User'])) {
            $model->attributes = $_POST['User'];
            $password = isset($_POST['User']['password']) ? $_POST['User']['password'] : '';

            if ($model->validate()) {
                if ($password !== '') {
                    $model->setPassword($password);
                }
                if ($model->save(false)) {
                    $this->redirect(array('admin'));
                }
            }
        }

        $this->render('update', array('model' => $model));
    }

    public function actionDelete($id)
    {
        $model = $this->loadModel($id);

        if (!$model->delete()) {
            Yii::app()->user->setFlash(
                'userDeleteBlocked',
                'The "admin" account cannot be deleted — it is needed to sign in to every admin-only page, including this one.'
            );
        }

        if (!isset($_GET['ajax'])) {
            $this->redirect(array('admin'));
        }
    }

    public function loadModel($id)
    {
        $model = User::model()->findByPk($id);
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        return $model;
    }
}
