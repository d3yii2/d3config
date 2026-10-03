<?php

namespace d3yii2\d3config\components;

use Closure;
use d3yii2\d3config\models\SettingModel;
use ReflectionNamedType;
use ReflectionProperty;
use Throwable;
use Yii;
use yii\base\Component;
use yii\db\Exception;
use yii\helpers\Json;

/**
 * Base component for settings editable with d3config module.
 * Stored settings are applied over config values in init()
 */
class D3ConfigComponent extends Component
{

    /** @var string path or alias to d3config settings files directory */
    public string $configPath = '@d3configDataDir';

    /** @var string|null application component ID; auto-detected, if not set */
    public ?string $componentName = null;

    /** @var bool apply settings stored by d3config module */
    public bool $loadSettings = true;

    /** properties, which can not be changed by stored settings */
    public const SYSTEM_PROPERTIES = ['configPath', 'componentName', 'loadSettings'];

    public function init(): void
    {
        parent::init();
        $this->configPath = Yii::getAlias($this->configPath);
        if ($this->loadSettings) {
            $this->applySettings();
        }
    }

    /**
     * apply stored settings. Later directories override earlier
     */
    public function applySettings(): void
    {
        if (!$this->componentName && !$this->componentName = $this->detectComponentName()) {
            return;
        }
        foreach ($this->getSettingsDirs() as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.json') as $filePath) {
                $settingName = basename($filePath, '.json');
                if (in_array($settingName, self::SYSTEM_PROPERTIES, true)
                    || !property_exists($this, $settingName)
                ) {
                    continue;
                }
                $property = new ReflectionProperty($this, $settingName);
                if (!$property->isPublic() || $property->isStatic()) {
                    continue;
                }
                try {
                    $value = Json::decode(file_get_contents($filePath));
                    $this->$settingName = $this->castValue($property, $value);
                } catch (Throwable $e) {
                    /** broken file must not stop component */
                    Yii::error('Can not apply setting file ' . $filePath . ': ' . $e->getMessage(), __METHOD__);
                }
            }
        }
    }

    /**
     * find application component ID of this object
     */
    protected function detectComponentName(): ?string
    {
        if (!Yii::$app) {
            return null;
        }
        $definitions = Yii::$app->getComponents();

        /**
         * component is created in ServiceLocator::get($id) - take ID from the closest call
         */
        foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 30) as $frame) {
            if (($frame['function'] ?? null) !== 'get'
                || ($frame['object'] ?? null) !== Yii::$app
                || !isset($frame['args'][0])
                || !is_string($frame['args'][0])
            ) {
                continue;
            }
            $id = $frame['args'][0];
            if (isset($definitions[$id]) && $this->getDefinitionClass($definitions[$id]) === static::class) {
                return $id;
            }
            /** created inside other component creation, not by locator */
            break;
        }

        /**
         * created outside locator - find by class, if class used once
         */
        $found = [];
        foreach ($definitions as $id => $definition) {
            if ($this->getDefinitionClass($definition) === static::class) {
                $found[] = $id;
            }
        }
        if (count($found) > 1) {
            Yii::warning(
                'Can not detect componentName for ' . static::class . '. Used by components: '
                . implode(', ', $found) . '. Stored settings not loaded',
                __METHOD__
            );
            return null;
        }
        return $found[0] ?? null;
    }

    /**
     * @param mixed $definition component definition
     */
    private function getDefinitionClass($definition): ?string
    {
        if ($definition instanceof Closure) {
            return null;
        }
        if (is_object($definition)) {
            return get_class($definition);
        }
        if (is_array($definition)) {
            $class = $definition['class'] ?? null;
        } elseif (is_string($definition)) {
            $class = $definition;
        } else {
            return null;
        }
        return $class ? ltrim($class, '\\') : null;
    }

    /**
     * setting directories ordered from general to specific
     * @return string[]
     * @throws Exception
     */
    protected function getSettingsDirs(): array
    {
        $sysCompanyId = null;
        if (Yii::$app->has('SysCmp')) {
            $sysCompanyId = (int)Yii::$app->SysCmp->getActiveCompanyId();
            if ($sysCompanyId <= 0) {
                $sysCompanyId = null;
            }
        }
        $username = null;
        if (Yii::$app->has('user')
            && !Yii::$app->user->isGuest
            && isset(Yii::$app->user->identity->username)
            && SettingModel::isValidUsername((string)Yii::$app->user->identity->username)
        ) {
            $username = Yii::$app->user->identity->username;
        }

        $paths = [SettingModel::TYPE_GLOBAL];
        if ($sysCompanyId) {
            $paths[] = SettingModel::TYPE_SYS_COMPANY . '/' . $sysCompanyId;
        }
        if ($username) {
            $paths[] = SettingModel::TYPE_USER_GLOBAL . '/' . $username;
            if ($sysCompanyId) {
                $paths[] = SettingModel::TYPE_USER_SYS_COMPANY . '/' . $username . '/' . $sysCompanyId;
            }
        }

        $dirs = [];
        foreach ($paths as $path) {
            $dirs[] = $this->configPath . '/' . $path . '/components/' . $this->componentName;
        }
        return $dirs;
    }

    /**
     * cast stored value to property type
     * @param ReflectionProperty $property
     * @param mixed $value
     * @return mixed
     */
    protected function castValue(ReflectionProperty $property, $value)
    {
        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }
        if (($value === null || $value === '') && $type->allowsNull()) {
            return null;
        }
        switch ($type->getName()) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                return is_string($value) ? (array)Json::decode($value) : (array)$value;
            case 'string':
                return (string)$value;
        }
        return $value;
    }

}
