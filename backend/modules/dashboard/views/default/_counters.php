<?php

/** @var array $counterMonthly */
/** @var string $month */

use yii\helpers\Html;
use yii\helpers\Url;

?>
<div class="mb-3 text-right">
    <?= Html::button(Yii::t('app', '每日汇总'), [
        'type' => 'button',
        'class' => 'btn btn-outline-secondary btn-sm gsl-detail-modal-trigger',
        'data-url' => Url::to(['/dashboard/default/daily', 'month' => $month, 'metric' => 'counter']),
        'data-title' => Yii::t('app', '{label} — {month} 每日汇总', [
            'label' => Yii::t('app', '柜台月汇总'),
            'month' => $month,
        ]),
    ]) ?>
</div>
<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
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
