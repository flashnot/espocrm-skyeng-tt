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

    protected function send($entity)
    {
        $to = $entity->get('to');
        
        if (empty($to) || strpos($to, '@') === false) {         
            throw new Error("Destanation email is empty", 404);
        }
        
        $smtpParams = $this->getPreferences()->getSmtpParams();
        if (empty($smtpParams)) {
            $smtpParams = $this->getSmtpParamsFromEmailAccount('no-reply@espocrm.dmigus.com', $this->getUser()->id);
        }
        
        if ($smtpParams) {
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }
        }
        
        if (!$smtpParams) {
            throw new Error('Can not use system smtp. System SMTP is not shared.');
        }
        
        $emailSender = $this->getMailSender();
        $emailSender->useSmtp($smtpParams);
        
        /*if (strpos($data['to'], ';') !== false) {
            $ea = explode(';', $data['to']);
            
            $to = array_shift($ea);
            $bcc = count($ea) > 0 ? implode(';', $ea) : null;
        } else {
            $to = $data['to'];
            $bcc = null;
        }*/
        
        $emailTemplate = $this->getEntityManager()->getRepository('EmailTemplate')->get('58aab71ea99715007');
        
        if (!empty($emailTemplate)) {
            $subject = $emailTemplate->get('subject');
            $body = $emailTemplate->get('body');
            $isHtml = $emailTemplate->get('isHtml');
        } else {
            $subject = 'Hi for Skyeng from Gusakov D.A. !';
            $body = 'Привет для Skyeng от Гусакова Д.А.';
            $isHtml = false;
        }
        
        $email = $this->getEntityManager()->getEntity('Email');
        $email->set([
            'subject'    => $subject,
            'isHtml'    => $isHtml,
            'to'        => $to,            //TODO: все получатели видят кому ещё было отправлено письмо :( Варианты - а) подставлять to в цикле, б) использовать bcc
            //'bcc'        => $bcc,
            'body'        => $body
        ]);
        
        try {
            $emailSender->send($email);
        } catch (\Exception $e) {
            throw new Error($e->getMessage(), $e->getCode());
        }
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

        if ($entity) {
            try {
                $this->send($entity);
            } catch (\Exception $e) {
                $this->getEntityManager()->removeEntity($entity);
                throw new Error($e->getMessage(), $e->getCode());
            }
        }

        return $entity;
    }

    protected function afterUpdate(Entity $entity, array $data = array())
    {
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


    public function loadNameHash(Entity $entity, array $fieldList = ['to'])
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
}
