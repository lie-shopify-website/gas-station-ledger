<?php

/** @var yii\web\View $this */
/** @var common\models\GslPricePeriod $model */
/** @var string $title */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '价格管理'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= Html::encode($this->title) ?></h3></div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'valid_from')->input('date') ?>
        <?= $form->field($model, 'valid_to')->input('date') ?>
        <?= $form->field($model, 'list_price')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
            <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
