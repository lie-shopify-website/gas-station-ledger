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

}

