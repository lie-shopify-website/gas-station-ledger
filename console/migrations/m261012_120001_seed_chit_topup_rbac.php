<?php

use yii\db\Migration;

/**
 * 油票预存页面权限：chittopups.*，挂到 admin / accountant / counter_clerk / viewer。
 */
class m261012_120001_seed_chit_topup_rbac extends Migration
{
    private function permissions(): array
    {
        return [
            'chittopups.view' => 'View chit top ups',
            'chittopups.write' => 'Write chit top ups',
        ];
    }

    private function grants(): array
    {
        return [
            'admin' => ['chittopups.view', 'chittopups.write'],
            'accountant' => ['chittopups.view', 'chittopups.write'],
            'counter_clerk' => ['chittopups.view', 'chittopups.write'],
            'viewer' => ['chittopups.view'],
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