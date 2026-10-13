<?php

/** @var yii\web\View $this */
/** @var string $from */
/** @var string $to */
/** @var array<int, array{owner_name:string, rate:float, filled:bool}> $rows */
/** @var common\models\GslCardRate[] $history */
/** @var bool $canWrite */

use yii\helpers\Html;

$this->title = Yii::t('app', '卡费率');
$this->params['breadcrumbs'][] = $this->title;

$today = date('Y-m-d');
$isCurrentWeek = $today >= $from && $today <= $to;
$prevWeek = date('Y-m-d', strtotime($from . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($from . ' +7 days'));
$missing = 0;
foreach ($rows as $row) {
    if (!$row['filled']) {
        $missing++;
    }
}
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
    </div>
    <div class="card-body">
        <div class="form-row align-items-end">
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '所在周（周四 ~ 周三）') ?></label>
                <input type="date" class="form-control" value="<?= Html::encode($from) ?>"
                       onchange="location.href='<?= \yii\helpers\Url::to(['index']) ?>?week='+this.value;">
            </div>
            <div class="col-auto">
                <?= Html::a(Yii::t('app', '上一周'), ['index', 'week' => $prevWeek], ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::a(Yii::t('app', '下一周'), ['index', 'week' => $nextWeek], ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::a(Yii::t('app', '本周'), ['index', 'week' => $today], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
            <div class="col-auto align-self-center">
                <span class="badge badge-info"><?= Html::encode($from) ?> ~ <?= Html::encode($to) ?></span>
                <?php if ($isCurrentWeek): ?>
                <span class="badge badge-success"><?= Yii::t('app', '当前生效') ?></span>
                <?php endif; ?>
                <?php if ($missing > 0): ?>
                <span class="badge badge-warning"><?= Yii::t('app', '{count} 个客户未填', ['count' => $missing]) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-muted small mt-2 mb-0">
            <?= Yii::t('app', '每周四填写下一周费率；刷卡时按刷卡当天日期自动取该客户当期费率，并记入刷卡记录。可超刷，不设上限。') ?>
        </p>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= Yii::t('app', '本周费率（按客户）') ?></h3>
    </div>
    <?php if ($canWrite): ?>
    <?= Html::beginForm(['save'], 'post') ?>
    <?= Html::hiddenInput('effective_from', $from) ?>
    <?= Html::hiddenInput('effective_to', $to) ?>
    <?php endif; ?>
    <div class="card-body p-0">
        <div class="table-responsive table-responsive-wide">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '卡所属人') ?></th>
                    <th class="text-right" style="width:160px"><?= Yii::t('app', '费率') ?></th>
                    <th><?= Yii::t('app', '状态') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="3" class="text-center text-muted"><?= Yii::t('app', '暂无客户') ?></td></tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr class="<?= $row['filled'] ? '' : 'table-warning' ?>">
                    <td><?= Html::encode($row['owner_name']) ?></td>
                    <td class="text-right">
                        <?php if ($canWrite): ?>
                        <input type="number" step="0.01" class="form-control form-control-sm text-right"
                               name="rate[<?= Html::encode($row['owner_name']) ?>]" value="<?= number_format($row['rate'], 2, '.', '') ?>">
                        <?php else: ?>
                        <?= number_format($row['rate'], 2) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['filled']): ?>
                        <span class="badge badge-success"><?= Yii::t('app', '已填') ?></span>
                        <?php else: ?>
                        <span class="badge badge-warning"><?= Yii::t('app', '未填') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="card-footer">
        <?= Html::submitButton(Yii::t('app', '保存本周费率'), ['class' => 'btn btn-success']) ?>
    </div>
    <?= Html::endForm() ?>
    <?php endif; ?>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= Yii::t('app', '费率历史') ?></h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive table-responsive-wide">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '生效周') ?></th>
                    <th><?= Yii::t('app', '卡所属人') ?></th>
                    <th class="text-right"><?= Yii::t('app', '费率') ?></th>
                    <th><?= Yii::t('app', '备注') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($history)): ?>
                <tr><td colspan="4" class="text-center text-muted"><?= Yii::t('app', '暂无费率记录') ?></td></tr>
                <?php else: ?>
                <?php foreach ($history as $rate): ?>
                <tr>
                    <td><?= Html::encode($rate->effective_from) ?> ~ <?= Html::encode($rate->effective_to) ?></td>
                    <td><?= Html::encode($rate->owner_name) ?></td>
                    <td class="text-right"><?= number_format((float) $rate->rate, 2) ?></td>
                    <td><?= Html::encode($rate->note) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
