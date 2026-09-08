<?php

/** @var yii\web\View $this */
/** @var array $kpis */
/** @var string $month */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var array $companyMonthly */
/** @var array $dailyCompany */
/** @var array $counterMonthly */

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
                <?= $kpiBox('bg-success', 'fas fa-tint', Yii::t('app', '总升数'), number_format($kpis['total_liters'], 3), [
                    'url' => Url::to(['/dashboard/default/daily-company', 'month' => $month]),
                    'title' => Yii::t('app', '按日按公司 — {month}', ['month' => $month]),
                ]) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-primary', 'fas fa-cash-register', Yii::t('app', '柜台金额'), number_format($kpis['list_amount'], 2), [
                    'url' => Url::to(['/dashboard/default/counters', 'month' => $month]),
                    'title' => Yii::t('app', '柜台月汇总 — {month}', ['month' => $month]),
                ]) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-warning', 'fas fa-yen-sign', Yii::t('app', '应收金额'), number_format($kpis['amount_due'], 2), ['metric' => 'amount_due']) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-danger', 'fas fa-chart-line', Yii::t('app', '总成本'), number_format($kpis['total_cost'], 2), ['metric' => 'total_cost']) ?>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <?= $kpiBox('bg-secondary', 'fas fa-credit-card', Yii::t('app', '刷卡升数'), number_format($kpis['swipe_liters'], 3), ['metric' => 'swipe_liters']) ?>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h5 class="mb-3"><?= Yii::t('app', '按公司') ?></h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead>
                        <tr>
                            <th><?= Yii::t('app', '公司') ?></th>
                            <th><?= Yii::t('app', '付款方式') ?></th>
                            <th class="text-right"><?= Yii::t('app', '笔数') ?></th>
                            <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                            <th class="text-right"><?= Yii::t('app', '应收') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($companyMonthly['companies'] as $row): ?>
                            <tr>
                                <td><?= Html::encode($row['company_name']) ?></td>
                                <td><?= Html::encode($row['payment_type']) ?></td>
                                <td class="text-right"><?= (int) $row['count'] ?></td>
                                <td class="text-right"><?= number_format($row['liters'], 3) ?></td>
                                <td class="text-right"><?= number_format($row['amount_due'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="2"><?= Yii::t('app', '合计') ?></td>
                            <td class="text-right"><?= (int) $companyMonthly['totals']['count'] ?></td>
                            <td class="text-right"><?= number_format($companyMonthly['totals']['liters'], 3) ?></td>
                            <td class="text-right"><?= number_format($companyMonthly['totals']['amount_due'], 2) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="mb-3"><?= Yii::t('app', '按柜台') ?></h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead>
                        <tr>
                            <th><?= Yii::t('app', '柜台') ?></th>
                            <th class="text-right"><?= Yii::t('app', '笔数') ?></th>
                            <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                            <th class="text-right"><?= Yii::t('app', '挂牌金额') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($counterMonthly['counters'] as $row): ?>
                            <?php if ((int) $row['count'] === 0) { continue; } ?>
                            <tr>
                                <td><?= Html::encode($row['counter_code']) ?></td>
                                <td class="text-right"><?= (int) $row['count'] ?></td>
                                <td class="text-right"><?= number_format($row['liters'], 3) ?></td>
                                <td class="text-right"><?= number_format($row['list_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr class="font-weight-bold">
                            <td><?= Yii::t('app', '合计') ?></td>
                            <td class="text-right"><?= (int) $counterMonthly['totals']['count'] ?></td>
                            <td class="text-right"><?= number_format($counterMonthly['totals']['liters'], 3) ?></td>
                            <td class="text-right"><?= number_format($counterMonthly['totals']['list_amount'], 2) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <h5 class="mb-3 mt-4"><?= Yii::t('app', '按日按公司') ?></h5>
        <?= $this->render('_daily_company', ['dailyCompany' => $dailyCompany]) ?>

        <hr class="mt-4 mb-3">
        <h5 class="mb-3"><?= Yii::t('app', '报告导出/下载') ?></h5>
        <?php $exportForm = ActiveForm::begin(['method' => 'get', 'action' => ['/dashboard/default/export']]); ?>
        <?= Html::hiddenInput('month', $month) ?>
        <div class="form-row align-items-end">
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '开始日期') ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= Html::encode($dateFrom) ?>">
            </div>
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '结束日期') ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= Html::encode($dateTo) ?>">
            </div>
            <div class="col-auto">
                <?= Html::submitButton(Yii::t('app', '下载 Excel'), ['class' => 'btn btn-outline-primary']) ?>
            </div>
            <div class="col-auto">
                <?= Html::submitButton(Yii::t('app', '下载精简版'), [
                    'class' => 'btn btn-outline-secondary',
                    'name' => 'variant',
                    'value' => 'compact',
                ]) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<?php
$this->registerCss('.kpi-clickable{cursor:pointer}.kpi-clickable:hover{box-shadow:0 .25rem .75rem rgba(0,0,0,.18);transform:translateY(-1px)}.kpi-clickable:focus{outline:2px solid rgba(255,255,255,.7);outline-offset:2px}');
?>
