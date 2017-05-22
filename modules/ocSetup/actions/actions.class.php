<?php

require_once dirname(__FILE__).'/../lib/ocSetupGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/ocSetupGeneratorHelper.class.php';

/**
 * ocSetup actions.
 *
 * @package    e-venement
 * @subpackage ocSetup
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ocSetupActions extends autoOcSetupActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $q = Doctrine::getTable('OcConfig')->createQuery('config')
      ->andWhere('config.sf_guard_user_id = ?', $this->getUser()->getId())
    ;
    $q->count() == 0 ? $this->redirect('ocSetup/new') : $this->redirect('ocSetup/edit?id='.$q->fetchOne()->id);
  }
  
  public function executeEdit(sfWebRequest $request)
  {
    parent::executeEdit($request);
    if ( $this->getRoute()->getObject()->sf_guard_user_id != $this->getUser()->getId() )
    {
      $this->getUser()->setFlash("You are not allowed to access someone else's configuration");
      $this->redirect('ocSetup/index');
    }
  }
  

  
  public function executeNew(sfWebRequest $request)
  {
    $q = Doctrine::getTable('OcConfig')->createQuery('config')
      ->andWhere('config.sf_guard_user_id = ?', $this->getUser()->getId())
    ;
    if ( $q->count() > 0 )
      $this->redirect('ocSetup/edit?id='.$q->fetchOne()->id);
    parent::executeNew($request);
  }
}
