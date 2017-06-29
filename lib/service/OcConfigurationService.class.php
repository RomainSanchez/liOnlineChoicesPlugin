<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OcConfigurationService
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class OcConfigurationService
{
    /**
     * get back the current configuration (depending on the authentified user)
     * 
     * @param  $user            sfBasicSecurityUser
     * @return array            configuration
     * @throws liApiConfigurationException if no exception is found
     *
     */
    public function getConfigurationFor(sfBasicSecurityUser $user)
    {
        $config = Doctrine::getTable('OcConfig')->createQuery('c')
            ->andWhere('c.sf_guard_user_id = ?', $user->getId())
            ->fetchOne()
        ;
        
        if ( !$config ) {
            throw new liApiConfigurationException('No configuration found.');
        }
        
        return $config->toArray();
    }
}
