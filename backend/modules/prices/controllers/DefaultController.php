<?php

namespace backend\modules\prices\controllers;

use backend\components\GslController;
use common\models\GslPriceDiscount;
use common\models\GslPricePeriod;
use common\services\PricePeriodGeneratorService;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('prices.view');

        $year = (int) Yii::$app->request->get('year', (int) date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $yearStart = sprintf('%04d-01-01', $year);
        $yearEnd = sprintf('%04d-12-31', $year);

        $periods = GslPricePeriod::find()
            ->with(['discounts.company'])
            ->where(['<=', 'valid_from', $yearEnd])
            ->andWhere(['>=', 'valid_to', $yearStart])
            ->orderBy(['valid_from' => SORT_ASC])
            ->all();

        $years = GslPricePeriod::find()
            ->select(['year' => new \yii\db\Expression('YEAR([[valid_from]])')])
            ->distinct()
            ->orderBy(['year' => SORT_DESC])
            ->column();

        if (empty($years)) {
            $years = [$year];
        } elseif (!in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        $periodsByMonth = [];
        foreach ($periods as $period) {
            $monthKey = substr($period->valid_from, 0, 7);
            $periodsByMonth[$monthKey][] = $period;
        }
        ksort($periodsByMonth);

        $minGeneratableMonth = PricePeriodGeneratorService::minGeneratableMonth();
        $emptyFutureMonths = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthKey = sprintf('%04d-%02d', $year, $m);
            if ($monthKey >= $minGeneratableMonth && !isset($periodsByMonth[$monthKey])) {
                $emptyFutureMonths[] = $monthKey;
            }
        }

        $today = date('Y-m-d');
        $activePeriodId = null;
        foreach ($periods as $period) {
            if ($period->valid_from <= $today && $period->valid_to >= $today) {
                $activePeriodId = $period->id;
                break;
            }
        }

        return $this->render('index', [
            'periodsByMonth' => $periodsByMonth,
            'emptyFutureMonths' => $emptyFutureMonths,
            'year' => $year,
            'years' => $years,
            'today' => $today,
            'activePeriodId' => $activePeriodId,
            'minGeneratableMonth' => $minGeneratableMonth,
            'canWrite' => $this->canWrite('prices.write'),
        ]);
    }

    public function actionGenerateMonth()
    {
        $this->checkPermission('prices.write');

        $month = Yii::$app->request->post('month');
        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new BadRequestHttpException(Yii::t('app', '月份格式无效。'));
        }

        $service = new PricePeriodGeneratorService();
        try {
            $count = $service->generate($month);
            Yii::$app->session->setFlash('success', Yii::t('app', '已生成 {count} 个价格时段。', ['count' => $count]));
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index', 'year' => (int) substr($month, 0, 4)]);
    }

    public function actionClearMonth()
    {
        $this->checkPermission('prices.write');

        $month = Yii::$app->request->post('month');
        if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new BadRequestHttpException(Yii::t('app', '月份格式无效。'));
        }

        $service = new PricePeriodGeneratorService();
        try {
            $count = $service->clearMonth($month);
            Yii::$app->session->setFlash('success', Yii::t('app', '已清空 {count} 个价格时段。', ['count' => $count]));
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index', 'year' => (int) substr($month, 0, 4)]);
    }

    public function actionCreatePeriod()
    {
        $this->checkPermission('prices.write');

        $model = new GslPricePeriod();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '价格时段已创建。'));

            return $this->redirect(['index', 'year' => (int) substr($model->valid_from, 0, 4)]);
        }

        return $this->render('period-form', [
            'model' => $model,
            'title' => Yii::t('app', '新增价格时段'),
        ]);
    }

    public function actionUpdatePeriod(int $id)
    {
        $this->checkPermission('prices.write');

        $model = $this->findPeriod($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '价格时段已更新。'));

            return $this->redirect(['index', 'year' => (int) substr($model->valid_from, 0, 4)]);
        }

        return $this->render('period-form', [
            'model' => $model,
            'title' => Yii::t('app', '编辑价格时段'),
        ]);
    }

    public function actionCreateDiscount(int $period_id)
    {
        $this->checkPermission('prices.write');

        $period = $this->findPeriod($period_id);
        $model = new GslPriceDiscount(['price_period_id' => $period->id]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '折扣价已创建。'));

            return $this->redirect(['index', 'year' => (int) substr($period->valid_from, 0, 4)]);
        }

        return $this->render('discount-form', [
            'model' => $model,
            'period' => $period,
            'title' => Yii::t('app', '新增折扣价'),
        ]);
    }

    public function actionUpdateDiscount(int $id)
    {
        $this->checkPermission('prices.write');

        $model = $this->findDiscount($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', '折扣价已更新。'));

            $period = $this->findPeriod((int) $model->price_period_id);
            return $this->redirect(['index', 'year' => (int) substr($period->valid_from, 0, 4)]);
        }

        return $this->render('discount-form', [
            'model' => $model,
            'period' => $this->findPeriod((int) $model->price_period_id),
            'title' => Yii::t('app', '编辑折扣价'),
        ]);
    }

    protected function findPeriod(int $id): GslPricePeriod
    {
        if (($model = GslPricePeriod::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', '价格时段不存在。'));
    }

    protected function findDiscount(int $id): GslPriceDiscount
    {
        if (($model = GslPriceDiscount::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', '折扣价不存在。'));
    }
}
