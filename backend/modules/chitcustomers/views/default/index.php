<?php

/** @var yii\web\View $this */
/** @var common\models\GslChitCustomer[] $customers */
/** @var bool $canWrite */

use yii\helpers\Html;

$this->title = Yii::t('app', '油票客户');
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
                    <th><?= Yii::t('app', '客户名称') ?></th>
                    <th class="text-right"><?= Yii::t('app', '预存金额') ?></th>
                    <th><?= Yii::t('app', '启用状态') ?></th>
                    <?php if ($canWrite): ?><th></th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($customers)): ?>
                <tr><td colspan="<?= $canWrite ? 5 : 4 ?>" class="text-muted text-center"><?= Yii::t('app', '暂无油票客户') ?></td></tr>
                <?php else: ?>
                <?php foreach ($customers as $i => $customer): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= Html::encode($customer->name) ?></td>
                    <td class="text-right"><?= Yii::$app->formatter->asDecimal((float) $customer->deposit_amount, 2) ?></td>
                    <td><?= Html::encode($customer->getIsActiveLabel()) ?></td>
                    <?php if ($canWrite): ?>
                    <td class="text-right chit-customer-row-actions">
                        <?= Html::a(Yii::t('app', '编辑'), ['update', 'id' => $customer->id], ['class' => 'btn btn-xs btn-primary']) ?>
                        <?= Html::a(Yii::t('app', '删除'), ['delete', 'id' => $customer->id], [
                            'class' => 'btn btn-xs btn-danger',
                            'data' => ['method' => 'post', 'confirm' => Yii::t('app', '确定删除此油票客户？')],
                        ]) ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$this->registerCss('.chit-customer-row-actions a{margin-left:8px}');
?>