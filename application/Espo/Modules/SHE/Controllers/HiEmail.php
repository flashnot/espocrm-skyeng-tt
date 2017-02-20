<?php

namespace Espo\Modules\SHE\Controllers;

class HiEmail extends \Espo\Core\Controllers\Record
{
    public function actionCreate($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->check($this->name, 'create')) {
            throw new Forbidden();
        }

        $service = $this->getRecordService();

        if ($entity = $service->createEntity($data)) {
            print_r($data); die();
            
            
            return $entity->toArray();
        }

        throw new Error();
    }
}