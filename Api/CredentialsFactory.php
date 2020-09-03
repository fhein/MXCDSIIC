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

        $config = $container->get('shopwareConfig');
        $user = $config->offsetGet('api_user');
        $password = null;
        if (is_string($user)) {
            $password = $config->offsetGet('api_password');
        } else {
            $credentialsTable = 's_mxcbc_dsi_credentials';
            /**
             * @var Connection $dbal
             */
            $dbal = $container->get('dbal_connection');
            if ($dbal->getSchemaManager()->tablesExist([$credentialsTable])) {
                $sql = sprintf('SELECT user, password FROM %s WHERE type = \'%s\'', $credentialsTable, $this->mode);
                $credentials = $dbal->query($sql)->fetchAll();
                if (count($credentials) > 0) {
                    $user = $credentials[0]['user'];
                    $password = $credentials[0]['password'];

                    $log = MxcDropshipInnocigs::getServices()->get('logger');
                    $mode = strtoupper($this->mode);
                    $log->info(sprintf("***** %s MODE, USER: %s ****", $mode, $user));
                }
            }
        }
        if (! (is_string($user) && is_string($password) && $user !== '' && $password !== '')) {
            throw new ServiceNotCreatedException('No valid InnoCigs API credentials available.');
        }
        return new Credentials($user, $password);
    }
}