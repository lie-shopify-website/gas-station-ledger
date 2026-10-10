<?php

/** @var yii\web\View $this */
/** @var backend\modules\chits\models\GslChitSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array{amount:float,count:int} $totals */
/** @var bool $canWrite */

use common\models\GslCounter;
use yii\grid\GridView;
use yii\grid\SerialColumn;
use yii\helpers\Html;

$this->title = Yii::t('app', '油票登记');
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