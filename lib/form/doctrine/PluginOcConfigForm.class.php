<?php

/**
 * PluginOcConfig form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage form
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginOcConfigForm extends BaseOcConfigForm
{
  public function configure()
  {
    parent::configure();
    $this->widgetSchema->getFormFormatter()->setTranslationCatalogue('li_oc');
    
    $this->widgetSchema['sf_guard_user_id'] = new sfWidgetFormInputHidden;
    $this->widgetSchema['automatic']        = new sfWidgetFormInputHidden;
    $this->widgetSchema['version']          = new sfWidgetFormInputHidden;
    $this->widgetSchema['group_id']
        ->setOption('add_empty', true)
    //    ->setOption('expanded', true)
    ;
    $this->widgetSchema['workspace_id']
        ->setOption('add_empty', true)
    //    ->setOption('expanded', true)
    ;
  }
}
