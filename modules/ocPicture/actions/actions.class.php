<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of actions
 *
 * @author Baptiste SIMON <baptiste.simon@libre-informatique.fr>
 */
class ocPictureActions extends sfActions
{
  public function executeDisplay(sfWebRequest $request)
  {
    $this->picture = Doctrine::getTable('Picture')->createQuery('p')
        ->andWhere('p.id = ?', $request->getParameter('id'))
        ->fetchOne();
    
    if ( !$this->picture instanceof Doctrine_Record ) {
        $this->getResponse()->setStatusCode(ApiHttpStatus::NOT_FOUND);
        return sfView::NONE;
    }
    
    $cache = 'max-age='.($time = 60*60*48); // caching data for 48h
    $this->getResponse()->addHttpMeta('Content-Type',$this->picture->type);
    $this->getResponse()->addHttpMeta('Content-Disposition','inline; filename='.$this->picture->name);
    $this->getResponse()->addHttpMeta('Cache-Control',$cache);
    $this->getResponse()->addHttpMeta('Cache-Control',$cache);
    $this->getResponse()->addHttpMeta('Pragma',$cache);
    $this->getResponse()->addHttpMeta('Expires',date(DATE_W3C,time()+$time)); // caching data for 48h
    
    if ( $this->picture->content_encoding ) {
      $this->getResponse()->addHttpMeta('Content-Encoding', $this->picture->content_encoding);
    }
  }
}
