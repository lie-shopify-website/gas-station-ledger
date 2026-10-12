<?php

/** @var yii\web\View $this */
/** @var common\models\GslChitTopup[] $topups */
/** @var float $total */
/** @var bool $canWrite */

use yii\helpers\Html;

$this->title = Yii::t('app', '预存记录');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
        <?php if ($canWrite): ?>
        <div class="card-tools">
            <?= Html::a('<i class="fas fa-plus"></i> ' . Yii::t('app', '新增'), ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '序号') ?></th>
                    <th><?= Yii::t('app', '预存日期') ?></th>
                    <th><?= Yii::t('app', '客户名称') ?></th>
                    <th class="text-right"><?= Yii::t('app', '预存金额') ?></th>
                    <th><?= Yii::t('app', '备注') ?></th>
                    <?php if ($canWrite): ?><th></th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($topups)): ?>
                <tr><td colspan="<?= $canWrite ? 6 : 5 ?>" class="text-muted text-center"><?= Yii::t('app', '暂无预存记录') ?></td></tr>
                <?php else: ?>
                <?php foreach ($topups as $i => $topup): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= Html::encode($topup->topup_date) ?></td>
                    <td><?= Html::encode($topup->customer ? $topup->customer->name : '') ?></td>
                    <td class="text-right"><?= Yii::$app->formatter->asDecimal((float) $topup->amount, 2) ?></td>
                    <td><?= Html::encode($topup->note) ?></td>
                    <?php if ($canWrite): ?>
                    <td class="text-right chit-topup-row-actions">
                        <?= Html::a(Yii::t('app', '编辑'), ['update', 'id' => $topup->id], ['class' => 'btn btn-xs btn-primary']) ?>
                        <?= Html::a(Yii::t('app', '删除'), ['delete', 'id' => $topup->id], [
                            'class' => 'btn btn-xs btn-danger',
                            'data' => ['method' => 'post', 'confirm' => Yii::t('app', '确定删除此预存记录？')],
                        ]) ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-right"><strong><?= Yii::t('app', '合计') ?></strong></td>
                    <td class="text-right"><strong><?= Yii::$app->formatter->asDecimal($total, 2) ?></strong></td>
                    <td colspan="<?= $canWrite ? 2 : 1 ?>"></td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$this->registerCss('.chit-topup-row-actions a{margin-left:8px}');
?>