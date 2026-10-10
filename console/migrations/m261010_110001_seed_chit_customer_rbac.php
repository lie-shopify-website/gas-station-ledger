<?php

use yii\db\Migration;

/**
 * 油票客户页面权限：chitcustomers.*，挂到 admin / accountant / counter_clerk / viewer。
 */
class m261010_110001_seed_chit_customer_rbac extends Migration
{
    private function permissions(): array
    {
        return [
            'chitcustomers.view' => 'View chit customers',
            'chitcustomers.write' => 'Write chit customers',
        ];
    }

    private function grants(): array
    {
        return [
            'admin' => ['chitcustomers.view', 'chitcustomers.write'],
            'accountant' => ['chitcustomers.view'],
            'counter_clerk' => ['chitcustomers.view'],
            'viewer' => ['chitcustomers.view'],
        ];
    }

    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        foreach ($this->permissions() as $name => $description) {
            if ($auth->getPermission($name) !== null) {
                continue;
            }
            $perm = $auth->createPermission($name);
            $perm->description = $description;
            $auth->add($perm);
        }

        foreach ($this->grants() as $roleName => $names) {
            $role = $auth->getRole($roleName);
            if ($role === null) {
                continue;
            }
            foreach ($names as $name) {
                $perm = $auth->getPermission($name);
                if ($perm !== null && !$auth->hasChild($role, $perm)) {
                    $auth->addChild($role, $perm);
                }
            }
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        foreach ($this->grants() as $roleName => $names) {
            $role = $auth->getRole($roleName);
            if ($role === null) {
                continue;
            }
            foreach ($names as $name) {
                $perm = $auth->getPermission($name);
                if ($perm !== null && $auth->hasChild($role, $perm)) {
                    $auth->removeChild($role, $perm);
                }
            }
        }

        foreach (array_keys($this->permissions()) as $name) {
            $perm = $auth->getPermission($name);
            if ($perm !== null) {
                $auth->remove($perm);
            }
        }
    }
}