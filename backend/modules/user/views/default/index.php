<?php

/** @var yii\web\View $this */
/** @var common\models\User[] $users */
/** @var string[] $roleNames */
/** @var array $userRoles */
/** @var backend\modules\user\models\AssignRoleForm $assignModel */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = Yii::t('app', '用户管理');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= Yii::t('app', '用户列表') ?></h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= Yii::t('app', '用户名') ?></th>
                            <th><?= Yii::t('app', '邮箱') ?></th>
                            <th><?= Yii::t('app', '状态') ?></th>
                            <th><?= Yii::t('app', '角色') ?></th>
                            <th><?= Yii::t('app', '须改密码') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= (int) $user->id ?></td>
                            <td><?= Html::encode($user->username) ?></td>
                            <td><?= Html::encode($user->email) ?></td>
                            <td><?= $user->status == \common\models\User::STATUS_ACTIVE ? Yii::t('app', '启用') : Yii::t('app', '停用') ?></td>
                            <td><?= Html::encode($userRoles[$user->id] ?? '—') ?></td>
                            <td><?= $user->must_change_password ? Yii::t('app', '是') : Yii::t('app', '否') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title"><?= Yii::t('app', '分配角色') ?></h3></div>
            <div class="card-body">
                <?php $form = ActiveForm::begin(); ?>
                <?= $form->field($assignModel, 'user_id')->dropDownList(
                    ArrayHelper::map($users, 'id', fn($u) => $u->username . ' (' . $u->email . ')'),
                    ['prompt' => Yii::t('app', '选择用户')]
                ) ?>
                <?= $form->field($assignModel, 'role')->dropDownList(
                    array_combine($roleNames, $roleNames),
                    ['prompt' => Yii::t('app', '选择角色')]
                ) ?>
                <div class="form-group">
                    <?= Html::submitButton(Yii::t('app', '分配'), ['class' => 'btn btn-success']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
