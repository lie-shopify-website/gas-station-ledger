<?php



/** @var yii\web\View $this */

/** @var array $summary */



use yii\helpers\Html;

use yii\helpers\Url;

use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '日报汇总');

$this->params['breadcrumbs'][] = $this->title;

$workDate = $summary['work_date'];

?>

<div class="card summary-panel">

    <div class="card-header">

        <h3 class="card-title"><?= Html::encode(Yii::t('app', '汇总 — {date}', ['date' => $workDate])) ?></h3>

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

        </div>

        <?php ActiveForm::end(); ?>



        <div class="table-responsive table-responsive-wide">

            <table class="table table-bordered table-striped table-sm">

                <thead>

                <tr>

                    <th><?= Yii::t('app', '公司') ?></th>

                    <th><?= Yii::t('app', '付款方式') ?></th>

                    <th class="text-right"><?= Yii::t('app', '优惠价格') ?></th>

                    <th class="text-right"><?= Yii::t('app', '升数') ?></th>

                    <th class="text-right"><?= Yii::t('app', '应收') ?></th>

                    <th class="text-right"><?= Yii::t('app', '成本') ?></th>

                    <th class="text-right"><?= Yii::t('app', '笔数') ?></th>

                    <th></th>

                </tr>

                </thead>

                <tbody>

                <?php foreach ($summary['companies'] as $row): ?>

                <tr>

                    <td><?= Html::encode($row['company_name']) ?></td>

                    <td><?= Html::encode($row['payment_type']) ?></td>

                    <td class="text-right"><?= $row['discount_price'] !== null ? number_format($row['discount_price'], 2) : '—' ?></td>

                    <td class="text-right"><?= number_format($row['liters'], 3) ?></td>

                    <td class="text-right"><?= number_format($row['amount_due'], 2) ?></td>

                    <td class="text-right"><?= number_format($row['cost'], 2) ?></td>

                    <td class="text-right"><?= (int) $row['count'] ?></td>

                    <td>

                        <?= Html::button(Yii::t('app', '查看明细'), [

                            'class' => 'btn btn-outline-secondary btn-xs btn-sm gsl-detail-modal-trigger',

                            'data-url' => Url::to(['details', 'company_id' => $row['company_id'], 'work_date' => $workDate]),

                            'data-title' => Yii::t('app', '{label} — {date} 明细', [

                                'label' => $row['company_name'],

                                'date' => $workDate,

                            ]),

                        ]) ?>

                    </td>

                </tr>

                <?php endforeach; ?>

                </tbody>

                <tfoot>

                <tr class="font-weight-bold">

                    <td colspan="2"><?= Yii::t('app', '合计') ?></td>

                    <td></td>

                    <td class="text-right"><?= number_format($summary['totals']['liters'], 3) ?></td>

                    <td class="text-right"><?= number_format($summary['totals']['amount_due'], 2) ?></td>

                    <td class="text-right"><?= number_format($summary['totals']['cost'], 2) ?></td>

                    <td class="text-right"><?= (int) $summary['totals']['count'] ?></td>

                    <td></td>

                </tr>

                </tfoot>

            </table>

        </div>



        <div class="summary-totals-panel p-3 border rounded bg-light">

            <h5 class="font-weight-bold mb-3"><?= Yii::t('app', '合计') ?></h5>

            <div class="summary-totals-list">

                <div class="summary-totals-item">

                    <span><?= Yii::t('app', '升数') ?></span>

                    <strong><?= number_format($summary['totals']['liters'], 3) ?></strong>

                </div>

                <div class="summary-totals-item">

                    <span><?= Yii::t('app', '应收') ?></span>

                    <strong><?= number_format($summary['totals']['amount_due'], 2) ?></strong>

                </div>

                <div class="summary-totals-item">

                    <span><?= Yii::t('app', '成本') ?></span>

                    <strong><?= number_format($summary['totals']['cost'], 2) ?></strong>

                </div>

                <div class="summary-totals-item mb-0">

                    <span><?= Yii::t('app', '笔数') ?></span>

                    <strong><?= (int) $summary['totals']['count'] ?></strong>

                </div>

            </div>

        </div>

    </div>

</div>
