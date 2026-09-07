<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var string $assetDir */
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= Url::to(['/dashboard/default/index']) ?>" class="brand-link">
        <img src="<?= $assetDir ?>/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Gas Station Ledger</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= $assetDir ?>/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= Yii::$app->user->isGuest ? 'Guest' : Html::encode(Yii::$app->user->identity->username) ?></a>
            </div>
        </div>

        <nav class="mt-2">
            <?php
            echo \hail812\adminlte\widgets\Menu::widget([
                'items' => [
                    ['label' => Yii::t('app', '仪表盘'), 'icon' => 'tachometer-alt', 'url' => ['/dashboard/default/index']],
                    ['label' => Yii::t('app', '日报汇总'), 'icon' => 'calendar-day', 'url' => ['/daily/summary/index']],
                    ['label' => Yii::t('app', '柜台汇总'), 'icon' => 'cash-register', 'url' => ['/counter/summary/index']],
                    ['label' => Yii::t('app', '加油记录'), 'icon' => 'gas-pump', 'url' => ['/fills/default/index']],
                    ['label' => Yii::t('app', '刷卡状态'), 'icon' => 'credit-card', 'url' => ['/swipes/status/index']],
                    ['label' => Yii::t('app', '公司/车牌'), 'icon' => 'car', 'url' => ['/plates/default/index']],
                    ['label' => Yii::t('app', '价格管理'), 'icon' => 'tags', 'url' => ['/prices/default/index']],
                    ['label' => Yii::t('app', '油卡管理'), 'icon' => 'id-card', 'url' => ['/cards/default/index']],
                    ['label' => Yii::t('app', '系统设置'), 'icon' => 'cog', 'url' => ['/settings/default/index'], 'visible' => Yii::$app->user->can('settings.write')],
                    ['label' => Yii::t('app', '用户管理'), 'icon' => 'users', 'url' => ['/user/default/index'], 'visible' => Yii::$app->user->can('users.manage')],
                ],
            ]);
            ?>
        </nav>
    </div>
</aside>

