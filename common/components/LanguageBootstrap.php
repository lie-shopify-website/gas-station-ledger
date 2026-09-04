<?php

namespace common\components;

use Yii;
use yii\base\BootstrapInterface;

class LanguageBootstrap implements BootstrapInterface
{
    public const DEFAULT = 'zh-CN';

    public const SUPPORTED = ['zh-CN', 'en'];

    public function bootstrap($app)
    {
        $app->language = self::resolveLanguage($app);
    }

    public static function resolveLanguage($app): string
    {
        if (!$app->user->isGuest) {
            $lang = $app->user->identity->language ?? self::DEFAULT;
            if (self::isSupported($lang)) {
                return $lang;
            }
        }

        $sessionLang = $app->session->get('language');
        if (self::isSupported($sessionLang)) {
            return $sessionLang;
        }

        $cookieLang = $app->request->cookies->getValue('language');
        if (self::isSupported($cookieLang)) {
            return $cookieLang;
        }

        return self::DEFAULT;
    }

    public static function isSupported(?string $lang): bool
    {
        return $lang !== null && in_array($lang, self::SUPPORTED, true);
    }

    public static function persistLanguage(string $lang): void
    {
        if (!self::isSupported($lang)) {
            return;
        }

        Yii::$app->language = $lang;
        Yii::$app->session->set('language', $lang);

        Yii::$app->response->cookies->add(new \yii\web\Cookie([
            'name' => 'language',
            'value' => $lang,
            'expire' => time() + 86400 * 365,
        ]));

        if (!Yii::$app->user->isGuest) {
            /** @var \common\models\User $user */
            $user = Yii::$app->user->identity;
            if ($user->language !== $lang) {
                $user->language = $lang;
                $user->save(false, ['language']);
            }
        }
    }
}
