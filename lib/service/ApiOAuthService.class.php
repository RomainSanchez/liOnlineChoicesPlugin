<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiOAuthService
 *
 * @author Glenn Cavarlé <glenn.cavarle@libre-informatique.fr>
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ApiOAuthService
{

    /**
     * @var OcToken
     * */
    protected $token = NULL;

    /**
     * 
     * @param sfWebRequest $request
     * @return boolean
     */
    public function isAuthenticated()
    {
        $key = str_replace('Bearer ', '', $this->getAuthorizationHeader());

        try {
            $this->token = $this->findOneByApiKey($key);
            return true;
        } catch ( liOnlineSaleException $e ) {
            return false;
        }
    }

    protected function getAuthorizationHeader()
    {
        $headers = getallheaders();
        if ( isset($headers['Authorization']) )
            return $headers['Authorization'];
        return false;
    }

    /**
     *
     * @return OcToken
     * */
    public function getToken()
    {
        return $this->token;
    }

    public function findOneByApiKey($key)
    {
        $q = Doctrine::getTable('OcToken')->createQuery('ot')
            ->andWhere('ot.token = ?', $key)
            ->andWhere('expires_at > ?', date('Y-m-d H:i:s'))
        ;
        $token = $q->fetchOne();

        if ( !$token instanceof OcToken )
            throw new liOnlineSaleException('No token found for ' . $key);

        return $token;
    }

    public function findApplication($client_id, $client_secret)
    {
        $q = Doctrine::getTable('OcApplication')->createQuery('app')
            ->andWhere('app.identifier = ?', $client_id)
            ->andWhere('app.secret     = ?', $this->encryptSecret($client_secret))
            ->andWhere('app.expires_at IS NULL OR app.expires_at > NOW()')
        ;

        $app = $q->fetchOne();
        if ( !$app instanceof OcApplication )
            throw new liOnlineSaleException('Application not found.');

        return $app;
    }

    public function createToken(OcApplication $app)
    {
        $token = new OcToken;

        $token->token = $this->generateToken();
        $token->refresh_token = $this->generateToken();
        $token->expires_at = $this->getExpirationTime();
        $token->oc_application_id = $app->id;
        $token->OcTransaction[] = new OcTransaction;
        $token->save();

        return $token;
    }

    public function refreshToken($refresh, OcApplication $app)
    {
        $q = Doctrine::getTable('OcToken')->createQuery('ot')
            ->andWhere('ot.refresh_token = ?', $refresh)
            ->andWhere('ot.oc_application_id = ?', $app->id)
            ->andWhere('oc.created_at > ?', date('Y-m-d H:i:s', strtotime('24 hours ago')))
        ;

        $token = $q->fetchOne();
        if ( !$token instanceof OcToken ) {
            throw new liOnlineSaleException('Refresh token not found.');
        }
        $token->token = $this->generateToken();
        $token->refresh_token = $this->generateToken();
        $token->expires_at = $this->getExpirationTime();
        $token->save();

        return $token;
    }

    public function encryptSecret($secret)
    {
        $salt = sfConfig::get('project_eticketting_salt', '123456789azerty');
        return md5($secret . $salt);
    }

    protected function generateToken()
    {
        $date = str_replace('-', 'T', date('Ymd-HisP'));
        return $this->encryptSecret($date . '-' . rand(1000000, 9999999));
    }

    public function getTokenLifetime()
    {
        return ini_get('session.gc_maxlifetime');
    }

    protected function getExpirationTime()
    {
        return date('Y-m-d H:i:s', time() + $this->getTokenLifetime());
    }
}
