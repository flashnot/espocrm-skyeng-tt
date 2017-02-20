<?php
namespace Espo\Modules\SHE\Services;

use \Espo\ORM\Entity;
use \Espo\Entities;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\BadRequest;


class HiEmail extends \Espo\Services\Record
{
    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'container',
            'preferences',
            'fileManager',
            'crypt',
            'serviceFactory'
        ]);
    }

    private $streamService = null;

    protected $getEntityBeforeUpdate = true;

    protected $allowedForUpdateAttributeList = [   ];

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function getPreferences()
    {
        return $this->injections['preferences'];
    }

    protected function getCrypt()
    {
        return $this->injections['crypt'];
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }

    protected function send(Entities\Email $entity)
    {
        $emailSender = $this->getMailSender();

        $userAddressList = [];
        foreach ($this->getUser()->get('emailAddresses') as $ea) {
            $userAddressList[] = $ea->get('lower');
        }

        $primaryUserAddress = strtolower($this->getUser()->get('emailAddress'));
        $fromAddress = strtolower($entity->get('from'));

        if (empty($fromAddress)) {
            throw new Error();
        }

        $smtpParams = null;
        if (in_array($fromAddress, $userAddressList)) {
            if ($primaryUserAddress === $fromAddress) {
                $smtpParams = $this->getPreferences()->getSmtpParams();
            }
            if (!$smtpParams) {
                $smtpParams = $this->getSmtpParamsFromEmailAccount($entity->get('from'), $this->getUser()->id);
            }

            if ($smtpParams) {
                if (array_key_exists('password', $smtpParams)) {
                    $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
                }
                $smtpParams['fromName'] = $this->getUser()->get('name');
                $emailSender->useSmtp($smtpParams);
            }
        }

        if (!$smtpParams && $fromAddress === strtolower($this->getConfig()->get('outboundEmailFromAddress'))) {
            if (!$this->getConfig()->get('outboundEmailIsShared')) {
                throw new Error('Can not use system smtp. System SMTP is not shared.');
            }
            $emailSender->setParams(array(
                'fromName' => $this->getUser()->get('name')
            ));
        }

        $params = array();

        $parent = null;
        if ($entity->get('parentType') && $entity->get('parentId')) {
            $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));
            if ($parent) {
                if ($entity->get('parentType') == 'Case') {
                    if ($parent->get('inboundEmailId')) {
                        $inboundEmail = $this->getEntityManager()->getEntity('InboundEmail', $parent->get('inboundEmailId'));
                        if ($inboundEmail && $inboundEmail->get('replyToAddress')) {
                            $params['replyToAddress'] = $inboundEmail->get('replyToAddress');
                        }
                    }
                }
            }
        }

        $message = null;

        try {
            $emailSender->send($entity, $params, $message);
        } catch (\Exception $e) {
            $entity->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($entity, array(
                'silent' => true
            ));
            throw new Error($e->getMessage(), $e->getCode());
        }

        if ($entity->get('from') && $message) {
            $emailAccount = $this->getEntityManager()->getRepository('EmailAccount')->where(array(
                'storeSentEmails' => true,
                'emailAddress' => $entity->get('from'),
                'assignedUserId' => $this->getUser()->id
            ))->findOne();
            if ($emailAccount) {
                try {
                    $emailAccountService = $this->getServiceFactory()->create('EmailAccount');
                    $emailAccountService->storeSentMessage($emailAccount, $message);
                } catch (\Exception $e) {
                    $GLOBALS['log']->error("Could not store sent email (Email Account {$emailAccount->id}): " . $e->getMessage());
                }
            }
        }

        if ($parent) {
            $this->getStreamService()->noteEmailSent($parent, $entity);
        }

        $entity->set('isJustSent', true);

        $this->getEntityManager()->saveEntity($entity);
    }

    protected function getSmtpParamsFromEmailAccount($address, $userId)
    {
        $emailAccount = $this->getEntityManager()->getRepository('EmailAccount')->where([
            'emailAddress' => $address,
            'assignedUserId' => $userId,
            'active' => true,
            'useSmtp' => true
        ])->findOne();

        if (!$emailAccount) return;

        $smtpParams = array();
        $smtpParams['server'] = $emailAccount->get('smtpHost');
        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');
            $smtpParams['username'] = $emailAccount->get('smtpUsername');
            $smtpParams['password'] = $emailAccount->get('smtpPassword');
            return $smtpParams;
        }

        return;
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }
        return $this->streamService;
    }

    public function createEntity($data)
    {
        $entity = parent::createEntity($data);

        if ($entity && $entity->get('status') == 'Sending') {
            $this->send($entity);
        }

        return $entity;
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        if ($entity->get('status') == 'Sending') {
            $messageId = \Espo\Core\Mail\Sender::generateMessageId($entity);
            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    protected function afterUpdate(Entity $entity, array $data = array())
    {
        if ($entity && $entity->get('status') == 'Sending') {
            $this->send($entity);
        }

        $this->loadAdditionalFields($entity);
    }

    public function loadFromField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadFromField($entity);
    }

    public function loadToField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadToField($entity);
    }

    public function getEntity($id = null)
    {
        $entity = $this->getRepository()->get($id);
        if (!empty($entity) && !empty($id)) {
            $this->loadAdditionalFields($entity);

            if (!$this->getAcl()->check($entity, 'read')) {
                throw new Forbidden();
            }
        }
        if (!empty($entity)) {
            $this->prepareEntityForOutput($entity);
        }

        return $entity;
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $this->loadToField($entity);

        $this->loadNameHash($entity);
    }

   

    static public function parseFromName($string)
    {
        $fromName = '';
        if ($string) {
            if (stripos($string, '<') !== false) {
                $fromName = trim(preg_replace('/(<.*>)/', '', $string), '" ');
            }
        }
        return $fromName;
    }

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFieldsForList($entity);

        $userEmailAdddressIdList = [];
        foreach ($this->getUser()->get('emailAddresses') as $ea) {
            $userEmailAdddressIdList[] = $ea->id;
        }

        $status = $entity->get('status');
        if (in_array($entity->get('fromEmailAddressId'), $userEmailAdddressIdList)) {
            $entity->loadLinkMultipleField('toEmailAddresses');
            $idList = $entity->get('toEmailAddressesIds');
            $names = $entity->get('toEmailAddressesNames');

            if (!empty($idList)) {
                $arr = [];
                foreach ($idList as $emailAddressId) {
                    $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($emailAddressId);
                    if ($person) {
                        $arr[] = $person->get('name');
                    } else {
                        $arr[] = $names->$emailAddressId;
                    }
                }
                $entity->set('personStringData', 'To: ' . implode(', ', $arr));
            }
        } else {
            $fromEmailAddressId = $entity->get('fromEmailAddressId');
            if (!empty($fromEmailAddressId)) {
                $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($fromEmailAddressId);
                if ($person) {
                    $entity->set('personStringData', $person->get('name'));
                } else {
                    $fromName = self::parseFromName($entity->get('fromString'));
                    if (!empty($fromName)) {
                        $entity->set('personStringData', $fromName);
                    } else {
                        $entity->set('personStringData', $entity->get('fromEmailAddressName'));
                    }
                }
            }
        }
    }


    public function loadNameHash(Entity $entity, array $fieldList = ['from', 'to', 'cc'])
    {
        $this->getEntityManager()->getRepository('Email')->loadNameHash($entity, $fieldList);
    }

    protected function getSelectParams($params)
    {
        $searchByEmailAddress = false;
        if (!empty($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $i => $p) {
                if (!empty($p['attribute']) && $p['attribute'] == 'emailAddress') {
                    $searchByEmailAddress = true;
                    $emailAddress = $p['value'];
                    unset($params['where'][$i]);
                }

            }
        }

        $selectManager = $this->getSelectManager($this->getEntityType());

        $selectParams = $selectManager->getSelectParams($params, true);

        if ($searchByEmailAddress) {
            $selectManager->whereEmailAddress($emailAddress, $selectParams);
        }

        return $selectParams;
    }

    public function sendTestEmail($data)
    {
        $email = $this->getEntityManager()->getEntity('HiEmail');

        $email->set(array(
            'subject' => 'EspoCRM: Test Email',
            'to' => $data['emailAddress']
        ));

        $emailSender = $this->getMailSender();
        $emailSender->useSmtp($data)->send($email);

        return true;
    }
}

