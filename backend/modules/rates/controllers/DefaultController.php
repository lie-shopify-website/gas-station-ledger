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

        $cards = GslCard::find()
            ->where(['is_active' => 1])
            ->with('cardType')
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $existing = GslCardRate::find()
            ->where(['effective_from' => $from])
            ->indexBy('card_id')
            ->all();

        $rows = [];
        foreach ($cards as $card) {
            $row = $existing[$card->id] ?? null;
            $rows[] = [
                'card' => $card,
                'rate' => $row ? (float) $row->rate : $card->getCurrentRate($from),
                'filled' => $row !== null,
            ];
        }

        $history = GslCardRate::find()
            ->with('card')
            ->orderBy(['effective_from' => SORT_DESC, 'card_id' => SORT_ASC])
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
        $postedOwners = (array) $request->post('owner_name', []);
        $saved = 0;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($posted as $cardId => $value) {
                $cardId = (int) $cardId;
                if ($cardId <= 0 || $value === null || $value === '' || !is_numeric($value)) {
                    continue;
                }
                GslCardRate::setRate($cardId, $from, $to, (float) $value);
                $saved++;
            }

            // 卡所属人为手填文本，只在不为空时更新，避免误清空
            foreach ($postedOwners as $cardId => $name) {
                $cardId = (int) $cardId;
                $name = trim((string) $name);
                if ($cardId <= 0 || $name === '') {
                    continue;
                }
                $card = GslCard::findOne($cardId);
                if ($card !== null && $card->owner_name !== $name) {
                    $card->owner_name = $name;
                    $card->save(false);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        Yii::$app->session->setFlash('success', Yii::t('app', '已保存 {count} 张卡的费率。', ['count' => $saved]));

        return $this->redirect(['index', 'week' => $from]);
    }
}
