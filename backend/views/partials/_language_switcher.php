<?php

use common\components\LanguageBootstrap;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */

$current = Yii::$app->language;
$labels = [
    'zh-CN' => Yii::t('app', '中文'),
    'en' => Yii::t('app', '英文'),
];
?>
<li class="nav-item dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#" title="<?= Html::encode(Yii::t('app', '语言')) ?>">
        <i class="fas fa-globe"></i>
        <?= Html::encode($labels[$current] ?? $current) ?>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <?php foreach (LanguageBootstrap::SUPPORTED as $lang): ?>
            <?php if ($lang === $current) continue; ?>
            <?= Html::a($labels[$lang], ['/site/set-language', 'lang' => $lang], [
                'class' => 'dropdown-item',
            ]) ?>
        <?php endforeach; ?>
    </div>
</li>
