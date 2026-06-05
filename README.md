#D3Config"

## Features


## Installation

To base composer.json to reposities add
```json
    {
      "type": "git",
      "url": "https://gitlab2.weberp.lv/d3yii2/d3config.git"
    },
```

Too app composer.json require add

```json
"d3yii2/d3config": "dev-master"
```

## migration
Add to console config controllerMap>>migrate>>migrationPath
```php
'@vendor/d3yii2/d3config/migrations',
```

Translation
```php
    'd3config => [
        'class' => \'yii\i18n\PhpMessageSource\',
        'basePath' => '@vendor/d3yii2/d3config/messages',
        'sourceLanguage' => 'en-US',
    ],
```






## Methods


## Usage

## Examples
