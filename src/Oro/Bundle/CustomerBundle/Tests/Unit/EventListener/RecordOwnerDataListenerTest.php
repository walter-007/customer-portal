<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\EventListener\RecordOwnerDataListener;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\CustomerBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RecordOwnerDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /**  @var RecordOwnerDataListener */
    protected $listener;

    /** @var CustomerUserProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerUserProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    protected function setUp()
    {
        $this->customerUserProvider = $this->createMock(CustomerUserProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->listener = new RecordOwnerDataListener(
            $this->customerUserProvider,
            $this->configProvider,
            PropertyAccess::createPropertyAccessor()
        );
    }

    /**
     * @param $user
     * @param $securityConfig
     * @param $expect
     *
     * @dataProvider preSetData
     */
    public function testPrePersistUser($user, $securityConfig, $expect)
    {
        $entity = new Entity();
        $this->customerUserProvider->expects($this->once())
            ->method('getLoggedUserIncludingGuest')
            ->will($this->returnValue($user));

        $args = new LifecycleEventArgs($entity, $this->createMock(ObjectManager::class));
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($securityConfig));

        $this->listener->prePersist($args);
        if (isset($expect['owner'])) {
            $this->assertEquals($expect['owner'], $entity->getOwner());
        } else {
            $this->assertNull($entity->getOwner());
        }
    }

    /**
     * @return array
     */
    public function preSetData()
    {
        /** @var EntityConfigId $entityConfigId */
        $entityConfigId = $this->getMockBuilder(EntityConfigId::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User();
        $user->setId(1);

        $customer = $this->createMock(Customer::class);
        $user->setCustomer($customer);

        $userConfig = new Config($entityConfigId);
        $userConfig->setValues(
            [
                "frontend_owner_type" => "FRONTEND_USER",
                "frontend_owner_field_name" => "owner",
                "frontend_owner_column_name" => "owner_id"
            ]
        );
        $buConfig = new Config($entityConfigId);
        $buConfig->setValues(
            [
                "frontend_owner_type" => "FRONTEND_CUSTOMER",
                "frontend_owner_field_name" => "owner",
                "frontend_owner_column_name" => "owner_id"
            ]
        );
        $organizationConfig = new Config($entityConfigId);
        $organizationConfig->setValues(
            [
                "frontend_owner_type" => "FRONTEND_ORGANIZATION",
                "frontend_owner_field_name" => "owner",
                "frontend_owner_column_name" => "owner_id"
            ]
        );

        return [
            'OwnershipType User' => [
                $user,
                $userConfig,
                ['owner' => $user]
            ],
            'OwnershipType Customer' => [
                $user,
                $buConfig,
                ['owner' => $customer]
            ],
            'OwnershipType Organization' => [
                $user,
                $organizationConfig,
                []
            ],
        ];
    }
}
