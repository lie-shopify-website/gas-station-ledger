<?php

/** @var yii\web\View $this */
/** @var common\models\GslChit $model */

use common\models\GslCounter;
use hail812\adminlte3\assets\AdminLteAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$counterOperators = [];
foreach (GslCounter::find()->all() as $counter) {
    $counterOperators[$counter->id] = (string) $counter->operator_no;
}

$searchableOptions = ['class' => 'form-control gsl-searchable-select'];
?>
<?php $form = ActiveForm::begin(); ?>
<div class="row">
    <div class="col-md-4"><?= $form->field($model, 'work_date')->input('date') ?></div>
    <div class="col-md-4"><?= $form->field($model, 'counter_id')->dropDownList(
        GslCounter::options(),
        $searchableOptions + ['prompt' => Yii::t('app', '选择柜台')]
    ) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'operator_no')->textInput(['maxlength' => true]) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'amount')->textInput(['type' => 'number', 'step' => '0.01']) ?></div>
    <div class="col-md-4"><?= $form->field($model, 'chit_stub_no')->textInput(['maxlength' => true]) ?></div>
    <div class="col-md-4">
        <div class="gsl-ticket-wrap">
            <?= $form->field($model, 'receipt_no')->textInput(['maxlength' => true, 'autocomplete' => 'off']) ?>
            <div class="gsl-ticket-suggest list-group" hidden></div>
        </div>
    </div>
</div>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>
    <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>
<?php
$suggestUrlJson = Json::encode(Url::to(['ticket-suggest']));
$operatorsJson = Json::encode($counterOperators);
$emptyJson = Json::encode(Yii::t('app', '无匹配'));
$this->registerCss('.gsl-ticket-wrap{position:relative}.gsl-ticket-suggest{position:absolute;left:0;right:0;top:100%;z-index:1050;max-height:220px;overflow-y:auto;box-shadow:0 2px 8px rgba(0,0,0,.15)}.gsl-ticket-suggest .list-group-item{padding:.35rem .6rem;cursor:pointer}');
$this->registerJsFile('@web/js/gsl-searchable-select.js', ['depends' => [AdminLteAsset::class]]);
$js = <<<JS
(function () {
    var suggestUrl = {$suggestUrlJson};
    var operators = {$operatorsJson};
    var emptyText = {$emptyJson};

    var \$counter = $('#gslchit-counter_id');
    var \$operator = $('#gslchit-operator_no');
    var \$date = $('#gslchit-work_date');
    var \$receipt = $('#gslchit-receipt_no');
    var \$list = $('.gsl-ticket-suggest');

    \$counter.on('change', function () {
        var op = operators[\$counter.val()];
        if (op !== undefined) {
            \$operator.val(op);
        }
    });

    var timer = null;
    var lastRequest = 0;

    function hideList() {
        \$list.prop('hidden', true).empty();
    }

    function renderList(items) {
        \$list.empty();
        if (!items.length) {
            \$list.append($('<div class="list-group-item text-muted"></div>').text(emptyText));
            \$list.prop('hidden', false);
            return;
        }
        items.forEach(function (text) {
            $('<button type="button" class="list-group-item list-group-item-action"></button>')
                .text(text)
                .on('mousedown', function (e) {
                    e.preventDefault();
                    \$receipt.val(text);
                    hideList();
                })
                .appendTo(\$list);
        });
        \$list.prop('hidden', false);
    }

    function fetchSuggest() {
        var reqId = ++lastRequest;
        $.getJSON(suggestUrl, { work_date: \$date.val(), q: $.trim(\$receipt.val()) })
            .done(function (data) {
                if (reqId !== lastRequest) {
                    return;
                }
                renderList((data && data.items) ? data.items : []);
            })
            .fail(function () {
                if (reqId === lastRequest) {
                    hideList();
                }
            });
    }

    \$receipt.on('input', function () {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(fetchSuggest, 200);
    });
    \$receipt.on('focus', fetchSuggest);
    \$receipt.on('blur', function () {
        setTimeout(hideList, 150);
    });

    if (window.GslSearchableSelect) {
        GslSearchableSelect.bind('#gslchit-counter_id', emptyText);
    }
})();
JS;
$this->registerJs($js);
?>