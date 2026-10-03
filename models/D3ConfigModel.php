<?php

namespace d3yii2\d3config\models;

use Yii;
use yii\base\Model;

class D3ConfigModel extends Model
{


    public ?string $name = null;
    public ?string $className = null;
    public ?array $config = null;

    public function rules(): array
    {
        return [
            [['name', 'className'], 'required'],
            [['name', 'className'], 'string'],
            [['config'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Name',
            'className' => 'Class Name',
            'config' => 'Config',
        ];
    }

    public static function findAllComponents(array $accessList): array
    {
        if (!$accessList) {
            return [];
        }

        $models = [];
        foreach (Yii::$app->components as $componentName => $component) {
            /**
             * check access
             */
            $hasAccess = false;
            foreach ($accessList as $access) {
                $components = [];
                foreach ($access['components'] as $cKey => $cName){
                    if (is_array($cName)) {
                        $components[] = $cKey;
                        continue;
                    }
                    $components[] = $cName;
                }
                $hasAccess = in_array('*', $components, true)
                  || in_array($componentName, $components, true);
                if ($hasAccess) {
                    break;
                }
            }
            if (!$hasAccess) {
                continue;
            }
            $model = new self([
                'name' => $componentName,
            ]);
            if (is_array($component)) {
                $model->className = $component['class'];
                unset($component['class']);
                $model->config = $component;
            } else {
                $model->className = get_class($component);
                $model->config = get_object_vars($component);
            }
            $models[$componentName] = $model;
        }
        return $models;
    }
}