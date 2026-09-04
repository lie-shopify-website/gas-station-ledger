<?php

namespace common\models;

use common\components\LanguageBootstrap;
use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', '用户名'),
            'password' => Yii::t('app', '密码'),
            'rememberMe' => Yii::t('app', '记住我'),
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, Yii::t('app', '用户名或密码不正确。'));
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $loggedIn = Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 30 : 0);
            if ($loggedIn) {
                $guestLang = Yii::$app->session->get('language');
                if (LanguageBootstrap::isSupported($guestLang) && $user->language !== $guestLang) {
                    $user->language = $guestLang;
                    $user->save(false, ['language']);
                }
                Yii::$app->language = $user->language ?: LanguageBootstrap::DEFAULT;
            }
            return $loggedIn;
        }
        return false;
    }

    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }
}
