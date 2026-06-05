<?php

namespace d3yii2\d3config;

use Yii;
use d3system\yii2\base\D3Module;

class Module extends D3Module
{
    public $controllerNamespace = 'd3yii2\d3config\controllers';

    public $leftMenu = 'd3yii2\d3config\LeftMenu';

    public $viewRole = 'SystemAdmin';

    public function getLabel(): string
    {
        return Yii::t('d3config','D3Config');
    }
}
