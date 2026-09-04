<?php



/** @var common\models\GslFill[] $fills */



use yii\helpers\Html;

?>

<div class="table-responsive table-responsive-wide">

    <table class="table table-sm table-bordered mb-0">

        <thead>

        <tr>

            <th><?= Yii::t('app', '车牌') ?></th>

            <th><?= Yii::t('app', '柜台') ?></th>

            <th class="text-right"><?= Yii::t('app', '升数') ?></th>

            <th class="text-right"><?= Yii::t('app', '优惠价格') ?></th>

            <th class="text-right"><?= Yii::t('app', '应收') ?></th>

            <th><?= Yii::t('app', '票号') ?></th>

            <th><?= Yii::t('app', '备注') ?></th>

        </tr>

        </thead>

        <tbody>

        <?php if (empty($fills)): ?>

        <tr><td colspan="7" class="text-center text-muted"><?= Yii::t('app', '无明细数据') ?></td></tr>

        <?php else: ?>

        <?php foreach ($fills as $fill): ?>

        <tr>

            <td><?= Html::encode($fill->plate->plate_no ?? '—') ?></td>

            <td><?= Html::encode($fill->counter->code ?? '—') ?></td>

            <td class="text-right"><?= number_format($fill->liters, 3) ?></td>

            <td class="text-right"><?= $fill->discount_price !== null ? number_format($fill->discount_price, 2) : '—' ?></td>

            <td class="text-right"><?= number_format($fill->amount_due, 2) ?></td>

            <td><?= Html::encode($fill->ticket_no) ?></td>

            <td><?= Html::encode($fill->note) ?></td>

        </tr>

        <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

