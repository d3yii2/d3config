<?php

use cornernote\returnurl\ReturnUrl;
use d3system\yii2\web\D3SystemView;
use d3yii2\d3config\models\D3ConfigModel;
use eaBlankonThema\assetbundles\layout\LayoutAsset;
use eaBlankonThema\widget\ThAlertList;
use eaBlankonThema\widget\ThButton;
use eaBlankonThema\widget\ThDetailView;
use eaBlankonThema\widget\ThReturnButton;
use yii\helpers\VarDumper;

LayoutAsset::register($this);

/**
 * @var D3SystemView $this
 * @var D3ConfigModel $model
 * @var object $component
 * @var array $viewSettings
 * @var array $updateSettings
 */

$this->title = 'Config "'
    . $model->name
    . '"';
$this->setPageHeader($this->title);
$this->addPageButtons(ThReturnButton::widget([
    'backUrl' => [
        'index',
    ]
]));


$this->beginBlock('DETAILS');


echo ThDetailView::widget([
    'model' => $model,
    'attributes' => [
        'name:ntext',
        'className'
    ],
]);
$this->endBlock();
$ru = ReturnUrl::getToken();
?>
<div class="row">
    <?= ThAlertList::widget() ?>
    <div class="col-md-4">
        <div class="panel  rounded shadow">
            <div class="panel-body rounded-bottom cwpv-sale">
                <?= $this->blocks['DETAILS'] ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-success dataTable table-striped">
            <tr>
                <th>Name</th>
                <th>Value</th>
                <th>Comment</th>
            </tr>
            <?php
            $reflection = new ReflectionClass($component);
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $name = $property->getName();
                if ($viewSettings !== '*' && !in_array($name, $viewSettings, true)) {
                    continue;
                }
                $canEdit = $updateSettings === '*' || in_array($name, $updateSettings, true);
                $value = $property->getValue($component);
                $button = '';
                if ($canEdit) {
                    $button = ThButton::widget([
                        'icon' => ThButton::ICON_PENCIL,
                        'type' => ThButton::TYPE_PRIMARY,
                        'size' => ThButton::SIZE_XSMALL,
                        'link' => [
                            'update',
                            'componentName' => $model->name,
                            'settingName' => $name,
                            'ru' => $ru
                        ]
                    ]);
                }
                $comment = $property->getDocComment();
                $fixedComment = [];
                foreach (explode("\n", $comment) as $line) {
                    $line = trim(trim($line), '*/ ');
                    if (!$line) {
                        continue;
                    }
                    $fixedComment[] = $line;
                }

                ?>
                <tr>
                    <td class="text-left"><?= $name ?></td>
                    <td class="text-left"><?= VarDumper::dumpAsString($value, 10, true) ?><?=$button?></td>
                    <td class="text-left">
                        <pre class="text-left"><?= implode("\n", $fixedComment) ?></pre>
                    </td>
                </tr>
                <?php
            }
            ?></table>
    </div>
</div>
