<?php

namespace d3yii2\d3config\controllers;


use cornernote\returnurl\ReturnUrl;
use d3yii2\d3config\components\D3ConfigComponent;
use d3yii2\d3config\models\D3ConfigModel;
use d3yii2\d3config\models\SettingModel;
use d3yii2\d3config\Module;
use eaBlankonThema\yii2\web\LayoutController;
use ReflectionClass;
use ReflectionException;
use Yii;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;


/**
 * @property Module $module
 */
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
                        'actions' => ['index', 'view','update'],
                        /** empty roles in AccessRule allow everybody */
                        'roles' => $this->module->controllerAccessRoles ?: ['SystemAdmin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'view' => ['GET'],
                    'update' => ['GET', 'POST'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        /** settings change must be protected, also if CSRF validation disabled in application */
        Yii::$app->request->enableCsrfValidation = true;
        $this->enableCsrfValidation = true;
        return parent::beforeAction($action);
    }

    public function actionIndex(): string
    {
        $user = Yii::$app->user;
        $accessList = $this->getAccessList($user, 'read');

        return $this->render(
            'index',
            [
                'models' => D3ConfigModel::findAllComponents($accessList),
            ]
        );
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionView(string $id): string
    {
        $user = Yii::$app->user;
        $accessList = $this->getAccessList($user, 'read', $id);
        [$viewSettings, $updateSettings] = $this->getAccess($accessList, $id);
        $componentConfig = D3ConfigModel::findAllComponents($accessList)[$id] ?? null;
        if (!$componentConfig || !$viewSettings) {
            throw new ForbiddenHttpException('You are not allowed to view this component.');
        }

        return $this->render(
            'view',
            [
                'model' => $componentConfig,
                'component' => Yii::$app->$id,
                'viewSettings' => $viewSettings,
                'updateSettings' => $updateSettings ?? []
            ]
        );
    }

    /**
     * @throws ReflectionException
     * @throws ForbiddenHttpException
     * @throws Exception
     */
    public function actionUpdate(string $componentName, string $settingName)
    {
        $user = Yii::$app->user;
        $accessList = $this->getAccessList($user, 'update', $componentName);
        [$viewSettings, $updateSettings] = $this->getAccess($accessList, $componentName);

        $canEdit = $updateSettings === '*'
            || (is_array($updateSettings) && in_array($settingName, $updateSettings, true));
        $componentConfig = D3ConfigModel::findAllComponents($accessList)[$componentName] ?? null;
        if (!$canEdit
            || !$componentConfig
            || in_array($settingName, D3ConfigComponent::SYSTEM_PROPERTIES, true)
        ) {
            throw new ForbiddenHttpException('You are not allowed to edit this setting.');
        }
        $component = Yii::$app->$componentName;
        $reflection = new ReflectionClass($component);
        if (!$reflection->hasProperty($settingName)) {
            throw new ForbiddenHttpException('You are not allowed to edit this setting.');
        }
        $settingProperty = $reflection->getProperty($settingName);
        if (!$settingProperty->isPublic() || $settingProperty->isStatic()) {
            throw new ForbiddenHttpException('You are not allowed to edit this setting.');
        }
        $model = new SettingModel([
            'value' => $settingProperty->getValue($component),
            'configPath' => $this->module->getConfigPath(),
            'componentName' => $componentName,
            'settingName' => $settingName,
            'type' => SettingModel::TYPE_SYS_COMPANY,
            'username' => Yii::$app->user->identity->username,
            'sysCompanyId' => Yii::$app->SysCmp->getActiveCompanyId()

        ]);
        if (($post = Yii::$app->request->post())
            && $model->load($post)
            && $model->save()
        ) {
            return $this->redirect(ReturnUrl::getUrl());
        }


        return $this->render(
            'update',
            [
                'componentConfig' => $componentConfig,
                'model' => $model,
                'component' => $component,
                'viewSettings' => $viewSettings,
                'settingName' => $settingName
            ]
        );

    }

    /**
     * @param $user
     * @param string $action
     * @param string|null $componentName
     * @return array
     */
    public function getAccessList($user, string $action, ?string $componentName = null): array
    {
        $accessList = [];
        foreach ($this->module->rolesAccess as $access) {
            /** create component name list */
            $componentNames = [];
            foreach ($access['components'] as $cKey => $cValue) {
                if (is_array($cValue)) {
                    $componentNames[] = $cKey;
                } else {
                    $componentNames[] = $cValue;
                }
            }
            foreach ($access['roles'] as $role) {
                if (
                    (
                        in_array('*', $access['actions'], true)
                        || in_array($action, $access['actions'], true)
                    )
                    && (!$componentName || in_array($componentName, $componentNames, true))
                    && $user->can($role)
                ) {
                    $accessList[] = $access;
                    break;
                }
            }
        }
        return $accessList;
    }

    /**
     * @param array $accessList
     * @param string $id
     * @return array|array[]|null[]|string[]
     */
    public function getAccess(array $accessList, string $id): array
    {
        $viewSettings = null;
        $updateSettings = null;
        foreach ($accessList as $access) {
            //if ($viewSettings !== '*') {
            foreach ($access['components'] as $componentKey => $component) {
                if (is_array($component)) {
                    $componentName = $componentKey;
                } else {
                    $componentName = $component;
                }
                if ($componentName !== $id) {
                    continue;
                }
                if (!is_array($component)) {
                    $viewSettings = '*';
                }
                if ($viewSettings !== '*') {
                    if (($component['viewSettings'] ?? '') === '*') {
                        $viewSettings = '*';
                    } else {
                        if (!$viewSettings) {
                            $viewSettings = [];
                        }
                        foreach ($component['viewSettings'] ?? [] as $viewItem) {
                            $viewSettings[] = $viewItem;
                        }
                    }
                }
                if ($updateSettings !== '*' && is_array($component)) {
                    if (($component['updateSettings'] ?? '') === '*') {
                        $updateSettings = '*';
                    } else {
                        if (!$updateSettings) {
                            $updateSettings = [];
                        }
                        foreach ($component['updateSettings'] ?? [] as $viewItem) {
                            $updateSettings[] = $viewItem;
                        }
                    }
                }
            }
        }
        return array($viewSettings, $updateSettings);
    }

}
