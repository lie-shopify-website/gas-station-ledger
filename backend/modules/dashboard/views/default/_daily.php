<?php

/** @var array $breakdown */

use yii\helpers\Html;
use yii\helpers\Url;

$days = $breakdown['days'];
$isCounter = $breakdown['metric'] === 'counter';
$colspan = $isCounter ? 4 : 2;
?>
<?php if ($isCounter): ?>
<div class="mb-3">
    <?= Html::button(Yii::t('app', '返回'), [
        'type' => 'button',
        'class' => 'btn btn-outline-secondary btn-sm gsl-detail-modal-trigger',
        'data-url' => Url::to(['/dashboard/default/counters', 'month' => $breakdown['month']]),
        'data-title' => Yii::t('app', '柜台月汇总 — {month}', ['month' => $breakdown['month']]),
    ]) ?>
</div>
<?php endif; ?>
<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
        <thead>
        <tr>
            <th><?= Yii::t('app', '工作日期') ?></th>
            <?php if ($isCounter): ?>
                <th class="text-right"><?= Yii::t('app', '票数') ?></th>
                <th class="text-right"><?= Yii::t('app', '升数') ?></th>
                <th class="text-right"><?= Yii::t('app', '挂牌金额') ?></th>
            <?php else: ?>
                <th class="text-right"><?= Html::encode($breakdown['label']) ?></th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($days)): ?>
            <tr>
                <td colspan="<?= $colspan ?>" class="text-center text-muted"><?= Yii::t('app', '无有效日数据') ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($days as $day): ?>
                <tr>
                    <td><?= Html::encode($day['work_date']) ?></td>
                    <?php if ($isCounter): ?>
                        <td class="text-right"><?= (int) $day['count'] ?></td>
                        <td class="text-right"><?= number_format($day['liters'], 3) ?></td>
                        <td class="text-right"><?= number_format($day['list_amount'], 2) ?></td>
                    <?php else: ?>
                        <td class="text-right"><?= $breakdown['decimals'] === 0
                            ? (int) $day['value']
                            : number_format($day['value'], $breakdown['decimals']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($days)): ?>
            <tfoot>
            <tr class="font-weight-bold">
                <td><?= Yii::t('app', '合计') ?></td>
                <?php if ($isCounter): ?>
                    <td class="text-right"><?= (int) $breakdown['totals']['count'] ?></td>
                    <td class="text-right"><?= number_format($breakdown['totals']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($breakdown['totals']['list_amount'], 2) ?></td>
                <?php else: ?>
                    <td class="text-right"><?= $breakdown['decimals'] === 0
                        ? (int) $breakdown['totals']['value']
                        : number_format($breakdown['totals']['value'], $breakdown['decimals']) ?></td>
                <?php endif; ?>
            </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>
