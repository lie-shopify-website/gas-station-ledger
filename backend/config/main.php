<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'name' => 'Gas Station Ledger',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'homeUrl' => ['/dashboard/default/index'],
    'bootstrap' => ['log', 'common\components\LanguageBootstrap'],
    'modules' => [
        'dashboard' => ['class' => 'backend\modules\dashboard\Module'],
        'daily' => ['class' => 'backend\modules\daily\Module'],
        'counter' => ['class' => 'backend\modules\counter\Module'],
        'swipes' => ['class' => 'backend\modules\swipes\Module'],
        'fills' => ['class' => 'backend\modules\fills\Module'],
        'plates' => ['class' => 'backend\modules\plates\Module'],
        'prices' => ['class' => 'backend\modules\prices\Module'],
        'cards' => ['class' => 'backend\modules\cards\Module'],
        'settings' => ['class' => 'backend\modules\settings\Module'],
        'user' => ['class' => 'backend\modules\user\Module'],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/site/login'],
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@app/views' => [
                        '@app/views',
                        '@vendor/hail812/yii2-adminlte3/src/views',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
];
