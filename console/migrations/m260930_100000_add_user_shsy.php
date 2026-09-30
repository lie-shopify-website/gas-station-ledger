<?php

use common\models\User;
use yii\db\Migration;

class m260930_100000_add_user_shsy extends Migration
{
    private const USERNAME = 'shsy';
    private const PASSWORD = 'sh0000';
    private const ROLE = 'admin';

    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $user = User::findOne(['username' => self::USERNAME]);
        if ($user === null) {
            $user = new User();
            $user->username = self::USERNAME;
            $user->email = self::USERNAME . '@gas-station.local';
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
        if ($role === null) {
            throw new \RuntimeException('Role not found: ' . self::ROLE);
        }
        $auth->assign($role, (string) $user->id);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $user = User::findOne(['username' => self::USERNAME]);
        if ($user !== null) {
            $auth->revokeAll((string) $user->id);
            $this->delete('{{%user}}', ['id' => $user->id]);
        }
    }
}