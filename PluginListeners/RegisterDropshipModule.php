<?php

namespace MxcDropshipInnocigs\PluginListeners;

use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Models\DropshipModule;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin;

class RegisterDropshipModule implements AugmentedObject
{
    use ModelManagerAwareTrait;

    public function install(InstallContext $context)
    {
        if (! class_exists(MxcDropshipIntegrator::class)) return;

        $module = new DropshipModule();
        $module->setName('InnoCigs');
        $module->setSupplier('InnoCigs GmbH & Co. KG');
        $module->setSupplierId(DropshipManager::SUPPLIER_INNOCIGS);
        $plugin = strstr(__CLASS__, '\\', true);
        $module->setPlugin($plugin);
        $this->modelManager->persist($module);
        $this->modelManager->flush();
    }

    public function uninstall(UninstallContext $context)
    {
        if (! class_exists(MxcDropshipIntegrator::class)) return;

        $repo = $this->modelManager->getRepository(DropshipModule::class);
        $module = $repo->findOneBy(['supplierId' => DropshipManager::SUPPLIER_INNOCIGS]);
        if ($module instanceof DropshipModule) {
            $this->modelManager->remove($module);
            $this->modelManager->flush();
        }
    }

    public function activate(ActivateContext $context)
    {
        if (! class_exists(MxcDropshipIntegrator::class)) return;
        $this->activateModule(true);
    }

    public function deactivate(DeactivateContext $context)
    {
        if (! class_exists(MxcDropshipIntegrator::class)) return;
        $this->activateModule(false);
    }

    protected function activateModule(bool $active)
    {
        $repo = $this->modelManager->getRepository(DropshipModule::class);
        $module = $repo->findOneBy(['supplierId' => DropshipManager::SUPPLIER_INNOCIGS]);
        if ($module instanceof DropshipModule) {
            $module->setActive($active);
            $this->modelManager->flush();
        }
    }
}