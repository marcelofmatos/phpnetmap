<?php

class SearchController extends Controller {
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
    public function actionForm() {
        $model = new SearchForm;
        $form = new CForm('application.views.search.form', $model);
        $this->render('form', array('form' => $form));
    }

    public function actionIndex() {
        $model = new SearchForm;
        $form = new CForm('application.views.search.form', $model);
        if ($form->submitted('submit') && $model->validate()) {
            $result = $model->searchResult();
            $this->render('index', array('form' => $form, 'searchModel'=> $model, 'result' => $result));
        } else {
            $this->render('index', array('form' => $form));
        }
    }

    public function actionResult() {
        $model = new SearchForm;
        $form = new CForm('application.views.search.form', $model);
        if ($form->submitted('search') && $form->validate()) {
            $result = $model->searchResult();
            //$this->redirect(array('search/index'));
            $this->render('index', array('form' => $form, 'result' => $result));
        } else
            $this->render('index', array('form' => $form));
    }

    // Uncomment the following methods and override them if needed
    /*
      public function filters()
      {
      // return the filter configuration for this controller, e.g.:
      return array(
      'inlineFilterName',
      array(
      'class'=>'path.to.FilterClass',
      'propertyName'=>'propertyValue',
      ),
      );
      }

      public function actions()
      {
      // return external action classes, e.g.:
      return array(
      'action1'=>'path.to.ActionClass',
      'action2'=>array(
      'class'=>'path.to.AnotherActionClass',
      'propertyName'=>'propertyValue',
      ),
      );
      }
     */
}