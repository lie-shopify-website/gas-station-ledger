<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var string $assetDir */
/** @var \common\models\User|null $identity */
$identity = Yii::$app->user->identity;
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= Url::to(['/dashboard/default/index']) ?>" class="nav-link"><?= Yii::t('app', '首页') ?></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        <?= $this->render('//partials/_language_switcher') ?>
        <li class="nav-item d-none d-sm-inline-block">
            <span class="nav-link"><?= $identity ? Html::encode($identity->username) : '' ?></span>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <?= Html::a('<i class="fas fa-key"></i> ' . Yii::t('app', '修改密码'), ['/site/change-password'], [
                'class' => 'nav-link',
                'title' => Yii::t('app', '修改密码'),
            ]) ?>
        </li>
        <li class="nav-item">
            <?= Html::a('<i class="fas fa-sign-out-alt"></i> ' . Yii::t('app', '退出'), ['/site/logout'], [
                'data-method' => 'post',
                'class' => 'nav-link',
                'title' => Yii::t('app', '退出登录'),
            ]) ?>
        </li>
    </ul>
</nav>
