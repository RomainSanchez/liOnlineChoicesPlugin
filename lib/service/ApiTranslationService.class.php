<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ApiTranslationService
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ApiTranslationService
{
    public function reformat(&$originals)
    {
        foreach ( $originals as $id => $original ) {
            unset(
                $originals[$id]['id'],
                $originals[$id]['lang']
            );
        }
        
        return $this;
    }
}
