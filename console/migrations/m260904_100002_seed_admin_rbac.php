<?php

use yii\db\Migration;

class m260904_100002_seed_admin_rbac extends Migration
{
    private function permissions(): array
    {
        return [
            'fills.view' => 'View fills',
            'fills.write' => 'Write fills',
            'swipes.view' => 'View swipes',
            'swipes.write' => 'Write swipes',
            'prices.view' => 'View prices',
            'prices.write' => 'Write prices',
            'plates.view' => 'View plates',
            'plates.write' => 'Write plates',
            'daily.view' => 'View daily summary',
            'daily.write' => 'Write daily tickets',
            'counter.view' => 'View counter summary',
            'dashboard.view' => 'View dashboard',
            'cards.view' => 'View cards',
            'cards.write' => 'Write cards',
            'settings.write' => 'Write settings',
            'users.manage' => 'Manage users',
        ];
    }

    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $time = time();

        foreach ($this->permissions() as $name => $description) {
            $perm = $auth->createPermission($name);
            $perm->description = $description;
            $auth->add($perm);
        }

        $admin = $auth->createRole('admin');
        $admin->description = 'Administrator';
        $auth->add($admin);
        foreach (array_keys($this->permissions()) as $name) {
            $auth->addChild($admin, $auth->getPermission($name));
        }

        $counterClerk = $auth->createRole('counter_clerk');
        $counterClerk->description = 'Counter clerk';
        $auth->add($counterClerk);
        foreach (['fills.write', 'fills.view', 'swipes.view', 'prices.view', 'plates.view', 'daily.view', 'counter.view', 'dashboard.view', 'cards.view'] as $name) {
            $auth->addChild($counterClerk, $auth->getPermission($name));
        }

        $accountant = $auth->createRole('accountant');
        $accountant->description = 'Accountant';
        $auth->add($accountant);
        foreach (['swipes.write', 'swipes.view', 'fills.view', 'daily.view', 'counter.view', 'dashboard.view', 'cards.view', 'prices.view', 'plates.view'] as $name) {
            $auth->addChild($accountant, $auth->getPermission($name));
        }

        $user = new \common\models\User();
        $user->username = 'leonliu';
        $user->email = 'fkgo9999@gmail.com';
        $user->status = \common\models\User::STATUS_ACTIVE;
        $user->must_change_password = 0;
        $user->setPassword('lie123456');
        $user->generateAuthKey();
        if (!$user->save(false)) {
            throw new \RuntimeException('Failed to seed admin user');
        }

        $auth->assign($admin, (string) $user->id);
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();

        $this->delete('{{%user}}', ['email' => 'fkgo9999@gmail.com']);
    }
}
