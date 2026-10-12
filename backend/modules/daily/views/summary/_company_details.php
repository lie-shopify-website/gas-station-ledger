<?php

/** @var yii\web\View $this */
/** @var array $days */
/** @var array $totals */

use yii\helpers\Html;

?>

<div class="table-responsive table-responsive-wide">
    <table class="table table-sm table-bordered mb-0">
        <thead>
        <tr>
            <th><?= Yii::t('app', '日期') ?></th>
            <th class="text-right"><?= Yii::t('app', '升数') ?></th>
            <th class="text-right"><?= Yii::t('app', '应收') ?></th>
            <th class="text-right"><?= Yii::t('app', '笔数') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($days)): ?>
            <tr><td colspan="4" class="text-center text-muted"><?= Yii::t('app', '无明细数据') ?></td></tr>
        <?php else: ?>
            <?php foreach ($days as $day): ?>
            <tr>
                <td><?= Html::encode($day['work_date']) ?></td>
                <td class="text-right"><?= number_format($day['liters'], 3) ?></td>
                <td class="text-right"><?= number_format($day['amount_due'], 2) ?></td>
                <td class="text-right"><?= (int) $day['count'] ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($days)): ?>
        <tfoot>
        <tr class="font-weight-bold">
            <td><?= Yii::t('app', '合计') ?></td>
            <td class="text-right"><?= number_format($totals['liters'], 3) ?></td>
            <td class="text-right"><?= number_format($totals['amount_due'], 2) ?></td>
            <td class="text-right"><?= (int) $totals['count'] ?></td>
        </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
