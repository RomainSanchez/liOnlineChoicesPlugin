<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of actions
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 */
class ocApiEventsActions extends apiActions
{

    /**
     * 
     * @param sfWebRequest $request
     * @return array
     */
    public function getOne(sfWebRequest $request)
    {
        return $this->jsonResponse(array('message' => __METHOD__));
    }

    /**
     * 
     * @param sfWebRequest $request
     * @param array $query
     * @return array
     */
    public function getAll(sfWebRequest $request, array $query)
    {
        $events  = $this->getService('events_service');
        $result  = $this->getListWithDecorator($events->findAll($query), $query);
        return $this->createJsonResponse($result);
    }
}
