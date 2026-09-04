<?php

/** @var yii\web\View $this */
/** @var array $kpis */
/** @var string $month */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '仪表盘');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card summary-panel">
    <div class="card-header">
        <h3 class="card-title"><?= Yii::t('app', '月度 KPI — {month}', ['month' => Html::encode($month)]) ?></h3>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]); ?>
        <div class="form-row align-items-end mb-3">
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '月份') ?></label>
                <input type="month" name="month" class="form-control" value="<?= Html::encode($month) ?>">
            </div>
            <div class="col-auto">
                <?= Html::submitButton(Yii::t('app', '查询'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <div class="row">
            <div class="col-md-2 col-sm-4 col-6">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-list-ol"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?= Yii::t('app', '加油笔数') ?></span>
                        <span class="info-box-number"><?= (int) $kpis['fill_count'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-tint"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?= Yii::t('app', '总升数') ?></span>
                        <span class="info-box-number"><?= number_format($kpis['total_liters'], 3) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-yen-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?= Yii::t('app', '应收金额') ?></span>
                        <span class="info-box-number"><?= number_format($kpis['amount_due'], 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?= Yii::t('app', '总成本') ?></span>
                        <span class="info-box-number"><?= number_format($kpis['total_cost'], 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="fas fa-credit-card"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?= Yii::t('app', '刷卡升数') ?></span>
                        <span class="info-box-number"><?= number_format($kpis['swipe_liters'], 3) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
