<?php

/**
 * PluginOcApplication form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginOcApplicationForm extends BaseOcApplicationForm
{
  public function configure()
  {
    parent::configure();
    
    $this->widgetSchema->getFormFormatter()->setTranslationCatalogue('li_oc');

    $this->widgetSchema['user_id']->setOption('add_empty', true);
    
    unset($this->widgetSchema['secret'], $this->validatorSchema['secret']);
    $this->widgetSchema   ['secret_new'] = new sfWidgetFormInputText(['label' => 'Secret']);
    $this->validatorSchema['secret_new'] = new sfValidatorString(['required' => false]);
    
    $this->validatorSchema['expires_at']->setOption('required' , false);
  }
  
  public function doSave($con = null)
  {
    $oauth = sfContext::getInstance()->getContainer()->get('api_oauth_service');
    
    if ( $this->values['secret_new'] || $this->object->isNew() )
      $this->values['secret'] = $oauth->encryptSecret($this->values['secret_new']);
    unset($this->values['secret_new']);
    
    parent::doSave($con);
  }
}
