<?php

namespace common\models;

use Yii;
use yii\base\Model;

class ChangePasswordForm extends Model
{
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;

    public function rules()
    {
        return [
            [['currentPassword', 'newPassword', 'confirmPassword'], 'required'],
            ['newPassword', 'string', 'min' => 6],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword'],
            ['currentPassword', 'validateCurrentPassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'currentPassword' => Yii::t('app', '当前密码'),
            'newPassword' => Yii::t('app', '新密码'),
            'confirmPassword' => Yii::t('app', '确认新密码'),
        ];
    }

    public function validateCurrentPassword($attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }
        /** @var User $user */
        $user = Yii::$app->user->identity;
        if (!$user->validatePassword($this->currentPassword)) {
            $this->addError($attribute, Yii::t('app', '当前密码不正确。'));
        }
    }

    public function changePassword(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $user->setPassword($this->newPassword);
        $user->must_change_password = 0;
        $user->password_changed_at = time();
        return $user->save(false);
    }
}
