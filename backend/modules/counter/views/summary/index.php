<?php

/** @var yii\web\View $this */
/** @var array $summary */
/** @var array $paymentSummary */
/** @var array $companySummary */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '柜台汇总');
$this->params['breadcrumbs'][] = $this->title;
$workDate = $summary['work_date'];
$listPrice = $summary['list_price'];

?>
<div class="card summary-panel">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode(Yii::t('app', '柜台汇总 — {date}', ['date' => $workDate])) ?></h3>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]); ?>
        <div class="form-row align-items-end">
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '工作日期') ?></label>
                <input type="date" name="work_date" class="form-control" value="<?= Html::encode($workDate) ?>">
            </div>
            <div class="col-auto">
                <?= Html::submitButton(Yii::t('app', '查询'), ['class' => 'btn btn-primary']) ?>
            </div>
            <?php if ($listPrice !== null): ?>
            <div class="col-auto text-muted align-self-center">
                <?= Yii::t('app', '当日挂牌价') ?>：<strong><?= number_format($listPrice, 2) ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <?php ActiveForm::end(); ?>

        <h5 class="mb-3"><?= Yii::t('app', '当日 Cash / MCM 汇总') ?></h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '付款方式') ?></th>
                    <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '挂牌金额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '票数') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($paymentSummary['rows'] as $row): ?>
                <tr>
                    <td><?= Html::encode($row['payment_type']) ?></td>
                    <td class="text-right"><?= number_format($row['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($row['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $row['count'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="font-weight-bold">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <td class="text-right"><?= number_format($paymentSummary['totals']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($paymentSummary['totals']['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $paymentSummary['totals']['count'] ?></td>
                </tr>
                </tfoot>
            </table>
        </div>

        <div class="table-responsive table-responsive-wide mb-4">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th rowspan="2"><?= Yii::t('app', '柜台') ?></th>
                    <th rowspan="2" class="text-right"><?= Yii::t('app', '挂牌价格') ?></th>
                    <th colspan="3" class="text-center"><?= Yii::t('app', '合计') ?></th>
                    <th colspan="3" class="text-center">Cash</th>
                    <th colspan="3" class="text-center">MCM</th>
                    <th rowspan="2"></th>
                </tr>
                <tr>
                    <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '挂牌金额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '笔数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '挂牌金额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '票数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '挂牌金额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '票数') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($summary['counters'] as $row): ?>
                <tr>
                    <td><?= Html::encode($row['counter_code']) ?></td>
                    <td class="text-right"><?= $row['list_price'] !== null ? number_format($row['list_price'], 2) : '—' ?></td>
                    <td class="text-right"><?= number_format($row['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($row['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $row['count'] ?></td>
                    <td class="text-right"><?= number_format($row['cash']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($row['cash']['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $row['cash']['count'] ?></td>
                    <td class="text-right"><?= number_format($row['mcm']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($row['mcm']['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $row['mcm']['count'] ?></td>
                    <td>
                        <?= Html::button(Yii::t('app', '查看明细'), [
                            'class' => 'btn btn-outline-secondary btn-sm gsl-detail-modal-trigger',
                            'data-url' => Url::to(['details', 'counter_id' => $row['counter_id'], 'work_date' => $workDate]),
                            'data-title' => Yii::t('app', '{label} — {date} 明细', [
                                'label' => $row['counter_code'],
                                'date' => $workDate,
                            ]),
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="font-weight-bold">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <td></td>
                    <td class="text-right"><?= number_format($summary['totals']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($summary['totals']['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $summary['totals']['count'] ?></td>
                    <td class="text-right"><?= number_format($summary['totals']['cash']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($summary['totals']['cash']['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $summary['totals']['cash']['count'] ?></td>
                    <td class="text-right"><?= number_format($summary['totals']['mcm']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($summary['totals']['mcm']['list_amount'], 2) ?></td>
                    <td class="text-right"><?= (int) $summary['totals']['mcm']['count'] ?></td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>

        <h5 class="mb-3"><?= Yii::t('app', '按公司') ?></h5>
        <div class="table-responsive table-responsive-wide mb-0">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '公司') ?></th>
                    <th><?= Yii::t('app', '付款方式') ?></th>
                    <th class="text-right"><?= Yii::t('app', '优惠价格') ?></th>
                    <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '应收') ?></th>
                    <th class="text-right"><?= Yii::t('app', '笔数') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($companySummary['companies'] as $row): ?>
                    <?php if ((int) $row['count'] === 0) { continue; } ?>
                    <tr>
                        <td><?= Html::encode($row['company_name']) ?></td>
                        <td><?= Html::encode($row['payment_type']) ?></td>
                        <td class="text-right"><?= $row['discount_price'] !== null ? number_format($row['discount_price'], 2) : '—' ?></td>
                        <td class="text-right"><?= number_format($row['liters'], 3) ?></td>
                        <td class="text-right"><?= number_format($row['amount_due'], 2) ?></td>
                        <td class="text-right"><?= (int) $row['count'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="font-weight-bold">
                    <td colspan="2"><?= Yii::t('app', '合计') ?></td>
                    <td></td>
                    <td class="text-right"><?= number_format($companySummary['totals']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($companySummary['totals']['amount_due'], 2) ?></td>
                    <td class="text-right"><?= (int) $companySummary['totals']['count'] ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
