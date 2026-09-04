<?php
/* @var $content string */

use yii\bootstrap4\Alert as BsAlert;
use yii\bootstrap4\Breadcrumbs;

$flashTypes = [
    'error' => 'alert-danger',
    'danger' => 'alert-danger',
    'success' => 'alert-success',
    'info' => 'alert-info',
    'warning' => 'alert-warning',
];
$session = Yii::$app->session;
$flashes = $session->getAllFlashes();
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <?php
                        if (!is_null($this->title)) {
                            echo \yii\helpers\Html::encode($this->title);
                        } else {
                            echo \yii\helpers\Inflector::camelize($this->context->id);
                        }
                        ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <?= Breadcrumbs::widget([
                        'homeLink' => [
                            'label' => Yii::t('app', '首页'),
                            'url' => Yii::$app->homeUrl,
                        ],
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        'options' => ['class' => 'breadcrumb float-sm-right'],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php foreach ($flashes as $type => $flash): ?>
                <?php if (!isset($flashTypes[$type])) continue; ?>
                <?php foreach ((array) $flash as $i => $message): ?>
                    <?= BsAlert::widget([
                        'body' => $message,
                        'closeButton' => [],
                        'options' => [
                            'class' => $flashTypes[$type],
                        ],
                    ]) ?>
                <?php endforeach; ?>
                <?php $session->removeFlash($type); ?>
            <?php endforeach; ?>
            <?= $content ?>
        </div>
    </div>
    <?= $this->render('//partials/_detail_modal') ?>
</div>
