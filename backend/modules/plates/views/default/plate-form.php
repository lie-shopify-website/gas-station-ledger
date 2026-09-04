<?php

/** @var yii\web\View $this */
/** @var common\models\GslPlate $model */
/** @var common\models\GslCompany $company */
/** @var string $title */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '公司/车牌'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode($this->title) ?> — <?= Html::encode($company->name) ?></h3>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'plate_no')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'is_active')->checkbox() ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
            <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
