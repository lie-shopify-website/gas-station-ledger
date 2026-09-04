<?php

/** @var common\models\GslFill[] $fills */

use yii\helpers\Html;

?>
<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
        <thead>
        <tr>
            <th><?= Yii::t('app', '公司') ?></th>
            <th><?= Yii::t('app', '车牌') ?></th>
            <th class="text-right"><?= Yii::t('app', '升数') ?></th>
            <th class="text-right"><?= Yii::t('app', '挂牌价格') ?></th>
            <th class="text-right"><?= Yii::t('app', '挂牌金额') ?></th>
            <th><?= Yii::t('app', '票号') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($fills)): ?>
        <tr><td colspan="6" class="text-center text-muted"><?= Yii::t('app', '无明细数据') ?></td></tr>
        <?php else: ?>
        <?php foreach ($fills as $fill): ?>
        <tr>
            <td><?= Html::encode($fill->company->name ?? '—') ?></td>
            <td><?= Html::encode($fill->plate->plate_no ?? '—') ?></td>
            <td class="text-right"><?= number_format($fill->liters, 3) ?></td>
            <td class="text-right"><?= $fill->list_price !== null ? number_format($fill->list_price, 2) : '—' ?></td>
            <td class="text-right"><?= $fill->list_amount !== null ? number_format($fill->list_amount, 2) : '—' ?></td>
            <td><?= Html::encode($fill->ticket_no) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
