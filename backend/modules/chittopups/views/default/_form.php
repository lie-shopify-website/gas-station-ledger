<?php

/** @var yii\web\View $this */
/** @var common\models\GslChitTopup $model */

use common\models\GslChitCustomer;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<?php $form = ActiveForm::begin(); ?>
<div class="row">
    <div class="col-md-4"><?= $form->field($model, 'customer_id')->dropDownList(GslChitCustomer::options(), ['prompt' => Yii::t('app', '选择油票客户')]) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'topup_date')->textInput(['type' => 'date']) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'amount')->textInput(['type' => 'number', 'step' => '0.01']) ?></div>
</div>
<div class="row">
    <div class="col-md-12"><?= $form->field($model, 'note')->textInput(['maxlength' => true]) ?></div>
</div>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
    <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>