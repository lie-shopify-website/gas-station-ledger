<?php

/** @var yii\web\View $this */
/** @var common\models\GslChitTopup $model */

use yii\helpers\Html;

$this->title = Yii::t('app', '新增预存');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '预存记录'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= Html::encode($this->title) ?></h3></div>
    <div class="card-body">
        <?= $this->render('_form', ['model' => $model]) ?>
    </div>
</div>