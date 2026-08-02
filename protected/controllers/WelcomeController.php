<?php

class WelcomeController extends Controller
{
    public function filters()
    {
        return array(
            'accessControl',
        );
    }

    public function accessRules()
    {
        return array(
            array('allow',
                'actions' => array('index', 'tour'),
                'users' => array('admin'),
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    public function actionIndex()
    {
        // Defensive re-check: if the install is no longer fresh (a real
        // host got added since this page was linked/bookmarked), don't
        // show a stale welcome screen — send the admin to the normal app.
        // Threshold is > 1, not > 0, because every install ships with one
        // seeded "localhost" placeholder host (id 1) that Configuration's
        // required Gateway field needs — that alone doesn't count as "a
        // real host was added."
        if (Host::model()->count() > 1) {
            $this->redirect(array('map/index'));
        }

        $this->render('index');
    }

    public function actionTour()
    {
        $this->render('tour');
    }
}
