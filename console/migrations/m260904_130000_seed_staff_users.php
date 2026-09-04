<?php

use common\models\User;
use yii\db\Migration;

class m260904_130000_seed_staff_users extends Migration
{
    private function viewPermissions(): array
    {
        return [
            'fills.view',
            'swipes.view',
            'prices.view',
            'plates.view',
            'daily.view',
            'counter.view',
            'dashboard.view',
            'cards.view',
        ];
    }

    private function users(): array
    {
        return [
            ['username' => 'shwendy', 'role' => 'viewer'],
            ['username' => 'shrichard', 'role' => 'admin'],
            ['username' => 'shmichelle', 'role' => 'admin'],
            ['username' => 'shet', 'role' => 'viewer'],
            ['username' => 'shwilliam', 'role' => 'counter_clerk'],
        ];
    }

    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        if ($auth->getRole('viewer') === null) {
            $viewer = $auth->createRole('viewer');
            $viewer->description = 'Read-only viewer';
            $auth->add($viewer);
            foreach ($this->viewPermissions() as $name) {
                $auth->addChild($viewer, $auth->getPermission($name));
            }
        }

        foreach ($this->users() as $spec) {
            $user = User::findOne(['username' => $spec['username']]);
            if ($user === null) {
                $user = new User();
                $user->username = $spec['username'];
                $user->email = $spec['username'] . '@gas-station.local';
                $user->status = User::STATUS_ACTIVE;
                $user->must_change_password = 0;
                $user->language = 'zh-CN';
                $user->setPassword('sh0000');
                $user->generateAuthKey();
                if (!$user->save(false)) {
                    throw new \RuntimeException('Failed to create user: ' . $spec['username']);
                }
            }

            $auth->revokeAll((string) $user->id);
            $role = $auth->getRole($spec['role']);
            if ($role === null) {
                throw new \RuntimeException('Role not found: ' . $spec['role']);
            }
            $auth->assign($role, (string) $user->id);
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        foreach ($this->users() as $spec) {
            $user = User::findOne(['username' => $spec['username']]);
            if ($user !== null) {
                $auth->revokeAll((string) $user->id);
                $this->delete('{{%user}}', ['id' => $user->id]);
            }
        }

        $viewer = $auth->getRole('viewer');
        if ($viewer !== null) {
            $auth->remove($viewer);
        }
    }
}
