<?php

use d3system\yii2\web\D3SystemView;
use d3yii2\d3config\models\D3ConfigModel;
use eaBlankonThema\assetbundles\layout\LayoutAsset;
use eaBlankonThema\widget\ThAlertList;
use eaBlankonThema\widget\ThDetailView;
use eaBlankonThema\widget\ThReturnButton;
use yii\helpers\VarDumper;

LayoutAsset::register($this);

/**
 * @var D3SystemView $this
 * @var D3ConfigModel $model
 * @var object $component
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
                $value = $property->getValue($component);
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
                    <td class="text-left"><?= VarDumper::dumpAsString($value, 10, true) ?></td>
                    <td class="text-left">
                        <pre class="text-left"><?= implode("\n", $fixedComment) ?></pre>
                    </td>
                </tr>
                <?php
            }
            ?></table>
    </div>
</div>
