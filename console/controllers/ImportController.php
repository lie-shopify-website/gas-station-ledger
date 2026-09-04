<?php

namespace console\controllers;

use common\services\import\ExcelImportService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ImportController extends Controller
{
    /** @var bool Dry run without writing */
    public $dryRun = false;
    /** @var bool Truncate business tables before import */
    public $truncate = true;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['dryRun', 'truncate']);
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'd' => 'dryRun',
            't' => 'truncate',
        ]);
    }

    /**
     * Import ledger data from Excel.
     * @param string $file Absolute path to xlsx
     */
    public function actionExcel($file)
    {
        if (!file_exists($file)) {
            $this->stderr("File not found: {$file}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Importing from: {$file}\n", Console::FG_YELLOW);
        if ($this->dryRun) {
            $this->stdout("DRY RUN — no database writes\n", Console::FG_CYAN);
        }

        try {
            $service = new ExcelImportService($this->dryRun);
            $service->import($file, $this->truncate);
        } catch (\Throwable $e) {
            $this->stderr("Import failed: {$e->getMessage()}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $stats = $service->getStats();
        foreach ($stats as $key => $val) {
            $this->stdout("  {$key}: {$val}\n");
        }

        if (!$this->dryRun) {
            $this->printReconciliation();
        }

        $this->stdout("Done.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function printReconciliation(): void
    {
        $db = Yii::$app->db;
        $this->stdout("\nReconciliation:\n", Console::FG_YELLOW);
        $amountDue = $db->createCommand('SELECT ROUND(SUM(amount_due),2) FROM {{%gsl_fill}}')->queryScalar();
        $cost = $db->createCommand('SELECT ROUND(SUM(cost),2) FROM {{%gsl_fill}}')->queryScalar();
        $liters = $db->createCommand('SELECT ROUND(SUM(liters),3) FROM {{%gsl_fill}}')->queryScalar();
        $count = $db->createCommand('SELECT COUNT(*) FROM {{%gsl_fill}} WHERE liters>0')->queryScalar();
        $this->stdout("  SUM(amount_due): {$amountDue}\n");
        $this->stdout("  SUM(cost): {$cost}\n");
        $this->stdout("  SUM(liters): {$liters}\n");
        $this->stdout("  fill count: {$count}\n");
    }
}
