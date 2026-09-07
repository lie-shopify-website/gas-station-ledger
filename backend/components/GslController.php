<?php

namespace backend\components;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class GslController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    protected function checkPermission(string $permission): void
    {
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->loginRequired();
            Yii::$app->end();
        }

        if (!Yii::$app->user->can($permission)) {
            throw new ForbiddenHttpException(Yii::t('app', '无权访问此页面。'));
        }
    }

    protected function canWrite(string $permission): bool
    {
        return Yii::$app->user->can($permission);
    }
}
