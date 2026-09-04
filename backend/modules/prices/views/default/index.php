<?php

/** @var yii\web\View $this */
/** @var array<string, common\models\GslPricePeriod[]> $periodsByMonth */
/** @var string[] $emptyFutureMonths */
/** @var int $year */
/** @var int[] $years */
/** @var string $today */
/** @var int|null $activePeriodId */
/** @var string $minGeneratableMonth */
/** @var bool $canWrite */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', '价格管理');
$this->params['breadcrumbs'][] = $this->title;

$totalPeriods = 0;
foreach ($periodsByMonth as $monthPeriods) {
    $totalPeriods += count($monthPeriods);
}

?>
<div class="card summary-panel">
    <div class="card-header">
        <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
        <?php if ($canWrite): ?>
        <div class="card-tools">
            <?= Html::a('<i class="fas fa-plus"></i> ' . Yii::t('app', '新增时段'), ['create-period'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]); ?>
        <div class="form-row align-items-end">
            <div class="col-auto">
                <label class="control-label"><?= Yii::t('app', '年份') ?></label>
                <select name="year" class="form-control">
                    <?php foreach ($years as $y): ?>
                    <option value="<?= (int) $y ?>"<?= (int) $y === $year ? ' selected' : '' ?>><?= (int) $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <?= Html::submitButton(Yii::t('app', '查询'), ['class' => 'btn btn-primary']) ?>
            </div>
            <div class="col-auto text-muted align-self-center">
                <?= Yii::t('app', '共 {count} 个时段', ['count' => $totalPeriods]) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <?php if ($canWrite): ?>
        <div class="card card-outline card-secondary">
            <div class="card-body py-3">
                <?php $generateForm = ActiveForm::begin(['action' => ['generate-month']]); ?>
                <div class="form-row align-items-end">
                    <div class="col-auto">
                        <label class="control-label"><?= Yii::t('app', '生成月份') ?></label>
                        <input type="month" name="month" class="form-control" min="<?= Html::encode($minGeneratableMonth) ?>" value="<?= Html::encode($minGeneratableMonth) ?>">
                    </div>
                    <div class="col-auto">
                        <?= Html::submitButton(Yii::t('app', '生成时段'), ['class' => 'btn btn-primary']) ?>
                    </div>
                    <div class="col text-muted align-self-center small">
                        <?= Yii::t('app', '仅可生成下月及以后；以周四为节点；挂牌价与折扣价默认为 0。') ?>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($periodsByMonth) && empty($emptyFutureMonths)): ?>
        <p class="text-muted mb-0"><?= Yii::t('app', '{year} 年暂无价格时段', ['year' => $year]) ?></p>
        <?php else: ?>
        <?php foreach ($periodsByMonth as $monthKey => $monthPeriods): ?>
        <?php
        [$y, $m] = explode('-', $monthKey);
        $canClearMonth = $canWrite && $monthKey >= $minGeneratableMonth;
        ?>
        <div class="card card-outline card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0"><?= Html::encode(Yii::t('app', '{year} 年 {month} 月', ['year' => (int) $y, 'month' => (int) $m])) ?></h3>
                <div class="card-tools">
                    <span class="badge badge-light"><?= Yii::t('app', '共 {count} 个时段', ['count' => count($monthPeriods)]) ?></span>
                    <?php if ($canClearMonth): ?>
                    <?php $clearFormId = 'clear-month-' . str_replace('-', '', $monthKey); ?>
                    <?= Html::beginForm(['clear-month'], 'post', ['id' => $clearFormId, 'class' => 'd-inline']) ?>
                    <?= Html::hiddenInput('month', $monthKey) ?>
                    <?= Html::submitButton(Yii::t('app', '清空该月'), [
                        'class' => 'btn btn-xs btn-outline-danger ml-2',
                        'data' => [
                            'confirm' => Yii::t('app', '确定清空 {month} 的全部价格时段？', ['month' => $monthKey]),
                        ],
                    ]) ?>
                    <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?= $this->render('_period_table', [
                    'monthPeriods' => $monthPeriods,
                    'today' => $today,
                    'activePeriodId' => $activePeriodId,
                    'canWrite' => $canWrite,
                ]) ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php foreach ($emptyFutureMonths as $monthKey): ?>
        <?php [$y, $m] = explode('-', $monthKey); ?>
        <div class="card card-outline card-secondary mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0"><?= Html::encode(Yii::t('app', '{year} 年 {month} 月', ['year' => (int) $y, 'month' => (int) $m])) ?></h3>
                <div class="card-tools">
                    <span class="text-muted small"><?= Yii::t('app', '尚未生成') ?></span>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0"><?= Yii::t('app', '该月暂无价格时段，可使用上方「生成时段」一键创建。') ?></p>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$js = <<<'JS'
$('.toggle-price-discounts').on('click', function () {
    $($(this).data('target')).collapse('toggle');
});
JS;
$this->registerJs($js);
?>
