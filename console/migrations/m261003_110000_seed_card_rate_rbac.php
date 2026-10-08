<?php

use yii\db\Migration;

/**
 * 费率表页面权限：rates.view / rates.write，挂到 admin / accountant / counter_clerk / viewer。
 */
class m261003_110000_seed_card_rate_rbac extends Migration
{
    private function permissions(): array
    {
        return [
            'rates.view' => 'View card rates',
            'rates.write' => 'Write card rates',
        ];
    }

    private function grants(): array
    {
        return [
            'admin' => ['rates.view', 'rates.write'],
            'accountant' => ['rates.view', 'rates.write'],
            'counter_clerk' => ['rates.view'],
            'viewer' => ['rates.view'],
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
