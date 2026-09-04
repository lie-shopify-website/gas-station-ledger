<?php

/** @var yii\web\View $this */
/** @var backend\modules\settings\models\SettingsForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = Yii::t('app', '系统设置');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= Html::encode($this->title) ?></h3></div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'ledger_month')->input('month') ?>
        <?= $form->field($model, 'cost_per_liter')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
