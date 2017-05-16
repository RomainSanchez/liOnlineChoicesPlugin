<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiActionsService
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ApiActionsService
{
    public function preExecute(sfActions $action)
    {
        $response = $action->getResponse();
        $response->setHttpHeader('Access-Control-Allow-Origin', '*');
        $response->setHttpHeader('Access-Control-Allow-Methods', 'POST, GET, PUT, OPTIONS, DELETE');
        $response->setHttpHeader('Access-Control-Allow-Headers', 'authorization, x-requested-with, content-type');
    }
}
