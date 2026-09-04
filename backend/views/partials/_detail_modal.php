<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

$loadingText = Json::encode(Yii::t('app', '加载中…'));
$errorText = Json::encode(Yii::t('app', '加载失败，请重试。'));

?>
<div class="modal fade" id="gsl-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= Html::encode(Yii::t('app', '关闭')) ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>
<?php
$this->registerJs(<<<JS
$(document).on('click', '.gsl-detail-modal-trigger', function () {
    var btn = $(this);
    var modal = $('#gsl-detail-modal');
    var title = btn.data('title') || '';
    var url = btn.data('url');
    var contentSelector = btn.data('content');
    var body = modal.find('.modal-body');

    modal.find('.modal-title').text(title);

    if (contentSelector) {
        var source = $(contentSelector);
        body.html(source.length ? source.html() : '');
        modal.modal('show');
        return;
    }

    if (!url) {
        return;
    }

    body.html('<p class="text-muted text-center py-4 mb-0">' + {$loadingText} + '</p>');
    modal.modal('show');
    $.get(url).done(function (html) {
        body.html(html);
    }).fail(function () {
        body.html('<p class="text-danger text-center py-4 mb-0">' + {$errorText} + '</p>');
    });
});
JS, View::POS_READY, 'gsl-detail-modal');
?>
