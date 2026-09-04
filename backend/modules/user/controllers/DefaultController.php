<?php

namespace backend\modules\user\controllers;

use backend\components\GslController;
use backend\modules\user\models\AssignRoleForm;
use common\models\User;
use Yii;
use yii\web\NotFoundHttpException;

class DefaultController extends GslController
{
    public function actionIndex()
    {
        $this->checkPermission('users.manage');

        $users = User::find()->where(['<>', 'status', User::STATUS_DELETED])->orderBy(['id' => SORT_ASC])->all();
        $roles = Yii::$app->authManager->getRoles();
        $roleNames = array_keys($roles);

        $assignModel = new AssignRoleForm();
        if ($assignModel->load(Yii::$app->request->post()) && $assignModel->validate()) {
            $this->assignRole((int) $assignModel->user_id, $assignModel->role);
            Yii::$app->session->setFlash('success', Yii::t('app', '角色已分配。'));
            return $this->refresh();
        }

        $userRoles = [];
        foreach ($users as $user) {
            $assigned = Yii::$app->authManager->getRolesByUser($user->id);
            $userRoles[$user->id] = empty($assigned) ? '—' : implode(', ', array_keys($assigned));
        }

        return $this->render('index', [
            'users' => $users,
            'roleNames' => $roleNames,
            'userRoles' => $userRoles,
            'assignModel' => $assignModel,
        ]);
    }

    protected function assignRole(int $userId, string $roleName): void
    {
        $user = User::findOne($userId);
        if (!$user) {
            throw new NotFoundHttpException(Yii::t('app', '用户不存在。'));
        }

        $auth = Yii::$app->authManager;
        $auth->revokeAll($userId);

        $role = $auth->getRole($roleName);
        if ($role) {
            $auth->assign($role, (string) $userId);
        }
    }
}
