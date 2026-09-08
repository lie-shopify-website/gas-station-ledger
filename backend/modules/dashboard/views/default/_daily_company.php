<?php

/** @var array $dailyCompany */

use yii\helpers\Html;

$days = $dailyCompany['days'] ?? [];
?>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm mb-0">
        <thead>
        <tr>
            <th><?= Yii::t('app', '工作日期') ?></th>
            <th><?= Yii::t('app', '公司') ?></th>
            <th><?= Yii::t('app', '付款方式') ?></th>
            <th class="text-right"><?= Yii::t('app', '票数') ?></th>
            <th class="text-right"><?= Yii::t('app', '升数') ?></th>
            <th class="text-right"><?= Yii::t('app', '应收') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($days)): ?>
            <tr>
                <td colspan="6" class="text-center text-muted"><?= Yii::t('app', '无有效日数据') ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($days as $day): ?>
                <?php foreach ($day['companies'] as $index => $row): ?>
                    <tr>
                        <td><?= $index === 0 ? Html::encode($day['work_date']) : '' ?></td>
                        <td><?= Html::encode($row['company_name']) ?></td>
                        <td><?= Html::encode($row['payment_type']) ?></td>
                        <td class="text-right"><?= (int) $row['count'] ?></td>
                        <td class="text-right"><?= number_format($row['liters'], 3) ?></td>
                        <td class="text-right"><?= number_format($row['amount_due'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="font-weight-bold table-light">
                    <td><?= Html::encode($day['work_date']) ?></td>
                    <td colspan="2"><?= Yii::t('app', '当日合计') ?></td>
                    <td class="text-right"><?= (int) $day['totals']['count'] ?></td>
                    <td class="text-right"><?= number_format($day['totals']['liters'], 3) ?></td>
                    <td class="text-right"><?= number_format($day['totals']['amount_due'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($days)): ?>
            <tfoot>
            <tr class="font-weight-bold">
                <td colspan="3"><?= Yii::t('app', '合计') ?></td>
                <td class="text-right"><?= (int) $dailyCompany['totals']['count'] ?></td>
                <td class="text-right"><?= number_format($dailyCompany['totals']['liters'], 3) ?></td>
                <td class="text-right"><?= number_format($dailyCompany['totals']['amount_due'], 2) ?></td>
            </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>
