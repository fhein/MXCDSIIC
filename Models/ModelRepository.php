<?php

namespace MxcDropshipInnocigs\Models;

use MxcCommons\Toolbox\Models\BaseEntityRepository;

class ModelRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'               =>
            'SELECT ir FROM MxcDropshipInnocigs\Models\Model ir INDEX BY ir.model WHERE ir.deleted = :deleted',

        'getModelsWithoutVariant'     =>
            'SELECT m FROM MxcDropshipInnocigs\Models\Model m '
            . 'LEFT JOIN MxcDropshipIntegrator\Models\Variant v WITH m.model = v.icNumber '
            . 'WHERE v.id IS NULL AND m.deleted = 0',

        // get models with variant which is marked deleted
        'getModelsWithDeletedVariant' =>
            'SELECT m, v FROM MxcDropshipInnocigs\Models\Model m '
            . 'LEFT JOIN MxcDropshipIntegrator\Models\Variant v WITH m.model = v.icNumber '
            . 'WHERE v.deleted = 1 AND m.deleted = 0',
    ];

    public function getAllIndexed()
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('deleted', false)
            ->getResult();
    }

    public function getAllDeletetedIndexed()
    {
        return $this->getQuery('getAllIndexed')
            ->setParameter('deleted', true)
            ->getResult();
    }

    public function getModelsWithDeletedVariant()
    {
        return array_chunk($this->getQuery(__FUNCTION__)
            ->getResult(), 2);
    }
}