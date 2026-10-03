<?php

use cornernote\returnurl\ReturnUrl;
use d3system\yii2\web\D3SystemView;
use d3yii2\d3config\models\SettingModel;
use eaBlankonThema\assetbundles\layout\LayoutAsset;
use eaBlankonThema\widget\ThAlertList;
use eaBlankonThema\widget\ThButton;
use eaBlankonThema\widget\ThDetailView;
use eaBlankonThema\widget\ThReturnButton;
use kartik\form\ActiveForm;

LayoutAsset::register($this);

/**
 * @var D3SystemView $this
 * @var SettingModel $model
 * @var SettingModel $componentConfig
 * @var object $component
 * @var array $viewSettings
 * @var array $settingName
 */

$this->title = 'Update "' . $componentConfig->name . '"';
$this->setPageHeader($this->title);
$this->addPageButtons(ThReturnButton::widget(['backUrl' => ReturnUrl::getUrl()]));
$ru = ReturnUrl::getToken();
?>
<div class="row">
    <?= ThAlertList::widget() ?>
    <div class="col-md-4">
        <div class="panel  rounded shadow">
            <div class="panel-body rounded-bottom cwpv-sale">
                <?= ThDetailView::widget([
                    'model' => $componentConfig,
                    'attributes' => [
                        'name:ntext',
                        'className'
                    ],
                ]) ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <?php
        $form = ActiveForm::begin([
            'enableClientValidation' => true,
            'errorSummaryCssClass' => 'error-summary alert alert-error',
        ]);
        echo $form
            ->field($model, 'value')
            ->textInput();
        echo ThButton::widget([
            'label' => 'Save',
            'icon' => ThButton::ICON_CHECK,
            'type' => ThButton::TYPE_SUCCESS,
            'submit' => true,
            'multiClickOff' => true,
            'htmlOptions' => [
                'name' => 'action',
                'value' => 'save',
            ],
        ]);
        ActiveForm::end();
        ?>
    </div>
</div>
