<?php

namespace backend\modules\chits\models;

use common\models\GslChit;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

class GslChitSearch extends GslChit
{
    public function rules()
    {
        return [
            [['id', 'counter_id'], 'integer'],
            [['work_date', 'operator_no', 'chit_stub_no', 'receipt_no'], 'safe'],
            [['amount'], 'number'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'work_date' => Yii::t('app', '日期'),
            'counter_id' => Yii::t('app', '柜台'),
            'operator_no' => Yii::t('app', '柜员号'),
            'amount' => Yii::t('app', '金额'),
            'chit_stub_no' => Yii::t('app', '票根号'),
            'receipt_no' => Yii::t('app', 'Receipt 号'),
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = GslChit::find()->with(['counter']);

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
            'counter_id' => $this->counter_id,
        ]);

        $query->andFilterWhere(['like', 'operator_no', $this->operator_no])
            ->andFilterWhere(['like', 'chit_stub_no', $this->chit_stub_no])
            ->andFilterWhere(['like', 'receipt_no', $this->receipt_no]);

        return $dataProvider;
    }

    /**
     * @return array{amount:float,count:int}
     */
    public function totals(ActiveQuery $query): array
    {
        $aggQuery = clone $query;
        $aggQuery->with = [];
        $aggQuery->orderBy = [];
        $aggQuery->limit = null;
        $aggQuery->offset = null;

        $row = $aggQuery
            ->select([
                'amount' => 'ROUND(SUM([[amount]]), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->asArray()
            ->one();

        return [
            'amount' => (float) ($row['amount'] ?? 0),
            'count' => (int) ($row['cnt'] ?? 0),
        ];
    }
}