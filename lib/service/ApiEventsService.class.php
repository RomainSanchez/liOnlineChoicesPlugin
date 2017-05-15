<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiCustomersService
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ApiEventsService extends ApiEntityService
{
    protected $FIELD_MAPPING = [
        'id'            => 'id',
        'metaEvent.id'  => 'MetaEvent.id',
        'metaEvent.translations' => 'MetaEvent.Translation',
        'category'      => 'EventCategory.name',
        'translations'  => 'Translation',
        'imageURL'      => null,
        'manifestations'=> null,
    ];
    
    /**
     * @var $manifestationsService
     */
    protected $manifestationsService;
    
    public function setManifestationsService(ApiManifestationsService $manifestations)
    {
        $this->manifestationsService = $manifestations;
    }
    
    public function buildInitialQuery()
    {
        return Doctrine::getTable('Event')->createQuery('root')
            ->leftJoin('root.Manifestations m')
        ;
    }
    
    protected function postFormatEntity(array $entity)
    {
        // translations
        foreach ( $entity['translations'] as $key => $value )
        {
            $entity['translations'][$value['lang']] = $value;
            unset(
                $entity['translations'][$key],
                $entity['translations'][$value['lang']]['lang'],
                $entity['translations'][$value['lang']]['id']
            );
        }
        
        // imageURL
        sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
        $entity['imageURL'] = url_for('default/picture?id='.$entity['id']);
        
        // manifestations
        $query = [
            'criteria' => [
                'event_id' => [
                    'type'  => 'equal',
                    'value' => $entity['id'],
                ],
                'happens_at' => [
                    'type'  => 'greater',
                    'value' => date('Y-m-d H:i:s'),
                ],
            ],
            'limit'    => 100,
            'sorting'  => [],
            'page'     => 1,
        ];
        $entity['manifestations'] = $this->manifestationsService->findAll($query)['_embedded']['items'];
        // TODO (a call to ApiManifestationService may be a good idea)
        
        return $entity;
    }
}
