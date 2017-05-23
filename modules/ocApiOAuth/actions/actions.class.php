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
        $this->getService('api_actions_service')
            ->populateAccessControlHeaders($this->getResponse());

        parent::preExecute();
    }

    public function executePreflight(sfWebRequest $request)
    {
        $response = $this->getResponse();
        $response->clearHttpHeaders();

        $this->getService('api_actions_service')
            ->populateAccessControlHeaders($response);

        return sfView::NONE;
    }

    /**
     * @param sfWebRequest $request
     * @todo move at least the protected functions into a service
     */
    public function executeToken(sfWebRequest $request)
    {
        $oauth = $this->getService('api_oauth_service');

        // find the app
        $app = $oauth->findApplication(
            $request->getParameter('client_id'), $request->getParameter('client_secret')
        );

        //no application found -> return error response
        if ( null === $app ) {
            return $this->createJsonErrorResponse(
                    'application authentification failed', ApiHttpStatus::UNAUTHORIZED);
        }

        // deal with the token
        if ( $refresh = $request->getParameter('refresh_token', false) ) {
            $newToken = $oauth->refreshToken($refresh, $app);

            if ( null === $newToken ) {
                OcLogger::log($e->getMessage(), $this);
                return $this->createJsonErrorResponse('token cannot be refreshed', ApiHttpStatus::UNAUTHORIZED);
            }
        } else {
            $newToken = $oauth->createToken($app);
        }

        // builds the result
        $result = [
            'access_token' => $newToken->token,
            'expires_in' => $oauth->getTokenLifetime(),
            'token_type' => 'bearer',
            'scope' => null,
            'refresh_token' => $newToken->refresh_token,
        ];

        // sends the result
        return $this->createJsonResponse($result);
    }
}
