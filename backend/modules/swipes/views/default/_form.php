<?php

/** @var yii\web\View $this */
/** @var common\models\GslSwipe $model */
/** @var array $summary */

use common\models\GslCard;
use common\models\GslCompany;
use hail812\adminlte3\assets\AdminLteAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$searchableOptions = ['class' => 'form-control gsl-searchable-select'];

$cardItems = ArrayHelper::map(
    GslCard::find()->where(['is_active' => 1])->orderBy('sort_order')->all(),
    'id',
    fn($card) => $card->card_type_id && $card->cardType
        ? $card->card_code . '（' . $card->cardType->name . '）'
        : $card->card_code
);
?>
<?php $form = ActiveForm::begin(); ?>
<div class="alert alert-info swipe-summary-panel" role="alert">
    <div class="row">
        <div class="col-md-3">
            <span class="text-muted"><?= Yii::t('app', '加油升数') ?></span>
            <div><strong id="swipe-summary-fill">0.000</strong> <?= Yii::t('app', '升') ?></div>
        </div>
        <div class="col-md-3">
            <span class="text-muted"><?= Yii::t('app', '已刷升数') ?></span>
            <div><strong id="swipe-summary-swiped">0.000</strong> <?= Yii::t('app', '升') ?></div>
        </div>
        <div class="col-md-3">
            <span class="text-muted"><?= Yii::t('app', '待刷升数') ?></span>
            <div><strong id="swipe-summary-left">0.000</strong> <?= Yii::t('app', '升') ?></div>
        </div>
        <div class="col-md-3">
            <span class="text-muted"><?= Yii::t('app', '该卡当日已刷') ?></span>
            <div><strong id="swipe-summary-card-used">0.000</strong> / <span id="swipe-summary-card-limit">0.000</span> <?= Yii::t('app', '升') ?></div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4"><?= $form->field($model, 'work_date')->input('date') ?></div>
    <div class="col-md-4"><?= $form->field($model, 'company_id')->dropDownList(
        ArrayHelper::map(GslCompany::find()->where(['is_active' => 1])->orderBy('sort_order')->all(), 'id', 'name'),
        $searchableOptions + ['prompt' => Yii::t('app', '选择公司')]
    ) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'card_id')->dropDownList(
        $cardItems,
        $searchableOptions + ['prompt' => Yii::t('app', '选择油卡')]
    ) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'liters')->textInput(['type' => 'number', 'step' => '0.001']) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'discount_price')->textInput(['readonly' => true]) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'amount_due')->textInput(['readonly' => true]) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'swipe_receipt')->textInput(['maxlength' => true]) ?></div>
    <div class="col-md-8"><?= $form->field($model, 'note')->textInput(['maxlength' => true]) ?></div>
</div>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
    <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>
<?php
$summaryUrlJson = Json::encode(Url::to(['summary']));
$initialJson = Json::encode($summary);
$emptyJson = Json::encode(Yii::t('app', '无匹配'));
$excludeId = (int) $model->id;
$this->registerJsFile('@web/js/gsl-searchable-select.js', ['depends' => [AdminLteAsset::class]]);
$js = <<<JS
(function () {
    var summaryUrl = {$summaryUrlJson};
    var initial = {$initialJson};
    var emptyText = {$emptyJson};
    var excludeId = {$excludeId};

    var \$date = $('#gslswipe-work_date');
    var \$company = $('#gslswipe-company_id');
    var \$card = $('#gslswipe-card_id');
    var \$liters = $('#gslswipe-liters');
    var \$price = $('#gslswipe-discount_price');
    var \$amount = $('#gslswipe-amount_due');

    function fmt(n, digits) {
        n = parseFloat(n);
        if (isNaN(n)) {
            n = 0;
        }
        return n.toFixed(digits === undefined ? 3 : digits);
    }

    function calcAmount() {
        var liters = parseFloat(\$liters.val()) || 0;
        var price = parseFloat(\$price.val()) || 0;
        \$amount.val((liters * price).toFixed(2));
    }

    function paint(data) {
        if (!data) {
            return;
        }
        $('#swipe-summary-fill').text(fmt(data.fill_liters));
        $('#swipe-summary-swiped').text(fmt(data.swiped_liters));
        $('#swipe-summary-left').text(fmt(data.left_liters));
        $('#swipe-summary-card-used').text(fmt(data.card_day_liters));
        $('#swipe-summary-card-limit').text(fmt(data.card_day_limit));
        if (data.discount_price !== null && data.discount_price !== undefined) {
            \$price.val(fmt(data.discount_price, 2));
        }
        calcAmount();
    }

    function refresh() {
        $.getJSON(summaryUrl, {
            work_date: \$date.val(),
            company_id: \$company.val(),
            card_id: \$card.val(),
            exclude_id: excludeId
        }).done(paint);
    }

    \$date.on('change', refresh);
    \$company.on('change', refresh);
    \$card.on('change', refresh);
    \$liters.on('input', calcAmount);
    \$price.on('input', calcAmount);

    paint(initial);

    if (window.GslSearchableSelect) {
        GslSearchableSelect.bind('#gslswipe-company_id, #gslswipe-card_id', emptyText);
    }
})();
JS;
$this->registerJs($js);
?>