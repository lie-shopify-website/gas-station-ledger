<?php

namespace backend\modules\dashboard\controllers;

use backend\components\GslController;
use common\models\GslSetting;
use common\services\report\DailySummaryService;
use common\services\report\DashboardService;
use common\services\report\ExcelReportService;
use Yii;
use yii\web\BadRequestHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('dashboard.view');

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();
        [$dateFrom, $dateTo] = $service->monthBounds($month);

        return $this->render('index', [
            'kpis' => $service->getKpis($month),
            'month' => $month,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyMonthly' => (new DailySummaryService())->getCompanyForRange($dateFrom, $dateTo),
            'dailyCompany' => (new DailySummaryService())->getDailyCompanyForRange($dateFrom, $dateTo),
            'counterMonthly' => $service->getCounterMonthly($month),
        ]);
    }

    public function actionExport()
    {
        $this->checkPermission('dashboard.view');

        $from = (string) Yii::$app->request->get('date_from', '');
        $to = (string) Yii::$app->request->get('date_to', '');
        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));

        if (!$this->isValidDate($from) || !$this->isValidDate($to) || $from > $to) {
            Yii::$app->session->setFlash('error', Yii::t('app', '日期范围无效。'));
            return $this->redirect(['index', 'month' => $month]);
        }

        $service = new ExcelReportService();
        $compact = Yii::$app->request->get('variant') === 'compact';
        $content = $compact ? $service->buildCompact($from, $to) : $service->build($from, $to);
        $name = $compact ? $service->filenameCompact($from, $to) : $service->filename($from, $to);

        return Yii::$app->response->sendContentAsFile(
            $content,
            $name,
            [
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'inline' => false,
            ]
        );
    }

    private function isValidDate(string $date): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }

    public function actionCounters()
    {
        $this->checkPermission('dashboard.view');

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();

        return $this->renderPartial('_counters', [
            'counterMonthly' => $service->getCounterMonthly($month),
            'month' => $month,
        ]);
    }

    public function actionDaily(string $metric)
    {
        $this->checkPermission('dashboard.view');

        if (!in_array($metric, DashboardService::METRICS, true)) {
            throw new BadRequestHttpException(Yii::t('app', '无效的汇总指标。'));
        }

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();

        return $this->renderPartial('_daily', [
            'breakdown' => $service->getDailyBreakdown($month, $metric),
        ]);
    }

    public function actionDailyCompany()
    {
        $this->checkPermission('dashboard.view');

        $month = Yii::$app->request->get('month', GslSetting::getValue('ledger_month', date('Y-m')));
        $service = new DashboardService();
        [$from, $to] = $service->monthBounds($month);

        return $this->renderPartial('_daily_company', [
            'dailyCompany' => (new DailySummaryService())->getDailyCompanyForRange($from, $to),
        ]);
    }
}
