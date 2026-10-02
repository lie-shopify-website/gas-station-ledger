<?php

namespace backend\modules\swipes\models;

use common\models\GslSwipe;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

class GslSwipeSearch extends GslSwipe
{
    public $card_type_id;

    public function rules()
    {
        return [
            [['id', 'company_id', 'card_id', 'card_type_id'], 'integer'],
            [['work_date', 'swipe_receipt', 'note'], 'safe'],
            [['liters', 'owner_rate', 'owner_payout'], 'number'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'work_date' => Yii::t('app', '日期'),
            'company_id' => Yii::t('app', '公司'),
            'card_id' => Yii::t('app', '油卡'),
            'card_type_id' => Yii::t('app', '卡类型'),
            'liters' => Yii::t('app', '升数'),
            'owner_rate' => Yii::t('app', '卡费率'),
            'owner_payout' => Yii::t('app', '应付持卡人'),
            'swipe_receipt' => Yii::t('app', '回单号'),
            'note' => Yii::t('app', '备注'),
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = GslSwipe::find()->with(['company', 'card', 'card.cardType']);

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
            'card_id' => $this->card_id,
        ]);

        $query->andFilterWhere(['like', 'swipe_receipt', $this->swipe_receipt])
            ->andFilterWhere(['like', 'note', $this->note]);

        if ($this->card_type_id) {
            $query->joinWith('card')
                ->andWhere(['gsl_card.card_type_id' => $this->card_type_id]);
        }

        return $dataProvider;
    }

    /**
     * @return array{liters:float,owner_payout:float,count:int}
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
                'liters' => 'ROUND(SUM([[liters]]), 3)',
                'owner_payout' => 'ROUND(SUM([[owner_payout]]), 2)',
                'cnt' => 'COUNT(*)',
            ])
            ->asArray()
            ->one();

        return [
            'liters' => (float) ($row['liters'] ?? 0),
            'owner_payout' => (float) ($row['owner_payout'] ?? 0),
            'count' => (int) ($row['cnt'] ?? 0),
        ];
    }
}