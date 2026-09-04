<?php



/** @var yii\web\View $this */

/** @var common\models\GslFill $model */



use common\models\GslCompany;

use common\models\GslCounter;

use common\models\GslPlate;

use yii\helpers\ArrayHelper;

use yii\helpers\Html;

use yii\helpers\Json;

use yii\widgets\ActiveForm;



$allPlates = GslPlate::find()

    ->where(['is_active' => 1])

    ->orderBy(['company_id' => SORT_ASC, 'plate_no' => SORT_ASC])

    ->all();



$platesByCompany = [];

foreach ($allPlates as $plate) {

    $platesByCompany[$plate->company_id][] = [

        'id' => $plate->id,

        'plate_no' => $plate->plate_no,

    ];

}



$plateItems = [];

if ($model->company_id) {

    foreach ($platesByCompany[$model->company_id] ?? [] as $item) {

        $plateItems[$item['id']] = $item['plate_no'];

    }

}

?>

<?php $form = ActiveForm::begin(); ?>

<div class="row">

    <div class="col-md-4"><?= $form->field($model, 'work_date')->input('date') ?></div>

    <div class="col-md-4"><?= $form->field($model, 'company_id')->dropDownList(ArrayHelper::map(GslCompany::find()->where(['is_active' => 1])->orderBy('sort_order')->all(), 'id', 'name'), ['prompt' => Yii::t('app', '选择公司')]) ?></div>

    <div class="col-md-4"><?= $form->field($model, 'plate_id')->dropDownList($plateItems, ['prompt' => Yii::t('app', '选择车牌')]) ?></div>

    <div class="col-md-4"><?= $form->field($model, 'counter_id')->dropDownList(ArrayHelper::map(GslCounter::find()->orderBy('sort_order')->all(), 'id', 'code'), ['prompt' => Yii::t('app', '选择柜台')]) ?></div>

    <div class="col-md-4"><?= $form->field($model, 'liters')->textInput(['type' => 'number', 'step' => '0.001']) ?></div>

    <div class="col-md-4"><?= $form->field($model, 'ticket_no')->textInput(['maxlength' => true]) ?></div>

    <div class="col-md-8"><?= $form->field($model, 'note')->textInput(['maxlength' => true]) ?></div>

</div>

<div class="form-group">

    <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>

    <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>

</div>

<?php ActiveForm::end(); ?>

<?php

$platesJson = Json::encode($platesByCompany);

$promptJson = Json::encode(Yii::t('app', '选择车牌'));

$js = <<<JS
(function () {
    var platesByCompany = {$platesJson};
    var prompt = {$promptJson};
    var \$company = $('#gslfill-company_id');
    var \$plate = $('#gslfill-plate_id');

    function refreshPlates(keepSelection) {
        var companyId = \$company.val();
        var current = keepSelection ? \$plate.val() : '';
        \$plate.empty();
        \$plate.append($('<option>').val('').text(prompt));
        if (companyId && platesByCompany[companyId]) {
            platesByCompany[companyId].forEach(function (plate) {
                \$plate.append($('<option>').val(plate.id).text(plate.plate_no));
            });
        }
        if (keepSelection && current) {
            \$plate.val(current);
        }
    }

    \$company.on('change', function () {
        refreshPlates(false);
    });

    refreshPlates(true);
})();
JS;

$this->registerJs($js);

?>
