<?php

/** @var yii\web\View $this */
/** @var common\models\GslCompany[] $companies */
/** @var bool $canWrite */

use yii\helpers\Html;
$this->title = Yii::t('app', '公司 / 车牌');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card summary-panel">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
        <?php if ($canWrite): ?>
        <div class="card-tools">
            <?= Html::a('<i class="fas fa-plus"></i> ' . Yii::t('app', '新增公司'), ['create-company'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php foreach ($companies as $company): ?>
        <div class="card card-outline card-secondary mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <?= Html::encode($company->name) ?>
                    <small class="text-muted">(<?= Html::encode($company->payment_type) ?>)</small>
                    <?php if (!$company->is_active): ?><span class="badge badge-secondary"><?= Yii::t('app', '停用') ?></span><?php endif; ?>
                </h3>
                <?php if ($canWrite): ?>
                <div class="card-tools">
                    <?= Html::a(Yii::t('app', '编辑'), ['update-company', 'id' => $company->id], ['class' => 'btn btn-xs btn-primary']) ?>
                    <?= Html::a(Yii::t('app', '新增车牌'), ['create-plate', 'company_id' => $company->id], ['class' => 'btn btn-xs btn-success']) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                        <tr>
                            <th><?= Yii::t('app', '车牌号') ?></th>
                            <th><?= Yii::t('app', '状态') ?></th>
                            <?php if ($canWrite): ?><th></th><?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($company->plates)): ?>
                        <tr><td colspan="<?= $canWrite ? 3 : 2 ?>" class="text-muted text-center"><?= Yii::t('app', '暂无车牌') ?></td></tr>
                        <?php else: ?>
                        <?php foreach ($company->plates as $plate): ?>
                        <tr>
                            <td><?= Html::encode($plate->plate_no) ?></td>
                            <td><?= $plate->is_active ? Yii::t('app', '启用') : Yii::t('app', '停用') ?></td>
                            <?php if ($canWrite): ?>
                            <td class="text-right">
                                <?= Html::a(Yii::t('app', '编辑'), ['update-plate', 'id' => $plate->id], ['class' => 'btn btn-xs btn-primary']) ?>
                                <?= Html::a(Yii::t('app', '删除'), ['delete-plate', 'id' => $plate->id], [
                                    'class' => 'btn btn-xs btn-danger',
                                    'data' => ['method' => 'post', 'confirm' => Yii::t('app', '确定删除此车牌？')],
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
        <?php endforeach; ?>
    </div>
</div>
