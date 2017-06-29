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
        ->setOption('query', Doctrine::getTable('Group')->createQuery('g')) // filtering done by the GroupTable::createQuery()
        ->setOption('order_by', ['name', 'asc'])
    //    ->setOption('expanded', true)
    ;
    $this->widgetSchema['workspace_id']
        ->setOption('add_empty', true)
        ->setOption('query', $ws = Doctrine::getTable('Workspace')->createQuery('ws'))
        ->setOption('order_by', ['name', 'asc'])
    //    ->setOption('expanded', true)
    ;
    
    // filtering request on workspaces
    if ( sfContext::hasInstance() ) {
        $ws->andWhereIn('ws.id', array_keys(sfContext::getInstance()->getUser()->getWorkspacesCredentials()));
    }
  }
}
