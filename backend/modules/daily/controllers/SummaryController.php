<?php

namespace backend\modules\daily\controllers;

use backend\components\GslController;
use common\services\report\CounterSummaryService;
use common\services\report\DailySummaryService;
use Yii;
use yii\web\Response;

class SummaryController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('daily.view');

        $workDate = Yii::$app->request->get('work_date', date('Y-m-d'));
        $service = new DailySummaryService();
        $summary = $service->getSummary($workDate);

        return $this->render('index', [
            'summary' => $summary,
            'counterSummary' => (new CounterSummaryService())->getSummary($workDate),
        ]);
    }

    public function actionWeek()
    {
        $this->checkPermission('daily.view');

        $today = date('Y-m-d');
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');

        if ($dateFrom && $dateTo) {
            $from = $dateFrom;
            $to = $dateTo;
        } else {
            $ts = strtotime($today);
            $dayOfWeek = (int) date('N', $ts);
            $from = date('Y-m-d', $ts - ($dayOfWeek - 1) * 86400);
            $to = date('Y-m-d', $ts + (7 - $dayOfWeek) * 86400);
        }

        $service = new DailySummaryService();
        $rangeSummary = $service->getProfitForRange($from, $to);
        $companyRange = $service->getCompanyForRange($from, $to);

        return $this->render('range', [
            'rangeSummary' => $rangeSummary,
            'companyRange' => $companyRange,
            'mode' => 'week',
        ]);
    }

    public function actionMonth()
    {
        $this->checkPermission('daily.view');

        $today = date('Y-m-d');
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');

        if ($dateFrom && $dateTo) {
            $from = $dateFrom;
            $to = $dateTo;
        } else {
            $ts = strtotime($today);
            $from = date('Y-m-01', $ts);
            $to = date('Y-m-t', $ts);
        }

        $service = new DailySummaryService();
        $rangeSummary = $service->getProfitForRange($from, $to);
        $companyRange = $service->getCompanyForRange($from, $to);

        return $this->render('range', [
            'rangeSummary' => $rangeSummary,
            'companyRange' => $companyRange,
            'mode' => 'month',
        ]);
    }

    public function actionCompanyDetails(int $company_id)
    {
        $this->checkPermission('daily.view');

        $today = date('Y-m-d');
        $ts = strtotime($today);
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01', $ts));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-t', $ts));

        $service = new DailySummaryService();
        $daily = $service->getDailyCompanyForRange($dateFrom, $dateTo);

        $days = [];
        foreach ($daily['days'] as $day) {
            foreach ($day['companies'] as $company) {
                if ((int) $company['company_id'] === $company_id) {
                    $days[] = [
                        'work_date' => $day['work_date'],
                        'count' => $company['count'],
                        'liters' => $company['liters'],
                        'amount_due' => $company['amount_due'],
                    ];
                    break;
                }
            }
        }

        return $this->renderPartial('_company_details', [
            'days' => $days,
            'totals' => [
                'count' => array_sum(array_column($days, 'count')),
                'liters' => round(array_sum(array_column($days, 'liters')), 3),
                'amount_due' => round(array_sum(array_column($days, 'amount_due')), 2),
            ],
        ]);
    }

    public function actionDetails(int $company_id)
    {
        $this->checkPermission('daily.view');

        $workDate = Yii::$app->request->get('work_date', date('Y-m-d'));
        $service = new DailySummaryService();
        $fills = $service->getCompanyDetails($workDate, $company_id);

        if (Yii::$app->request->isAjax) {
            return $this->renderPartial('_details', ['fills' => $fills]);
        }

        return $this->renderPartial('_details', ['fills' => $fills]);
    }
}
