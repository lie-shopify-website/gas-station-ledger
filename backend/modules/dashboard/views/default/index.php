<?php

/** @var yii\web\View $this */
/** @var array $kpis */
/** @var string $month */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '仪表盘');
$this->params['breadcrumbs'][] = $this->title;

$dailyTitle = static function (string $label) use ($month): string {
    return Yii::t('app', '{label} — {month} 每日汇总', [
        'label' => $label,
        'month' => $month,
    ]);
};
$dailyUrl = static function (string $metric) use ($month): string {
    return Url::to(['/dashboard/default/daily', 'month' => $month, 'metric' => $metric]);
};
$kpiBox = static function (string $bg, string $icon, string $label, string $value, array $trigger) use ($dailyTitle, $dailyUrl): string {
    $attrs = [
        'class' => 'info-box ' . $bg . ' gsl-detail-modal-trigger kpi-clickable',
        'role' => 'button',
        'tabindex' => '0',
        'data-title' => $trigger['title'] ?? $dailyTitle($label),
    ];
    if (isset($trigger['url'])) {
        $attrs['data-url'] = $trigger['url'];
    } else {
        $attrs['data-url'] = $dailyUrl($trigger['metric']);
    }

    return Html::tag(
        'div',
        Html::tag('span', Html::tag('i', '', ['class' => $icon]), ['class' => 'info-box-icon'])
        . Html::tag(
            'div',
            Html::tag('span', $label, ['class' => 'info-box-text'])
            . Html::tag('span', $value, ['class' => 'info-box-number']),
            ['class' => 'info-box-content']
        ),
        $attrs
    );
};
?>
<div class="card summary-panel">
    <div class="card-header">
        <h3 class="card-title"><?= Yii::t('app', '月度 KPI — {month}', ['month' => Html::encode($month)]) ?></h3>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['/dashboard/default/index']]); ?>
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

        <div class="row gsl-info-boxes">
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-info', 'fas fa-list-ol', Yii::t('app', '加油笔数'), (string) (int) $kpis['fill_count'], ['metric' => 'fill_count']) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-success', 'fas fa-tint', Yii::t('app', '总升数'), number_format($kpis['total_liters'], 3), ['metric' => 'total_liters']) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-warning', 'fas fa-yen-sign', Yii::t('app', '应收金额'), number_format($kpis['amount_due'], 2), ['metric' => 'amount_due']) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-primary', 'fas fa-cash-register', Yii::t('app', '柜台金额'), number_format($kpis['list_amount'], 2), [
                    'url' => Url::to(['/dashboard/default/counters', 'month' => $month]),
                    'title' => Yii::t('app', '柜台月汇总 — {month}', ['month' => $month]),
                ]) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-danger', 'fas fa-chart-line', Yii::t('app', '总成本'), number_format($kpis['total_cost'], 2), ['metric' => 'total_cost']) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-secondary', 'fas fa-credit-card', Yii::t('app', '刷卡升数'), number_format($kpis['swipe_liters'], 3), ['metric' => 'swipe_liters']) ?>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerCss('.kpi-clickable{cursor:pointer}.kpi-clickable:hover{box-shadow:0 .25rem .75rem rgba(0,0,0,.18);transform:translateY(-1px)}.kpi-clickable:focus{outline:2px solid rgba(255,255,255,.7);outline-offset:2px}');
?>
