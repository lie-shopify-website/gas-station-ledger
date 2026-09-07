<?php



/** @var yii\web\View $this */

/** @var backend\modules\fills\models\GslFillSearch $searchModel */

/** @var yii\data\ActiveDataProvider $dataProvider */

/** @var array{liters:float,list_amount:float,amount_due:float,count:int} $totals */

/** @var bool $canWrite */



use common\models\GslCompany;

use common\models\GslCounter;

use yii\grid\GridView;
use yii\grid\SerialColumn;

use yii\helpers\ArrayHelper;

use yii\helpers\Html;

$this->title = Yii::t('app', '加油记录');

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

                'footerRowOptions' => ['class' => 'font-weight-bold fill-summary-row'],

                'columns' => [

                    [
                        'class' => SerialColumn::class,
                        'header' => Yii::t('app', '序号'),
                        'footer' => Yii::t('app', '合计'),
                    ],

                    'work_date',

                    [

                        'attribute' => 'company_id',

                        'value' => 'company.name',

                        'filter' => ArrayHelper::map(GslCompany::find()->orderBy('sort_order')->all(), 'id', 'name'),

                    ],

                    [

                        'attribute' => 'plate_id',

                        'value' => 'plate.plate_no',

                        'label' => Yii::t('app', '车牌'),

                    ],

                    [

                        'attribute' => 'counter_id',

                        'value' => 'counter.code',

                        'filter' => ArrayHelper::map(GslCounter::find()->orderBy('sort_order')->all(), 'id', 'code'),

                        'label' => Yii::t('app', '柜台'),

                    ],

                    [

                        'attribute' => 'liters',

                        'contentOptions' => ['class' => 'text-right'],

                        'footerOptions' => ['class' => 'text-right'],

                        'format' => ['decimal', 3],

                        'footer' => Yii::$app->formatter->asDecimal($totals['liters'], 3),

                    ],

                    [

                        'attribute' => 'list_amount',

                        'label' => Yii::t('app', '挂牌金额'),

                        'contentOptions' => ['class' => 'text-right'],

                        'footerOptions' => ['class' => 'text-right'],

                        'filter' => false,

                        'format' => ['decimal', 2],

                        'footer' => Yii::$app->formatter->asDecimal($totals['list_amount'], 2),

                    ],

                    [

                        'attribute' => 'amount_due',

                        'contentOptions' => ['class' => 'text-right'],

                        'footerOptions' => ['class' => 'text-right'],

                        'format' => ['decimal', 2],

                        'footer' => Yii::$app->formatter->asDecimal($totals['amount_due'], 2),

                    ],

                    'ticket_no',

                    [

                        'class' => 'yii\grid\ActionColumn',

                        'template' => $canWrite ? '{update}{delete}' : '',

                        'contentOptions' => ['class' => 'fill-row-actions'],

                        'buttons' => [

                            'update' => fn($url) => Html::a('<i class="fas fa-edit"></i>', $url, ['class' => 'btn btn-xs btn-primary', 'title' => Yii::t('app', '编辑')]),

                            'delete' => fn($url) => Html::a('<i class="fas fa-trash"></i>', $url, [

                                'class' => 'btn btn-xs btn-danger',

                                'title' => Yii::t('app', '删除'),

                                'data' => [

                                    'method' => 'post',

                                    'confirm' => Yii::t('app', '确定删除此加油记录？'),

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
$this->registerCss('.fill-row-actions{display:flex;flex-direction:row;align-items:center;gap:16px;white-space:nowrap}.fill-row-actions a{flex-shrink:0}.grid-view .pagination{display:flex;flex-wrap:wrap;align-items:center;gap:16px;padding-left:0;margin:1rem 0 0}.grid-view .pagination>li{list-style:none}.fill-summary-row td{background:#f4f6f9}');
?>

