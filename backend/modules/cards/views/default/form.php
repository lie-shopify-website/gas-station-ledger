<?php



/** @var yii\web\View $this */

/** @var common\models\GslCard $model */

/** @var string $title */



use common\models\GslCardType;

use yii\helpers\Html;

use yii\widgets\ActiveForm;

$this->title = $title;

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '油卡管理'), 'url' => ['index']];

$this->params['breadcrumbs'][] = $this->title;

?>

<div class="card card-primary">

    <div class="card-header"><h3 class="card-title"><?= Html::encode($this->title) ?></h3></div>

    <div class="card-body">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'card_code')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'card_type_id')->dropDownList(
            GslCardType::options(),
            ['prompt' => Yii::t('app', '选择卡类型')]
        ) ?>

        <?= $form->field($model, 'real_card_no')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'monthly_quota')->textInput(['type' => 'number', 'step' => '0.001']) ?>

        <?= $form->field($model, 'owner_rate')->textInput(['type' => 'number', 'step' => '0.01']) ?>

        <?= $form->field($model, 'sort_order')->textInput(['type' => 'number']) ?>

        <?= $form->field($model, 'is_active')->checkbox() ?>

        <?= $form->field($model, 'note')->textInput(['maxlength' => true]) ?>

        <div class="form-group">

            <?= Html::submitButton(Yii::t('app', '保存'), ['class' => 'btn btn-success']) ?>

            <?= Html::a(Yii::t('app', '取消'), ['index'], ['class' => 'btn btn-default']) ?>

        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>

