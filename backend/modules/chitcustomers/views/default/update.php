<?php

/** @var yii\web\View $this */
/** @var common\models\GslChitCustomer $model */

use common\models\GslChitCustomer;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '编辑油票客户 #{id}', ['id' => $model->id]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '油票客户'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title"><?= Html::encode($this->title) ?></h3></div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-4"><?= $form->field($model, 'deposit_amount')->textInput(['type' => 'number', 'step' => '0.01']) ?></div>
            <div class="col-md-4"><?= $form->field($model, 'is_active')->dropDownList(GslChitCustomer::activeOptions(), ['prompt' => false]) ?></div>
        </div>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
            <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>