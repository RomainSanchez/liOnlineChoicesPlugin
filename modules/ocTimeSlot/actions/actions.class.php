<?php

require_once dirname(__FILE__).'/../lib/ocTimeSlotGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/ocTimeSlotGeneratorHelper.class.php';

/**
 * ocTimeSlot actions.
 *
 * @package    e-venement
 * @subpackage ocTimeSlot
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ocTimeSlotActions extends autoOcTimeSlotActions
{
    public function executeRefresh(sfWebRequest $request)
    {
        $q = Doctrine::getTable('OcTimeSlot')->createQuery('ts');
        foreach ( $q->execute as $ts ) {
            $ts->postSave('global refresh');
        }
        
        $this->getContext()->getConfiguration()->loadHelpers('I18N');
        $this->getUser()->setFlash('notice', __('All manifestations have been refreshed on their timeslots', null, 'li_oc'));
        $this->redirect('oc_time_slot/index');
    }
}
