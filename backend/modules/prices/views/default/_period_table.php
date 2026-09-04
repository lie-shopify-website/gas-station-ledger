<?php

/** @var common\models\GslPricePeriod[] $monthPeriods */
/** @var string $today */
/** @var int|null $activePeriodId */
/** @var bool $canWrite */

use yii\helpers\Html;

?>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm mb-0">
        <thead>
        <tr>
            <th><?= Yii::t('app', '生效日期') ?></th>
            <th class="text-right"><?= Yii::t('app', '挂牌价') ?></th>
            <th class="text-right"><?= Yii::t('app', '折扣数') ?></th>
            <th><?= Yii::t('app', '状态') ?></th>
            <th></th>
            <?php if ($canWrite): ?><th></th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($monthPeriods as $period): ?>
        <?php
        $isActive = $period->valid_from <= $today && $period->valid_to >= $today;
        $discountCount = count($period->discounts);
        $collapseId = 'price-period-' . $period->id;
        $shouldExpand = $activePeriodId === $period->id;
        ?>
        <tr class="<?= $isActive ? 'table-info' : '' ?>">
            <td><?= Html::encode($period->valid_from) ?> ~ <?= Html::encode($period->valid_to) ?></td>
            <td class="text-right"><?= number_format($period->list_price, 2) ?></td>
            <td class="text-right"><?= $discountCount ?></td>
            <td>
                <?php if ($isActive): ?>
                <span class="badge badge-success"><?= Yii::t('app', '当前生效') ?></span>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <?= Html::button(Yii::t('app', '查看折扣'), [
                    'class' => 'btn btn-outline-secondary btn-sm toggle-price-discounts',
                    'data-target' => '#' . $collapseId,
                    'aria-expanded' => $shouldExpand ? 'true' : 'false',
                ]) ?>
            </td>
            <?php if ($canWrite): ?>
            <td class="text-nowrap">
                <?= Html::a(Yii::t('app', '编辑'), ['update-period', 'id' => $period->id], ['class' => 'btn btn-xs btn-primary']) ?>
                <?= Html::a(Yii::t('app', '新增折扣'), ['create-discount', 'period_id' => $period->id], ['class' => 'btn btn-xs btn-success']) ?>
            </td>
            <?php endif; ?>
        </tr>
        <tr class="collapse price-discount-row<?= $shouldExpand ? ' show' : '' ?>" id="<?= $collapseId ?>">
            <td colspan="<?= $canWrite ? 6 : 5 ?>" class="p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 bg-light">
                        <thead>
                        <tr>
                            <th><?= Yii::t('app', '公司') ?></th>
                            <th class="text-right"><?= Yii::t('app', '折扣价') ?></th>
                            <?php if ($canWrite): ?><th></th><?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($period->discounts)): ?>
                        <tr>
                            <td colspan="<?= $canWrite ? 3 : 2 ?>" class="text-muted text-center"><?= Yii::t('app', '暂无折扣价') ?></td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($period->discounts as $discount): ?>
                        <tr>
                            <td><?= Html::encode($discount->company->name ?? '—') ?></td>
                            <td class="text-right"><?= number_format($discount->discount_price, 2) ?></td>
                            <?php if ($canWrite): ?>
                            <td class="text-right">
                                <?= Html::a(Yii::t('app', '编辑'), ['update-discount', 'id' => $discount->id], ['class' => 'btn btn-xs btn-primary']) ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
