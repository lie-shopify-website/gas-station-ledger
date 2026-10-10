<?php

namespace backend\controllers;

use common\components\LanguageBootstrap;
use common\models\ChangePasswordForm;
use common\models\LoginForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'set-language'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'change-password', 'set-language'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect($this->landingRoute());
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect($this->landingRoute());
        }

        $this->layout = 'main-login';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return Yii::$app->user->can('dashboard.view')
                ? $this->goBack()
                : $this->redirect($this->landingRoute());
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * 登录落地页：有仪表盘权限回仪表盘，否则回第一个可访问页面（员工账号→油票登记）。
     */
    protected function landingRoute(): array
    {
        if (Yii::$app->user->can('dashboard.view')) {
            return ['/dashboard/default/index'];
        }
        if (Yii::$app->user->can('chits.view')) {
            return ['/chits/default/index'];
        }

        return ['/site/login'];
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionChangePassword()
    {
        $this->layout = 'main';

        $model = new ChangePasswordForm();
        if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '密码修改成功。'));
            return $this->redirect(['/dashboard/default/index']);
        }

        return $this->render('change-password', [
            'model' => $model,
        ]);
    }

    public function actionSetLanguage(string $lang)
    {
        if (!LanguageBootstrap::isSupported($lang)) {
            throw new BadRequestHttpException('Unsupported language.');
        }

        LanguageBootstrap::persistLanguage($lang);

        return $this->redirect(Yii::$app->request->referrer ?: ['/site/login']);
    }
}
