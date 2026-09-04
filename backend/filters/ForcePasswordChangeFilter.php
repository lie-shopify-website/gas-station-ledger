<?php

namespace backend\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

class ForcePasswordChangeFilter extends ActionFilter
{
    public $allowedRoutes = [
        'site/change-password',
        'site/logout',
        'site/error',
        'site/login',
        'site/set-language',
    ];

    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest) {
            return parent::beforeAction($action);
        }

        /** @var \common\models\User $user */
        $user = Yii::$app->user->identity;
        if (!$user->must_change_password) {
            return parent::beforeAction($action);
        }

        $route = $action->controller->getRoute();
        if (in_array($route, $this->allowedRoutes, true)) {
            return parent::beforeAction($action);
        }

        Yii::$app->session->setFlash('warning', Yii::t('app', '请先修改初始密码后再使用系统。'));
        Yii::$app->response->redirect(['/site/change-password'])->send();
        return false;
    }
}
