<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Api;

use Doctrine\DBAL\Connection;
use MxcCommons\Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcCommons\ServiceManager\Exception\ServiceNotCreatedException;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class CredentialsFactory implements FactoryInterface
{
    private $mode = 'development';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $contextService = Shopware()->Container()->get('shopware_storefront.context_service');
        $host = $contextService->createShopContext(1)->getShop()->getHost();
        if ($host === 'www.vapee.de') $this->mode = 'production';

        [$user, $password] = $this->getCredentialsFromConfig($container);
        if (empty($user)) {
            [$user, $password] = $this->getCredentialsFromDb($container);
        }

        if (! (is_string($user) && is_string($password) && $user !== '' && $password !== '')) {
            throw new ServiceNotCreatedException('No valid InnoCigs API credentials available.');
        }

        return new Credentials($user, $password);
    }

    protected function getCredentialsFromConfig(ContainerInterface $container)
    {
        $config = $container->get('shopwareConfig');
        $user = $config->offsetGet('api_user');
        $password = $config->offsetGet('api_password');
        return [$user, $password];
    }

    protected function getCredentialsFromDb(ContainerInterface $container)
    {
        $credentialsTable = 's_mxcbc_dsi_credentials';
        /** @var  $ */
        $dbal = $container->get('dbal_connection');
        $user = null;
        $password = null;
        if ($dbal->getSchemaManager()->tablesExist([$credentialsTable])) {
            $sql = sprintf('SELECT user, password FROM %s WHERE type = \'%s\'', $credentialsTable, $this->mode);
            $credentials = $dbal->fetchAssoc($sql);
            if ($credentials) {
                $user = $credentials['user'];
                $password = $credentials['password'];
            }
        }
        return [$user, $password];
    }
}