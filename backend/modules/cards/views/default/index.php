<?php



/** @var yii\web\View $this */

/** @var common\models\GslCard[] $cards */

/** @var bool $canWrite */



use yii\helpers\Html;



$this->title = Yii::t('app', '油卡管理');

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

    <div class="card-body p-0">

        <div class="table-responsive table-responsive-wide">

            <table class="table table-bordered table-striped table-sm mb-0">

                <thead>

                <tr>

                    <th><?= Yii::t('app', '卡号') ?></th>

                    <th><?= Yii::t('app', '真实卡号') ?></th>

                    <th class="text-right"><?= Yii::t('app', '月配额') ?></th>

                    <th class="text-right"><?= Yii::t('app', '已用升数') ?></th>

                    <th><?= Yii::t('app', '排序') ?></th>

                    <th><?= Yii::t('app', '状态') ?></th>

                    <th><?= Yii::t('app', '备注') ?></th>

                    <?php if ($canWrite): ?><th></th><?php endif; ?>

                </tr>

                </thead>

                <tbody>

                <?php if (empty($cards)): ?>

                <tr><td colspan="<?= $canWrite ? 8 : 7 ?>" class="text-center text-muted"><?= Yii::t('app', '暂无油卡') ?></td></tr>

                <?php else: ?>

                <?php foreach ($cards as $card): ?>

                <tr>

                    <td><?= Html::encode($card->card_code) ?></td>

                    <td><?= $card->real_card_no !== null && $card->real_card_no !== '' ? Html::encode($card->real_card_no) : '—' ?></td>

                    <td class="text-right"><?= number_format($card->monthly_quota, 3) ?></td>

                    <td class="text-right"><?= number_format($card->getUsedLiters(), 3) ?></td>

                    <td><?= (int) $card->sort_order ?></td>

                    <td><?= $card->is_active ? Yii::t('app', '启用') : Yii::t('app', '停用') ?></td>

                    <td><?= Html::encode($card->note) ?></td>

                    <?php if ($canWrite): ?>

                    <td><?= Html::a(Yii::t('app', '编辑'), ['update', 'id' => $card->id], ['class' => 'btn btn-xs btn-primary']) ?></td>

                    <?php endif; ?>

                </tr>

                <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

