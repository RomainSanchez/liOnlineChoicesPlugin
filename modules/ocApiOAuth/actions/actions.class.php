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
class ocApiOAuthActions extends jsonActions
{
    public function preExecute()
    {
        $this->getService('actions_service')->preExecute($this);
        parent::preExecute();
    }
    
    public function executePreflight(sfWebRequest $request)
    {
        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $this->getService('actions_service')->preExecute($this);
        return sfView::NONE;
    }
    
    /**
     * @param sfWebRequest $request
     * @todo move at least the protected functions into a service
     */
    public function executeToken(sfWebRequest $request)
    {
        $oauth = $this->getService('oauth_service');
        
        // authenticates the app
        try {
            $app = $oauth->findApplication(
                $request->getParameter('client_id'),
                $request->getParameter('client_secret')
            );
        } catch ( liOnlineSaleException $e ) {
            OcLogger::log($e->getMessage(), $this);
            return $this->createJsonResponse([], ApiHttpStatus::UNAUTHORIZED);
        }
        
        // deal with the token
        if ( $refresh = $request->getParameter('refresh_token', false) )
        {
            try {
                $token = $oauth->refreshToken($refresh, $app);
            } catch ( liOnlineSaleException $e ) {
                OcLogger::log($e->getMessage(), $this);
                return $this->createJsonResponse([], ApiHttpStatus::UNAUTHORIZED);
            }
        }
        else
        {
            $token = $oauth->createToken($app);
        }
        
        // builds the result
        $result = [
            'access_token'  => $token->token,
            'expires_in'    => $oauth->getTokenLifetime(),
            'token_type'    => 'bearer',
            'scope'         => null,
            'refresh_token' => $token->refresh_token,
        ];
        
        // sends the result
        return $this->createJsonResponse($result);
    }
}
