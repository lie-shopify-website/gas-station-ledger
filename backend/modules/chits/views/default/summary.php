<?php

/** @var yii\web\View $this */
/** @var string $month */
/** @var array $daily */
/** @var array $monthly */
/** @var array $balances */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '油票汇总');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', '使用记录'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['summary']]); ?>
        <div class="form-row align-items-end">
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '月份') ?></label>
                <input type="month" name="month" class="form-control" value="<?= Html::encode($month) ?>">
            </div>
            <div class="col-auto">
                <?= Html::submitButton(Yii::t('app', '查询'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <h5 class="mt-4 mb-3"><?= Yii::t('app', '每日消费明细') ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '客户') ?></th>
                    <?php foreach ($daily['dates'] as $date): ?>
                        <th class="text-right" style="white-space:nowrap;"><?= substr($date, 5) ?></th>
                    <?php endforeach; ?>
                    <th class="text-right"><?= Yii::t('app', '合计') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($daily['customers'])): ?>
                    <tr><td colspan="<?= count($daily['dates']) + 2 ?>" class="text-center text-muted"><?= Yii::t('app', '暂无数据') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($daily['customers'] as $cust): ?>
                    <tr>
                        <td><?= Html::encode($cust['name']) ?></td>
                        <?php foreach ($daily['dates'] as $date): ?>
                            <?php $val = $daily['matrix'][$cust['id']][$date] ?? 0.0; ?>
                            <td class="text-right"><?= $val > 0 ? number_format($val, 2) : '' ?></td>
                        <?php endforeach; ?>
                        <td class="text-right font-weight-bold"><?= number_format($daily['rowTotals'][$cust['id']] ?? 0.0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($daily['customers'])): ?>
                <tfoot>
                <tr class="font-weight-bold">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <?php foreach ($daily['dates'] as $date): ?>
                        <td class="text-right"><?= ($daily['colTotals'][$date] ?? 0) > 0 ? number_format($daily['colTotals'][$date], 2) : '' ?></td>
                    <?php endforeach; ?>
                    <td class="text-right"><?= number_format($daily['grandTotal'], 2) ?></td>
                </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <h5 class="mt-4 mb-3"><?= Yii::t('app', '月度消费汇总') ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '客户') ?></th>
                    <th class="text-right"><?= Yii::t('app', '笔数') ?></th>
                    <th class="text-right"><?= Yii::t('app', '消费金额') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($monthly['rows'])): ?>
                    <tr><td colspan="3" class="text-center text-muted"><?= Yii::t('app', '暂无数据') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($monthly['rows'] as $row): ?>
                    <tr>
                        <td><?= Html::encode($row['name']) ?></td>
                        <td class="text-right"><?= (int) $row['count'] ?></td>
                        <td class="text-right"><?= number_format($row['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($monthly['rows'])): ?>
                <tfoot>
                <tr class="font-weight-bold">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <td class="text-right"><?= (int) $monthly['totals']['count'] ?></td>
                    <td class="text-right"><?= number_format($monthly['totals']['amount'], 2) ?></td>
                </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <h5 class="mt-4 mb-3"><?= Yii::t('app', '客户余额') ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '客户') ?></th>
                    <th class="text-right"><?= Yii::t('app', '预存总额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '消费总额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '余额') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($balances['rows'])): ?>
                    <tr><td colspan="4" class="text-center text-muted"><?= Yii::t('app', '暂无数据') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($balances['rows'] as $row): ?>
                    <tr>
                        <td><?= Html::encode($row['name']) ?></td>
                        <td class="text-right"><?= number_format($row['deposit'], 2) ?></td>
                        <td class="text-right text-danger"><?= number_format($row['used'], 2) ?></td>
                        <td class="text-right font-weight-bold <?= $row['balance'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($row['balance'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($balances['rows'])): ?>
                <tfoot>
                <tr class="font-weight-bold">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <td class="text-right"><?= number_format($balances['totals']['deposit'], 2) ?></td>
                    <td class="text-right text-danger"><?= number_format($balances['totals']['used'], 2) ?></td>
                    <td class="text-right <?= $balances['totals']['balance'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($balances['totals']['balance'], 2) ?></td>
                </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
