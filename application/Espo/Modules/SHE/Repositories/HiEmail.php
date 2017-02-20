<?php
namespace Espo\Modules\SHE\Repositories;

use Espo\ORM\Entity;

class HiEmail extends \Espo\Core\ORM\Repositories\RDB
{
    protected function init()
    {
        parent::init();
        $this->addDependency('emailFilterManager');
    }

    protected function prepareAddressess(Entity $entity, $type, $addAssignedUser = false)
    {
        if (!$entity->has($type)) {
            return;
        }

        $eaRepository = $this->getEntityManager()->getRepository('EmailAddress');

        $address = $entity->get($type);
        $idList = [];
        if (!empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $arr = array_map(function ($e) {
                return trim($e);
            }, explode(';', $address));

            $idList = $eaRepository->getIdListFormAddressList($arr);
            foreach ($idList as $id) {
                $this->addUserByEmailAddressId($entity, $id, $addAssignedUser);
            }
        }
        $entity->setLinkMultipleIdList($type . 'EmailAddresses', $idList);
    }

    protected function addUserByEmailAddressId(Entity $entity, $emailAddressId, $addAssignedUser = false)
    {
        $user = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($emailAddressId, 'User');
        if ($user) {
            $entity->addLinkMultipleId('users', $user->id);
            if ($addAssignedUser) {
                $entity->addLinkMultipleId('assignedUsers', $user->id);
            }
        }
    }

    public function loadToField(Entity $entity)
    {
        $entity->loadLinkMultipleField('toEmailAddresses');
        $names = $entity->get('toEmailAddressesNames');
        if (!empty($names)) {
            $arr = array();
            foreach ($names as $id => $address) {
                $arr[] = $address;
            }
            $entity->set('to', implode(';', $arr));
        }
    }

    public function loadNameHash(Entity $entity, array $fieldList = ['to'])
    {
        $addressList = array();

        if (in_array('to', $fieldList)) {
            $arr = explode(';', $entity->get('to'));
            foreach ($arr as $address) {
                if (!in_array($address, $addressList)) {
                    $addressList[] = $address;
                }
            }
        }

        $nameHash = (object) [];
        $typeHash = (object) [];
        $idHash = (object) [];
        foreach ($addressList as $address) {
            $p = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($address);
            if (!$p) {
                $p = $this->getEntityManager()->getRepository('InboundEmail')->where(array('emailAddress' => $address))->findOne();
            }
            if ($p) {
                $nameHash->$address = $p->get('name');
                $typeHash->$address = $p->getEntityName();
                $idHash->$address = $p->id;
            }
        }

        $entity->set('nameHash', $nameHash);
        $entity->set('typeHash', $typeHash);
        $entity->set('idHash', $idHash);
    }

    protected function beforeSave(Entity $entity, array $options = array())
    {
        if ($entity->has('to')) {
            if ($entity->has('to')) {
                $this->prepareAddressess($entity, 'to', true);
            }
        }

        parent::beforeSave($entity, $options);
    }

}

