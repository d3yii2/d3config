# D3Config

## Features

Web UI for viewing and editing application component settings.
Edited values are stored as JSON files, one file per setting:

```
{configPath}/{type}/components/{componentName}/{settingName}.json
```

`{type}` is one of:

| Type               | Directory                                    |
|--------------------|----------------------------------------------|
| `global`           | `global`                                     |
| `sys_company`      | `sys_company/{sysCompanyId}`                 |
| `user_global`      | `user_global/{username}`                     |
| `user_sys_company` | `user_sys_company/{username}/{sysCompanyId}` |

The setting value is stored JSON-encoded.

## Installation

To base composer.json to reposities add
```json
    {
      "type": "git",
      "url": "https://gitlab2.weberp.lv/d3yii2/d3config.git"
    }
```

Too app composer.json require add

```json
"d3yii2/d3config": "dev-master"
```

### Settings path

Define alias `@d3configData` in the aliases shared by all applications
(web, console, api), so the module (writes) and components (read) use the same directory:

```php
'aliases' => [
    '@d3configData' => $basePath . '/runtime-d3config',
],
```

Both `Module::$configPath` and `D3ConfigComponent::$configPath` default to `@d3configData`
and are resolved with `Yii::getAlias()` in `init()`.

## migration
Add to console config controllerMap>>migrate>>migrationPath
```php
'@vendor/d3yii2/d3config/migrations',
```

Translation
```php
    'd3config' => [
        'class' => 'yii\i18n\PhpMessageSource',
        'basePath' => '@vendor/d3yii2/d3config/messages',
        'sourceLanguage' => 'en-US',
    ],
```

## Usage

### Module

```php
'modules' => [
    'd3config' => [
        'class' => 'd3yii2\d3config\Module',
        'leftMenu' => 'company',
        'controllerAccessRoles' => ['SystemAdmin'],
        'rolesAccess' => [
            [
                'roles' => ['SystemAdmin'],
                'components' => ['*'],
                'actions' => ['read'],
            ],
            [
                'roles' => ['CwPavadzimesFull'],
                'components' => [
                    'hrPavadzimeCewoodDE',            // view all settings
                    'hrPavadzimeCewoodFI' => [
                        'viewSettings' => '*',
                        'updateSettings' => ['PVZ_IEN_DISCOUNT'], // edit PVZ_IEN_DISCOUNT
                    ],
                ],
                'actions' => ['read', 'update'],
            ],
        ],
    ],
],
```

Security:
- `controllerAccessRoles` empty → only `SystemAdmin` can open the controller.
- `read` action gives view, `update` action is required to save settings.
- CSRF validation is always on in this controller, also if disabled in the application.
- Only public non-static properties can be edited; `configPath`, `componentName`,
  `loadSettings` never.
- `viewSettings => '*'` shows **all** public property values (passwords, tokens too),
  `updateSettings => '*'` allows changing **all** public properties (class names, IPs, paths).
  Prefer explicit lists.

### Component

Extend `D3ConfigComponent`. After `init()`, `$this->configPath` contains the absolute path
to the settings directory.

```php
use d3yii2\d3config\components\D3ConfigComponent;

class ConfigComponent extends D3ConfigComponent
{
    public ?string $ip = null;
    public int $port = 502;
}
```

Per-component path override:

```php
'components' => [
    'kalteConfig' => [
        'class' => ConfigComponent::class,
        'configPath' => '@app/other-dir',
    ],
],
```

#### Stored settings

In `init()` the settings saved by the module are applied over config values.
Directories are read in order, later overrides earlier:

1. `global`
2. `sys_company/{activeCompanyId}`
3. `user_global/{username}`
4. `user_sys_company/{username}/{activeCompanyId}`

Only public non-static properties are set. Values are cast to the property type
(`int`, `float`, `bool`, `array`, `string`, nullable `''` → `null`).

Child classes must call `parent::init()` before using settings.

The component ID (settings directory name) is detected automatically from
`Yii::$app->get($id)`, also when one class is used by several components.
For objects created outside the application locator (`Yii::createObject()`, `new`) the ID
is found by class only if the class is used by one component; otherwise set it explicitly:

```php
$config = Yii::createObject([
    'class' => ConfigComponent::class,
    'componentName' => 'kalte3Config',
]);
```

Disable loading: `'loadSettings' => false`. Reload manually: `$component->applySettings()`.

## Methods


## Examples
