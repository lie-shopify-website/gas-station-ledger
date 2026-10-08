<?php

/** @var yii\web\View $this */
/** @var string $from */
/** @var string $to */
/** @var array<int, array{card: common\models\GslCard, rate: float, filled: bool}> $rows */
/** @var common\models\GslCardRate[] $history */
/** @var bool $canWrite */

use common\models\GslCard;
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
                <span class="badge badge-warning"><?= Yii::t('app', '{count} 张卡未填', ['count' => $missing]) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-muted small mt-2 mb-0">
            <?= Yii::t('app', '每周四填写下一周费率；刷卡时按刷卡当天日期自动取该卡当期费率，并记入刷卡记录。可超刷，不设上限。') ?>
        </p>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0"><?= Yii::t('app', '本周费率（按卡）') ?></h3>
    </div>
    <?php if ($canWrite): ?>
    <?= Html::beginForm(['save'], 'post') ?>
    <?= Html::hiddenInput('effective_from', $from) ?>
    <?= Html::hiddenInput('effective_to', $to) ?>
    <datalist id="card-owner-names">
        <?php foreach (GslCard::ownerNameOptions() as $name): ?>
        <option value="<?= Html::encode($name) ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <?php endif; ?>
    <div class="card-body p-0">
        <div class="table-responsive table-responsive-wide">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '卡号') ?></th>
                    <th><?= Yii::t('app', '卡类型') ?></th>
                    <th><?= Yii::t('app', '卡所属人') ?></th>
                    <th class="text-right"><?= Yii::t('app', '可刷升数') ?></th>
                    <th class="text-right" style="width:160px"><?= Yii::t('app', '费率') ?></th>
                    <th><?= Yii::t('app', '状态') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted"><?= Yii::t('app', '暂无启用的油卡') ?></td></tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <?php $card = $row['card']; ?>
                <tr class="<?= $row['filled'] ? '' : 'table-warning' ?>">
                    <td><?= Html::encode($card->card_code) ?></td>
                    <td><?= $card->getCardTypeName() !== '' ? Html::encode($card->getCardTypeName()) : '—' ?></td>
                    <td style="width:200px">
                        <?php if ($canWrite): ?>
                        <input type="text" class="form-control form-control-sm" maxlength="64"
                               list="card-owner-names" autocomplete="off"
                               name="owner_name[<?= (int) $card->id ?>]"
                               value="<?= Html::encode((string) $card->owner_name) ?>">
                        <?php else: ?>
                        <?= $card->owner_name !== null && $card->owner_name !== '' ? Html::encode($card->owner_name) : '—' ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?= number_format((float) $card->monthly_quota, 3) ?></td>
                    <td class="text-right">
                        <?php if ($canWrite): ?>
                        <input type="number" step="0.01" class="form-control form-control-sm text-right"
                               name="rate[<?= (int) $card->id ?>]" value="<?= number_format($row['rate'], 2, '.', '') ?>">
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
                    <th><?= Yii::t('app', '卡号') ?></th>
                    <th><?= Yii::t('app', '卡所属人') ?></th>
                    <th class="text-right"><?= Yii::t('app', '费率') ?></th>
                    <th><?= Yii::t('app', '备注') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($history)): ?>
                <tr><td colspan="5" class="text-center text-muted"><?= Yii::t('app', '暂无费率记录') ?></td></tr>
                <?php else: ?>
                <?php foreach ($history as $rate): ?>
                <tr>
                    <td><?= Html::encode($rate->effective_from) ?> ~ <?= Html::encode($rate->effective_to) ?></td>
                    <td><?= Html::encode($rate->card->card_code ?? '—') ?></td>
                    <td><?= Html::encode($rate->card->owner_name ?? '') ?: '—' ?></td>
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
