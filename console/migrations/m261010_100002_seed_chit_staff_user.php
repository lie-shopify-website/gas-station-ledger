<?php

use common\models\User;
use yii\db\Migration;

/**
 * 员工账号：仅能访问「油票登记」。
 * 新建角色 staff（仅 chits.view / chits.write）并创建用户 0（密码 0）。
 */
class m261010_100002_seed_chit_staff_user extends Migration
{
    private const USERNAME = '0';
    private const PASSWORD = '0';
    private const ROLE = 'staff';

    private function permissions(): array
    {
        return ['chits.view', 'chits.write'];
    }

    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        if ($auth->getRole(self::ROLE) === null) {
            $role = $auth->createRole(self::ROLE);
            $role->description = 'Chit entry staff';
            $auth->add($role);
            foreach ($this->permissions() as $name) {
                $perm = $auth->getPermission($name);
                if ($perm !== null) {
                    $auth->addChild($role, $perm);
                }
            }
        }

        $user = User::findOne(['username' => self::USERNAME]);
        if ($user === null) {
            $user = new User();
            $user->username = self::USERNAME;
            $user->email = '0@gas-station.local';
            $user->status = User::STATUS_ACTIVE;
            $user->must_change_password = 0;
            $user->language = 'zh-CN';
            $user->setPassword(self::PASSWORD);
            $user->generateAuthKey();
            if (!$user->save(false)) {
                throw new \RuntimeException('Failed to create user: ' . self::USERNAME);
            }
        }

        $auth->revokeAll((string) $user->id);
        $role = $auth->getRole(self::ROLE);
        if ($role !== null) {
            $auth->assign($role, (string) $user->id);
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $user = User::findOne(['username' => self::USERNAME]);
        if ($user !== null) {
            $auth->revokeAll((string) $user->id);
            $this->delete('{{%user}}', ['id' => $user->id]);
        }

        $role = $auth->getRole(self::ROLE);
        if ($role !== null) {
            $auth->remove($role);
        }
    }
}