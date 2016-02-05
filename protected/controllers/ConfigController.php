<?php

class ConfigController extends Controller {
    
    public function actionForm() {
        $model = new ConfigForm;
        $form = new CForm('application.views.config.form', $model);
        $model->load();
        $this->render('form', array('form' => $form));
    }
    
    public function actionIndex() {
        
        $model = new ConfigForm;
        $form = new CForm('application.views.config.form', $model);
        $model->load();

        if ($form->submitted('submit') && $model->validate()) {
            $model->attributes = $_POST['ConfigForm'];
            if ($model->save()) {
                Yii::app()->user->setFlash('config', Yii::t('app', 'Your new options have been saved.'));
            }
            $this->render('index', array('form' => $form, 'model'=> $model, 'result' => $result));
        } else {
            $this->render('index', array('form' => $form));
        }
        
    }

}
