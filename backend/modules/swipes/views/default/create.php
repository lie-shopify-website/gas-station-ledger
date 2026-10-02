<?php

/** @var yii\web\View $this */
/** @var common\models\GslSwipe $model */
/** @var array $summary */

use yii\helpers\Html;
$this->title = Yii::t('app', '新增刷卡记录');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '刷卡记录'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= Html::encode($this->title) ?></h3></div>
    <div class="card-body">
        <?= $this->render('_form', ['model' => $model, 'summary' => $summary]) ?>
    </div>
</div>