<?php

namespace MxcDropshipInnocigs\PluginListeners;

use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Models\DropshipModule;
use MxcDropship\MxcDropship;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class RegisterDropshipModule implements AugmentedObject
{
    use ModelManagerAwareTrait;

    public function install(InstallContext $context)
    {
        if (! class_exists(MxcDropship::class)) return;

        $module = new DropshipModule();
        $module->setName(MxcDropshipInnocigs::getDropshipModuleName());
        $module->setSupplier(MxcDropshipInnocigs::getDropshipModuleSupplier());
        $plugin = strstr(__CLASS__, '\\', true);
        $module->setPlugin($plugin);
        $this->modelManager->persist($module);
        $this->modelManager->flush();
    }

    public function uninstall(UninstallContext $context)
    {
        if (! class_exists(MxcDropship::class)) return;

        $repo = $this->modelManager->getRepository(DropshipModule::class);
        $module = $repo->findOneBy(['name' => MxcDropshipInnocigs::getDropshipModuleName()]);
        if ($module instanceof DropshipModule) {
            $this->modelManager->remove($module);
            $this->modelManager->flush();
        }
    }

    public function activate(ActivateContext $context)
    {
        if (! class_exists(MxcDropship::class)) return;
        $this->activateModule(true);
    }

    public function deactivate(DeactivateContext $context)
    {
        if (! class_exists(MxcDropship::class)) return;
        $this->activateModule(false);
    }

    protected function activateModule(bool $active)
    {
        $repo = $this->modelManager->getRepository(DropshipModule::class);
        $module = $repo->findOneBy(['name' => MxcDropshipInnocigs::getDropshipModuleName()]);
        if ($module instanceof DropshipModule) {
            $module->setActive($active);
            $this->modelManager->flush();
        }
    }
}