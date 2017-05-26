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
class ApiOAuthService extends EvenementService
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
    public function authenticate()
    {
        $headerValue = $this->getAuthorizationHeader();
        if ( !$headerValue ) {
            throw new ocAuthException('API Key not provided');
        }

        $apiKey = preg_replace('/^Bearer\s+/', '', $headerValue);
        $this->token = $this->findRegisteredTokenByApiKey($apiKey);

        if ( null === $this->token || !$this->token instanceof OcToken) {
            throw new ocAuthException('Invalid API authentication');
        }
        return true;
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

    /**
     * 
     * @param string $key
     * @return OcToken | null
     */
    public function findRegisteredTokenByApiKey($key)
    {
        $q = Doctrine::getTable('OcToken')->createQuery('ot')
            ->andWhere('ot.token = ?', $key)
            ->andWhere('expires_at > ?', date('Y-m-d H:i:s'))
        ;

        $token = $q->fetchOne();

        if ( !$token ) {
            return null;
        }

        return $token;
    }

    /**
     * 
     * @param string $client_id
     * @param string $client_secret
     * @return OcApplication | null
     */
    public function findApplication($client_id, $client_secret)
    {
        $q = Doctrine::getTable('OcApplication')->createQuery('app')
            ->leftJoin('app.User u')
            ->andWhere('app.identifier = ?', $client_id)
            ->andWhere('app.secret     = ?', $this->encryptSecret($client_secret))
            ->andWhere('app.expires_at IS NULL OR app.expires_at > NOW()')
        ;

        $app = $q->fetchOne();
        if ( !$app ) {
            return null;
        }

        return $app;
    }

    public function createToken(OcApplication $app)
    {
        $token = new OcToken();

        $token->token = $this->generateToken();
        $token->refresh_token = $this->generateToken();
        $token->expires_at = $this->getExpirationTime();
        $token->oc_application_id = $app->id;
        $token->OcTransaction[] = new OcTransaction();
        $token->save();

        return $token;
    }

    public function refreshToken($refresh, OcApplication $app)
    {
        $q = Doctrine::getTable('OcToken')->createQuery('ot')
            ->andWhere('ot.refresh_token = ?', $refresh)
            ->andWhere('ot.oc_application_id = ?', $app->id)
            ->andWhere('ot.created_at > ?', date('Y-m-d H:i:s', strtotime('24 hours ago')))
        ;

        $token = $q->fetchOne();
        if ( !$token instanceof OcToken ) {
            throw new ocAuthException('Refresh token not found.');
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
