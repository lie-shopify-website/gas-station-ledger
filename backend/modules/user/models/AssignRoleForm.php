<?php

namespace backend\modules\user\models;

use Yii;
use yii\base\Model;

class AssignRoleForm extends Model
{
    public $user_id;
    public $role;

    public function rules()
    {
        return [
            [['user_id', 'role'], 'required'],
            ['user_id', 'integer'],
            ['role', 'string', 'max' => 64],
        ];
    }

    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('app', '用户'),
            'role' => Yii::t('app', '角色'),
        ];
    }
}
