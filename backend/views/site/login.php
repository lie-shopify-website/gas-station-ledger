<?php

/** @var yii\web\View $this */
/** @var common\models\LoginForm $model */

use common\components\LanguageBootstrap;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('app', '登录');
?>
<div class="card">
    <div class="card-body login-card-body">
        <p class="login-box-msg"><?= Yii::t('app', '登录 Gas Station Ledger') ?></p>

        <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

        <?= $form->field($model, 'username', [
            'options' => ['class' => 'form-group has-feedback'],
            'inputTemplate' => '{input}<div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>',
            'template' => '{beginWrapper}{input}{error}{endWrapper}',
        ])->textInput(['autofocus' => true, 'placeholder' => Yii::t('app', '用户名')])->label(false) ?>

        <?= $form->field($model, 'password', [
            'options' => ['class' => 'form-group has-feedback'],
            'inputTemplate' => '{input}<div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>',
            'template' => '{beginWrapper}{input}{error}{endWrapper}',
        ])->passwordInput(['placeholder' => Yii::t('app', '密码')])->label(false) ?>

        <div class="row">
            <div class="col-8">
                <?= $form->field($model, 'rememberMe')->checkbox() ?>
            </div>
            <div class="col-4">
                <?= Html::submitButton(Yii::t('app', '登录'), ['class' => 'btn btn-primary btn-block']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>

        <div class="text-center mt-3">
            <i class="fas fa-globe"></i>
            <?php foreach (LanguageBootstrap::SUPPORTED as $lang): ?>
                <?php
                $label = $lang === 'zh-CN' ? Yii::t('app', '中文') : Yii::t('app', '英文');
                $active = Yii::$app->language === $lang;
                ?>
                <?= $active
                    ? Html::tag('span', $label, ['class' => 'font-weight-bold mx-1'])
                    : Html::a($label, ['/site/set-language', 'lang' => $lang], ['class' => 'mx-1']) ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
