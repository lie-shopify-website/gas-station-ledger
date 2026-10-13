<?php

namespace backend\modules\rates\controllers;

use backend\components\GslController;
use common\models\GslCard;
use common\models\GslCardRate;
use Yii;
use yii\web\BadRequestHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('rates.view');

        $anchor = (string) Yii::$app->request->get('week', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchor)) {
            $anchor = date('Y-m-d');
        }
        [$from, $to] = GslCardRate::weekOf($anchor);

        $owners = GslCard::ownerNameOptions();

        $existing = GslCardRate::find()
            ->where(['effective_from' => $from])
            ->indexBy('owner_name')
            ->all();

        $rows = [];
        foreach ($owners as $ownerName) {
            $row = $existing[$ownerName] ?? null;
            // Get a card for this owner to determine the default fallback rate
            $card = GslCard::find()->where(['owner_name' => $ownerName])->one();
            $rows[] = [
                'owner_name' => $ownerName,
                'rate' => $row ? (float) $row->rate : ($card ? $card->getCurrentRate($from) : 0.0),
                'filled' => $row !== null,
            ];
        }

        $history = GslCardRate::find()
            ->orderBy(['effective_from' => SORT_DESC, 'owner_name' => SORT_ASC])
            ->limit(200)
            ->all();

        return $this->render('index', [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'history' => $history,
            'canWrite' => $this->canWrite('rates.write'),
        ]);
    }

    public function actionSave()
    {
        $this->checkPermission('rates.write');

        $request = Yii::$app->request;
        if (!$request->isPost) {
            throw new BadRequestHttpException(Yii::t('app', '请求方式无效。'));
        }

        $from = (string) $request->post('effective_from', '');
        $to = (string) $request->post('effective_to', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            throw new BadRequestHttpException(Yii::t('app', '周区间无效。'));
        }

        $posted = (array) $request->post('rate', []);
        $saved = 0;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($posted as $ownerName => $value) {
                $ownerName = trim((string) $ownerName);
                if ($ownerName === '' || $value === null || $value === '' || !is_numeric($value)) {
                    continue;
                }
                GslCardRate::setRate($ownerName, $from, $to, (float) $value);
                $saved++;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        Yii::$app->session->setFlash('success', Yii::t('app', '已保存 {count} 个客户的费率。', ['count' => $saved]));

        return $this->redirect(['index', 'week' => $from]);
    }
}
