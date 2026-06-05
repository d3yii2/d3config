<?php

namespace d3yii2\d3config\controllers;


use d3yii2\d3config\models\D3ConfigModel;
use eaBlankonThema\yii2\web\LayoutController;
use yii\filters\AccessControl;

class ConfigController extends LayoutController
{

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {

        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index','view'],
                        'roles' => [$this->module->viewRole],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render(
            'index',
            [
                'models' => D3ConfigModel::findAllComponents(),
            ]
        );
    }

    public function actionView(string $id): string
    {
        return $this->render(
            'view',
            [
                'model' => D3ConfigModel::findAllComponents()[$id],
                'component' => \Yii::$app->$id,
            ]
        );
    }

}
