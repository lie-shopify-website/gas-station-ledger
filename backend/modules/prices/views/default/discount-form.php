<?php

/** @var yii\web\View $this */
/** @var common\models\GslPriceDiscount $model */
/** @var common\models\GslPricePeriod $period */
/** @var string $title */

use common\models\GslCompany;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '价格管理'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <?= Html::encode($this->title) ?>
            <small>(<?= Html::encode($period->valid_from) ?> ~ <?= Html::encode($period->valid_to) ?>)</small>
        </h3>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'company_id')->dropDownList(
            ArrayHelper::map(GslCompany::find()->where(['is_active' => 1])->orderBy('sort_order')->all(), 'id', 'name'),
            ['prompt' => Yii::t('app', '选择公司')]
        ) ?>
        <?= $form->field($model, 'discount_price')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
            <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
