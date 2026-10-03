<?php

namespace d3yii2\d3config\models;

use RuntimeException;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\helpers\Json;

class SettingModel extends Model
{
   public const TYPE_GLOBAL = 'global';
   public const TYPE_SYS_COMPANY = 'sys_company';
   public const TYPE_USER_GLOBAL = 'user_global';
   public const TYPE_USER_SYS_COMPANY = 'user_sys_company';

    public ?string $value = null;
    public ?string $configPath = null;
    public ?string $componentName = null;
    public ?string $settingName = null;
    public ?string $type = null;
    public ?string $username = null;
    public ?int $sysCompanyId = null;

    public function rules(): array
    {
        return [
            [['value'], 'required'],
            [['value'], 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'value' => 'Value',
        ];
    }

    /**
     * username is used as directory name - prevent path traversal
     */
    public static function isValidUsername(string $username): bool
    {
        return (bool)preg_match('/^[\w.@-]+$/u', $username)
            && $username !== '.'
            && $username !== '..';
    }

    public function createPath(): string
    {
        if ($this->type === self::TYPE_GLOBAL) {
            return self::TYPE_GLOBAL;
        }
        if (in_array($this->type, [self::TYPE_SYS_COMPANY, self::TYPE_USER_SYS_COMPANY], true)
            && (int)$this->sysCompanyId <= 0
        ) {
            throw new InvalidArgumentException('Invalid sysCompanyId "' . $this->sysCompanyId . '"');
        }
        if (in_array($this->type, [self::TYPE_USER_GLOBAL, self::TYPE_USER_SYS_COMPANY], true)
            && !self::isValidUsername((string)$this->username)
        ) {
            throw new InvalidArgumentException('Invalid username "' . $this->username . '"');
        }

        if ($this->type === self::TYPE_SYS_COMPANY) {
            return self::TYPE_SYS_COMPANY . '/' . $this->sysCompanyId;
        }
        if ($this->type === self::TYPE_USER_GLOBAL) {
            return self::TYPE_USER_GLOBAL . '/' . $this->username;
        }

        if ($this->type === self::TYPE_USER_SYS_COMPANY) {
            return self::TYPE_USER_SYS_COMPANY . '/' . $this->username. '/' . $this->sysCompanyId;
        }
        throw new InvalidArgumentException('Invalid type"'.$this->type.'"');
    }

    public function save(): bool
    {
        $dirPath = $this->configPath
            . '/' . $this->createPath()
            . '/components'
            . '/' . $this->componentName;

        if (!file_exists($dirPath) && !mkdir($dirPath, 0775, true) && !is_dir($dirPath)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dirPath));
        }
        $filePath = $dirPath . '/' . $this->settingName . '.json';

        /** write to temp file and rename - readers never get half-written file */
        $tmpFilePath = $filePath . '.tmp';
        if (file_put_contents($tmpFilePath, Json::encode($this->value), LOCK_EX) === false
            || !rename($tmpFilePath, $filePath)
        ) {
            throw new RuntimeException(sprintf('Setting file "%s" was not saved', $filePath));
        }

        return true;
    }

}