<?php



namespace backend\modules\fills\models;



use common\models\GslFill;

use Yii;

use yii\data\ActiveDataProvider;



class GslFillSearch extends GslFill

{

    public function rules()

    {

        return [

            [['id', 'company_id', 'plate_id', 'counter_id'], 'integer'],

            [['work_date', 'ticket_no', 'note'], 'safe'],

            [['liters', 'amount_due'], 'number'],

        ];

    }



    public function attributeLabels()

    {

        return [

            'id' => 'ID',

            'work_date' => Yii::t('app', '工作日期'),

            'company_id' => Yii::t('app', '公司'),

            'plate_id' => Yii::t('app', '车牌'),

            'counter_id' => Yii::t('app', '柜台'),

            'liters' => Yii::t('app', '升数'),

            'amount_due' => Yii::t('app', '应收'),

            'list_amount' => Yii::t('app', '挂牌金额'),

            'ticket_no' => Yii::t('app', '票号'),

            'note' => Yii::t('app', '备注'),

        ];

    }



    public function search(array $params): ActiveDataProvider

    {

        $query = GslFill::find()->with(['company', 'plate', 'counter']);



        $dataProvider = new ActiveDataProvider([

            'query' => $query,

            'sort' => ['defaultOrder' => ['work_date' => SORT_DESC, 'id' => SORT_ASC]],

            'pagination' => ['pageSize' => 50],

        ]);



        $this->load($params);



        if (!$this->validate()) {

            return $dataProvider;

        }



        $query->andFilterWhere([

            'id' => $this->id,

            'work_date' => $this->work_date,

            'company_id' => $this->company_id,

            'plate_id' => $this->plate_id,

            'counter_id' => $this->counter_id,

        ]);



        $query->andFilterWhere(['like', 'ticket_no', $this->ticket_no])

            ->andFilterWhere(['like', 'note', $this->note]);



        return $dataProvider;

    }



    /**

     * @return array{liters:float,list_amount:float,amount_due:float,count:int}

     */

    public function totals(\yii\db\ActiveQuery $query): array

    {

        $aggQuery = clone $query;

        $aggQuery->with = [];

        $aggQuery->orderBy = [];

        $aggQuery->limit = null;

        $aggQuery->offset = null;



        $row = $aggQuery

            ->select([

                'liters' => 'ROUND(SUM([[liters]]), 3)',

                'list_amount' => 'ROUND(SUM([[list_amount]]), 2)',

                'amount_due' => 'ROUND(SUM([[amount_due]]), 2)',

                'cnt' => 'COUNT(*)',

            ])

            ->asArray()

            ->one();



        return [

            'liters' => (float) ($row['liters'] ?? 0),

            'list_amount' => (float) ($row['list_amount'] ?? 0),

            'amount_due' => (float) ($row['amount_due'] ?? 0),

            'count' => (int) ($row['cnt'] ?? 0),

        ];

    }

}

