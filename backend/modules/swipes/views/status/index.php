<?php



/** @var yii\web\View $this */

/** @var array $status */

/** @var common\models\GslSwipe[] $swipes */

/** @var string $month */



use yii\helpers\Html;

use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '刷卡状态');

$this->params['breadcrumbs'][] = $this->title;

?>

<div id="swipes-detail-content" class="d-none">

    <div class="table-responsive">

        <table class="table table-bordered table-striped table-sm mb-0">

            <thead>

            <tr>

                <th><?= Yii::t('app', '油卡') ?></th>

                <th class="text-right"><?= Yii::t('app', '月配额') ?></th>

                <th class="text-right"><?= Yii::t('app', '已用') ?></th>

                <th class="text-right"><?= Yii::t('app', '剩余') ?></th>

            </tr>

            </thead>

            <tbody>

            <?php foreach ($status['cards'] as $card): ?>

            <tr>

                <td><?= Html::encode($card['card_code']) ?></td>

                <td class="text-right"><?= number_format($card['quota'], 3) ?></td>

                <td class="text-right"><?= number_format($card['used'], 3) ?></td>

                <td class="text-right"><?= number_format($card['remaining'], 3) ?></td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>



    <hr>



    <div class="table-responsive">

        <table class="table table-bordered table-striped table-sm mb-0">

            <thead>

            <tr>

                <th><?= Yii::t('app', '日期') ?></th>

                <th><?= Yii::t('app', '公司') ?></th>

                <th><?= Yii::t('app', '油卡') ?></th>

                <th class="text-right"><?= Yii::t('app', '升数') ?></th>

                <th><?= Yii::t('app', '回单号') ?></th>

                <th><?= Yii::t('app', '备注') ?></th>

            </tr>

            </thead>

            <tbody>

            <?php if (empty($swipes)): ?>

            <tr><td colspan="6" class="text-center text-muted"><?= Yii::t('app', '无刷卡记录') ?></td></tr>

            <?php else: ?>

            <?php foreach ($swipes as $swipe): ?>

            <tr>

                <td><?= Html::encode($swipe->work_date) ?></td>

                <td><?= Html::encode($swipe->company->name ?? '—') ?></td>

                <td><?= Html::encode($swipe->card->card_code ?? '—') ?></td>

                <td class="text-right"><?= number_format($swipe->liters, 3) ?></td>

                <td><?= Html::encode($swipe->swipe_receipt) ?></td>

                <td><?= Html::encode($swipe->note) ?></td>

            </tr>

            <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>



<div class="card summary-panel">

    <div class="card-header">

        <h3 class="card-title"><?= Html::encode(Yii::t('app', '刷卡汇总 — {month}', ['month' => $month])) ?></h3>

    </div>

    <div class="card-body">

        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]); ?>

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



        <div class="row gsl-info-boxes">

            <div class="col-12 col-md-4">

                <div class="info-box bg-info mb-0">

                    <span class="info-box-icon"><i class="fas fa-tint"></i></span>

                    <div class="info-box-content">

                        <span class="info-box-text"><?= Yii::t('app', '加油升数') ?></span>

                        <span class="info-box-number"><?= number_format($status['fill_liters'], 3) ?></span>

                    </div>

                </div>

            </div>

            <div class="col-12 col-md-4">

                <div class="info-box bg-success mb-0">

                    <span class="info-box-icon"><i class="fas fa-credit-card"></i></span>

                    <div class="info-box-content">

                        <span class="info-box-text"><?= Yii::t('app', '已刷升数') ?></span>

                        <span class="info-box-number"><?= number_format($status['swiped_liters'], 3) ?></span>

                    </div>

                </div>

            </div>

            <div class="col-12 col-md-4">

                <div class="info-box bg-warning mb-0">

                    <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>

                    <div class="info-box-content">

                        <span class="info-box-text"><?= Yii::t('app', '待刷升数') ?></span>

                        <span class="info-box-number"><?= number_format($status['left_liters'], 3) ?></span>

                    </div>

                </div>

            </div>

        </div>



        <?= Html::button(Yii::t('app', '查看明细'), [

            'class' => 'btn btn-outline-secondary btn-sm gsl-detail-modal-trigger',

            'data-content' => '#swipes-detail-content',

            'data-title' => Yii::t('app', '刷卡明细 — {month}', ['month' => $month]),

        ]) ?>

    </div>

</div>
