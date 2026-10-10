<?php

use yii\db\Migration;

/**
 * 油票登记/柜台管理页面权限：chits.* / counters.*，挂到 admin / accountant / counter_clerk / viewer。
 */
class m261010_100001_seed_chit_rbac extends Migration
{
    private function permissions(): array
    {
        return [
            'chits.view' => 'View chits',
            'chits.write' => 'Write chits',
            'counters.view' => 'View counters',
            'counters.write' => 'Write counters',
        ];
    }

    private function grants(): array
    {
        return [
            'admin' => ['chits.view', 'chits.write', 'counters.view', 'counters.write'],
            'accountant' => ['chits.view', 'chits.write', 'counters.view'],
            'counter_clerk' => ['chits.view', 'chits.write', 'counters.view'],
            'viewer' => ['chits.view', 'counters.view'],
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