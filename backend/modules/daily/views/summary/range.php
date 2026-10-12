<?php

/** @var yii\web\View $this */
/** @var array $rangeSummary */
/** @var string $mode — 'week' or 'month' */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$titleMap = ['week' => Yii::t('app', '周汇总'), 'month' => Yii::t('app', '月汇总')];
$this->title = $titleMap[$mode] ?? Yii::t('app', '日报汇总');
$this->params['breadcrumbs'][] = $this->title;

$rangeLabel = $rangeSummary['date_from'] . ' ~ ' . $rangeSummary['date_to'];
?>

<div class="card summary-panel">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode($this->title . ' — ' . $rangeLabel) ?></h3>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => [$mode]]); ?>
        <div class="form-row align-items-end">
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '开始日期') ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= Html::encode($rangeSummary['date_from']) ?>">
            </div>
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '结束日期') ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= Html::encode($rangeSummary['date_to']) ?>">
            </div>
            <div class="col-auto">
                <?= Html::submitButton(Yii::t('app', '查询'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <div class="table-responsive table-responsive-wide">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '日期') ?></th>
                    <th class="text-right"><?= Yii::t('app', '毛利') ?></th>
                    <th class="text-right"><?= Yii::t('app', '卡主应付') ?></th>
                    <th class="text-right"><?= Yii::t('app', '纯利') ?></th>
                    <th class="text-right"><?= Yii::t('app', '刷卡升数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '刷卡笔数') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rangeSummary['days'])): ?>
                    <tr><td colspan="6" class="text-center text-muted"><?= Yii::t('app', '暂无数据') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($rangeSummary['days'] as $day): ?>
                    <tr>
                        <td><?= Html::encode($day['work_date']) ?></td>
                        <td class="text-right"><?= number_format($day['gross'], 2) ?></td>
                        <td class="text-right text-danger">−<?= number_format($day['owner_payout'], 2) ?></td>
                        <td class="text-right font-weight-bold text-success"><?= number_format($day['net'], 2) ?></td>
                        <td class="text-right"><?= number_format($day['swipe_liters'], 3) ?></td>
                        <td class="text-right"><?= (int) $day['swipe_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($rangeSummary['days'])): ?>
                <tfoot>
                <tr class="font-weight-bold">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <td class="text-right"><?= number_format($rangeSummary['totals']['gross'], 2) ?></td>
                    <td class="text-right text-danger">−<?= number_format($rangeSummary['totals']['owner_payout'], 2) ?></td>
                    <td class="text-right text-success"><?= number_format($rangeSummary['totals']['net'], 2) ?></td>
                    <td class="text-right"><?= number_format($rangeSummary['totals']['swipe_liters'], 3) ?></td>
                    <td class="text-right"><?= (int) $rangeSummary['totals']['swipe_count'] ?></td>
                </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <h5 class="mt-4 mb-3"><?= Yii::t('app', '按公司') ?></h5>
        <div class="table-responsive table-responsive-wide">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '公司') ?></th>
                    <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '应收') ?></th>
                    <th class="text-right"><?= Yii::t('app', '笔数') ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($companyRange['companies'])): ?>
                    <tr><td colspan="5" class="text-center text-muted"><?= Yii::t('app', '暂无数据') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($companyRange['companies'] as $company): ?>
                    <tr>
                        <td><?= Html::encode($company['company_name']) ?></td>
                        <td class="text-right"><?= number_format($company['liters'], 3) ?></td>
                        <td class="text-right"><?= number_format($company['amount_due'], 2) ?></td>
                        <td class="text-right"><?= (int) $company['count'] ?></td>
                        <td>
                            <?= Html::button(Yii::t('app', '查看明细'), [
                                'class' => 'btn btn-outline-secondary btn-xs btn-sm gsl-detail-modal-trigger',
                                'data-url' => Url::to(['company-details', 'company_id' => $company['company_id'], 'date_from' => $rangeSummary['date_from'], 'date_to' => $rangeSummary['date_to']]),
                                'data-title' => Yii::t('app', '{label} — {from} ~ {to} 每日汇总', [
                                    'label' => $company['company_name'],
                                    'from' => $rangeSummary['date_from'],
                                    'to' => $rangeSummary['date_to'],
                                ]),
                            ]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($companyRange['companies'])): ?>
                <tfoot>
                <tr class="font-weight-bold">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <td class="text-right"><?= number_format($companyRange['totals']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($companyRange['totals']['amount_due'], 2) ?></td>
                    <td class="text-right"><?= (int) $companyRange['totals']['count'] ?></td>
                    <td></td>
                </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
