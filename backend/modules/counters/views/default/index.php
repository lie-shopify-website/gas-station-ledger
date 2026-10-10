<?php

/** @var yii\web\View $this */
/** @var common\models\GslCounter[] $counters */
/** @var bool $canWrite */

use yii\helpers\Html;

$this->title = Yii::t('app', '柜台管理');
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
                    <th><?= Yii::t('app', '代号') ?></th>
                    <th><?= Yii::t('app', '柜台名称') ?></th>
                    <th><?= Yii::t('app', '柜员号') ?></th>
                    <th><?= Yii::t('app', '排序') ?></th>
                    <?php if ($canWrite): ?><th></th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($counters)): ?>
                <tr><td colspan="<?= $canWrite ? 6 : 5 ?>" class="text-muted text-center"><?= Yii::t('app', '暂无柜台') ?></td></tr>
                <?php else: ?>
                <?php foreach ($counters as $i => $counter): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= Html::encode($counter->code) ?></td>
                    <td><?= Html::encode($counter->name) ?></td>
                    <td><?= Html::encode($counter->operator_no) ?></td>
                    <td><?= (int) $counter->sort_order ?></td>
                    <?php if ($canWrite): ?>
                    <td class="text-right counter-row-actions">
                        <?= Html::a(Yii::t('app', '编辑'), ['update', 'id' => $counter->id], ['class' => 'btn btn-xs btn-primary']) ?>
                        <?= Html::a(Yii::t('app', '删除'), ['delete', 'id' => $counter->id], [
                            'class' => 'btn btn-xs btn-danger',
                            'data' => ['method' => 'post', 'confirm' => Yii::t('app', '确定删除此柜台？')],
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
$this->registerCss('.counter-row-actions a{margin-left:8px}');
?>