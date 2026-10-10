<?php

/** @var yii\web\View $this */
/** @var backend\modules\chits\models\GslChitSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array{amount:float,count:int} $totals */
/** @var array{rows:array<int,array{name:string,deposit:float,used:float,remaining:float}>,deposit:float,used:float,remaining:float} $customerBalances */
/** @var bool $canWrite */

use common\models\GslChit;
use common\models\GslChitCustomer;
use common\models\GslCounter;
use yii\grid\GridView;
use yii\grid\SerialColumn;
use yii\helpers\Html;

$this->title = Yii::t('app', '油票登记');
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if (!empty($customerBalances['rows'])): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= Yii::t('app', '客户预存余额') ?></h3>
    </div>
    <div class="card-body">
        <div class="table-responsive table-responsive-wide">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                <tr>
                    <th><?= Yii::t('app', '客户名称') ?></th>
                    <th class="text-right"><?= Yii::t('app', '预存金额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '已用金额') ?></th>
                    <th class="text-right"><?= Yii::t('app', '剩余金额') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($customerBalances['rows'] as $row): ?>
                <tr>
                    <td><?= Html::encode($row['name']) ?></td>
                    <td class="text-right"><?= Yii::$app->formatter->asDecimal($row['deposit'], 2) ?></td>
                    <td class="text-right"><?= Yii::$app->formatter->asDecimal($row['used'], 2) ?></td>
                    <td class="text-right <?= $row['remaining'] < 0 ? 'text-danger' : '' ?>"><?= Yii::$app->formatter->asDecimal($row['remaining'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="font-weight-bold chit-summary-row">
                    <td><?= Yii::t('app', '合计') ?></td>
                    <td class="text-right"><?= Yii::$app->formatter->asDecimal($customerBalances['deposit'], 2) ?></td>
                    <td class="text-right"><?= Yii::$app->formatter->asDecimal($customerBalances['used'], 2) ?></td>
                    <td class="text-right"><?= Yii::$app->formatter->asDecimal($customerBalances['remaining'], 2) ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
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
        <div class="table-responsive table-responsive-wide">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'table table-bordered table-striped table-sm'],
                'showFooter' => true,
                'footerRowOptions' => ['class' => 'font-weight-bold chit-summary-row'],
                'columns' => [
                    [
                        'class' => SerialColumn::class,
                        'header' => Yii::t('app', '序号'),
                        'footer' => Yii::t('app', '合计'),
                    ],
                    'work_date',
                    [
                        'attribute' => 'counter_id',
                        'value' => fn($model) => $model->counter ? $model->counter->getLabel() : '—',
                        'filter' => GslCounter::options(),
                    ],
                    [
                        'attribute' => 'customer_id',
                        'value' => fn($model) => $model->customer ? $model->customer->name : '—',
                        'filter' => GslChitCustomer::options(),
                    ],
                    [
                        'attribute' => 'oil_type',
                        'filter' => GslChit::oilTypeOptions(),
                    ],
                    'operator_no',
                    [
                        'attribute' => 'amount',
                        'contentOptions' => ['class' => 'text-right'],
                        'footerOptions' => ['class' => 'text-right'],
                        'format' => ['decimal', 2],
                        'footer' => Yii::$app->formatter->asDecimal($totals['amount'], 2),
                    ],
                    'chit_stub_no',
                    'receipt_no',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => $canWrite ? '{update}{delete}' : '',
                        'contentOptions' => ['class' => 'chit-row-actions'],
                        'buttons' => [
                            'update' => fn($url) => Html::a('<i class="fas fa-edit"></i>', $url, ['class' => 'btn btn-xs btn-primary', 'title' => Yii::t('app', '编辑')]),
                            'delete' => fn($url) => Html::a('<i class="fas fa-trash"></i>', $url, [
                                'class' => 'btn btn-xs btn-danger',
                                'title' => Yii::t('app', '删除'),
                                'data' => [
                                    'method' => 'post',
                                    'confirm' => Yii::t('app', '确定删除此油票？'),
                                ],
                            ]),
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>

<?php
$this->registerCss('.chit-row-actions{display:flex;flex-direction:row;align-items:center;gap:16px;white-space:nowrap}.chit-row-actions a{flex-shrink:0}.grid-view .pagination{display:flex;flex-wrap:wrap;align-items:center;gap:16px;padding-left:0;margin:1rem 0 0}.grid-view .pagination>li{list-style:none}.chit-summary-row td{background:#f4f6f9}');
?>