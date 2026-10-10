<?php

namespace backend\modules\chits\controllers;

use backend\components\GslController;
use backend\modules\chits\models\GslChitSearch;
use common\models\GslChit;
use common\models\GslChitCustomer;
use common\models\GslFill;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('chits.view');

        $searchModel = new GslChitSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $totals = $searchModel->totals($dataProvider->query);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totals' => $totals,
            'customerBalances' => $this->customerBalances(),
            'canWrite' => $this->canWrite('chits.write'),
        ]);
    }

    /**
     * 按客户汇总预存金额、已用金额（该客户油票记录金额合计）与剩余金额。
     *
     * @return array{rows:array<int,array{name:string,deposit:float,used:float,remaining:float}>,deposit:float,used:float,remaining:float}
     */
    protected function customerBalances(): array
    {
        $usedByCustomer = GslChit::find()
            ->select(['customer_id', 'used' => 'ROUND(SUM([[amount]]), 2)'])
            ->where(['not', ['customer_id' => null]])
            ->groupBy('customer_id')
            ->asArray()
            ->all();

        $usedMap = [];
        foreach ($usedByCustomer as $row) {
            $usedMap[(int) $row['customer_id']] = (float) $row['used'];
        }

        $rows = [];
        $totalDeposit = 0.0;
        $totalUsed = 0.0;
        foreach (GslChitCustomer::find()->orderBy(['name' => SORT_ASC])->all() as $customer) {
            $deposit = (float) $customer->deposit_amount;
            $used = $usedMap[$customer->id] ?? 0.0;
            $rows[] = [
                'name' => $customer->name,
                'deposit' => $deposit,
                'used' => $used,
                'remaining' => $deposit - $used,
            ];
            $totalDeposit += $deposit;
            $totalUsed += $used;
        }

        return [
            'rows' => $rows,
            'deposit' => $totalDeposit,
            'used' => $totalUsed,
            'remaining' => $totalDeposit - $totalUsed,
        ];
    }

    public function actionCreate()
    {
        $this->checkPermission('chits.write');

        $model = new GslChit(['work_date' => date('Y-m-d')]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '油票已创建。'));
            return $this->redirect(['index', 'GslChitSearch' => ['work_date' => $model->work_date]]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate(int $id)
    {
        $this->checkPermission('chits.write');

        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '油票已更新。'));
            return $this->redirect(['index', 'GslChitSearch' => ['work_date' => $model->work_date]]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete(int $id)
    {
        $this->checkPermission('chits.write');

        $model = $this->findModel($id);
        $workDate = $model->work_date;
        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', '油票已删除。'));

        return $this->redirect(['index', 'GslChitSearch' => ['work_date' => $workDate]]);
    }

    /**
     * Receipt 号搜索提示：返回 work_date 往前 7 天窗口内 gsl_fill 的票号候选。
     */
    public function actionTicketSuggest()
    {
        $this->checkPermission('chits.view');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $workDate = (string) Yii::$app->request->get('work_date', '');
        $term = trim((string) Yii::$app->request->get('q', ''));

        $query = GslFill::find()
            ->select(['ticket_no'])
            ->where(['not', ['ticket_no' => null]])
            ->andWhere(['<>', 'ticket_no', '']);

        if ($workDate !== '') {
            $from = date('Y-m-d', strtotime($workDate . ' -7 days'));
            $query->andWhere(['between', 'work_date', $from, $workDate]);
        }

        if ($term !== '') {
            $query->andWhere(['like', 'ticket_no', $term]);
        }

        $rows = $query->orderBy(['work_date' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(50)
            ->column();

        $items = array_slice(array_values(array_unique($rows)), 0, 20);

        return ['items' => $items];
    }

    protected function findModel(int $id): GslChit
    {
        if (($model = GslChit::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', '记录不存在。'));
    }
}