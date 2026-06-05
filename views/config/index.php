<?php

use d3system\yii2\web\D3SystemView;
use d3yii2\d3config\models\D3ConfigModel;
use eaBlankonThema\assetbundles\layout\LayoutAsset;
use eaBlankonThema\widget\ThGridView;
use yii\data\ActiveDataProvider;

/**
 * @var D3SystemView $this
 * @var D3ConfigModel[] $models
 */

LayoutAsset::register($this);


$this->title = Yii::t('d3config', 'List');
$this->setPageHeader($this->title);

?>
<div class="row">
    <div class="col-md-12">
        <?= ThGridView::widget([
            'dataProvider' => new ActiveDataProvider([
                'models' => $models,
                'pagination' => false
            ]),
            'actionColumnTemplate' => '{view}',
            'columns' => [
                [
                    'attribute' => 'name',
                    'contentOptions' => ['class' => 'text-left'],
                ],
                [
                    'attribute' => 'className',
                    'contentOptions' => ['class' => 'text-left'],
                ],

            ],
        ])?>
    </div>
</div>
