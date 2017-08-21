<?php
require_once dirname(__FILE__) . '/../lib/ocBackendGeneratorConfiguration.class.php';
require_once dirname(__FILE__) . '/../lib/ocBackendGeneratorHelper.class.php';

/**
 * ocBackend actions.
 *
 * @package    symfony
 * @subpackage ocBackend
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class ocBackendActions extends autoOcBackendActions
{

    protected $setup = [];

    public function preExecute()
    {
        try {
            $this->setup = $this->getContext()->getContainer()
                ->get('oc_configuration_service')
                ->getConfigurationFor($this->getUser());
        } catch ( liApiConfigurationException $e ) {
            $this->getRequest()->setParameter('back_to', 'ocBackend/index');
            $this->forward('ocSetup', 'index');
        }

        $dates = $this->getDates();
        $date = date(sfContext::getInstance()->getRequest()->getParameter('date'));
        $this->day = null;

        if ( count($dates) == 0 )
            return;

        $this->getContext()->getConfiguration()->loadHelpers(['Array']); // backward compatibility with PHP < 5.6
        if ( (!$date || $date && !strtotime($date) || array_search($date, array_column($dates, 'm_start')) === false) && count($dates) > 0 ) {
            $this->day = $dates[0]['m_start'];
        } else {
            $this->day = $date;
        }

        if ( !in_array($this->getActionName(), array('index', 'pros', 'events', 'empty')) && $this->isValidated() ) {
            $this->forward('ocBackend', 'empty');
        }
    }

    public function executeEmpty(sfWebRequest $request)
    {
        $this->json = array('error' => 'Access denied');
        $this->setTemplate('json');
    }

    protected function isValidated()
    {
        $valid = false;

        $snapshot = Doctrine::getTable('OcSnapshot')->getLastValid($this->day)->fetchOne();
        if ( $snapshot ) {
            $valid = true;
        }

        return $valid;
    }

    public function executeIndex(sfWebRequest $request)
    {
        //parent::executeIndex($request);
        $this->getContext()->getConfiguration()->loadHelpers(['Date', 'Array']);

        $ocConfig = $this->getUser()->getGuardUser()->OcConfig;
        $workspace = $ocConfig->Workspace;
        $group = $ocConfig->Group;
        
        
        $this->config_form = new OcConfigForm();
        $this->config_form->bind($ocConfig->toArray());
        
        
        $this->form = new OcSnapshotForm();
        $this->_csrf_token = $this->form->getCSRFToken();
        $this->valid = $this->isValidated();

        $this->group = $group->name;
        $this->workspace = $workspace->name;

        
        
        $this->snapshots = Doctrine::getTable('OcSnapshot')->createQuery('s')
            ->andWhere('date(s.day) = ?', $this->day)
            ->orderBy('s.created_at DESC')
            ->execute();

        $this->groups = Doctrine::getTable('Group')->createQuery('g')
            ->andWhere('g.display_everywhere')
            ->execute()
        ;

        $this->initialChoicesActionEnabled = $this->noTransactionsAndNoInitSnapshotExist($this->day);

        $dates = $this->getDates();
        $day = array_search($this->day, array_column($dates, 'm_start'));
        $this->datesData = [];
        foreach ( $dates as $eachDay => $eachDate ) {
            $this->datesData[] = [
                'date' => $eachDate['m_start'],
                'day' => format_date(strtotime($eachDate['m_start']), 'EEEE d MMMM yyyy'),
                'current' => ($eachDay == $day)];
        }
    }

    public function executeUpdateContext(sfWebRequest $request)
    {
       $config_form = new OcConfigForm();
       $data = $request->getParameter($config_form->getName());
       
       $ocConfig = $this->getUser()->getGuardUser()->OcConfig;
       
       $ocConfig->workspace_id = $data['workspace_id'];
       $ocConfig->group_id = $data['group_id'];
       $ocConfig->save();
       
        
        $this->redirect('ocBackend/index'.($this->day ? '?date='. $this->day : ''));
    }
    
    private function noTransactionsAndNoInitSnapshotExist($date)
    {

        $group_id = $this->getUser()->getGuardUser()->OcConfig->group_id;
        $workspace_id = $this->getUser()->getGuardUser()->OcConfig->workspace_id;


        $lastInitSnapQuery = Doctrine::getTable('OcSnapshot')
            ->getLastInit($date);

        $ocProsWithOcTrQuery = Doctrine::getTable('OcProfessional')->createQuery('op')
            ->select('op.id, op.rank, p.id, t.id, t.checkout_state, c.firstname, c.name, o.name, g.id, m.id, tck.rank, tck.accepted, grp.id, grp.name, grp.picture_id, grp.display_everywhere')
            ->innerJoin('op.Professional p')
            ->leftJoin('p.Groups grp')
            ->leftJoin('p.Contact c')
            ->leftJoin('p.Organism o')
            ->leftJoin('op.OcTransactions t')
            ->leftJoin('t.OcTickets tck')
            ->leftJoin('tck.Gauge g WITH g.workspace_id = ?', $workspace_id)
            ->leftJoin('g.Manifestation m')
            ->leftJoin('m.Event e')
            ->andWhere('p.id IN (SELECT gpro.professional_id FROM GroupProfessional gpro WHERE gpro.group_id = ?)', $group_id)
            ->andWhere('date(m.happens_at) = ?', $date)
            ->having('COUNT(tck.id) > 0')
            ->groupBy('op.id')
        ;

        // no transactions and no init snap already saved -> ok
        return($lastInitSnapQuery->count() == 0 && $ocProsWithOcTrQuery->count() == 0);
    }

    public function executeAutoPositioning(sfWebRequest $request)
    {

        $service = sfContext::getInstance()->getContainer()->get('oc_decision_helper');

        $contacts = json_decode($request->getParameter('content'), true);

        $timeslots = array();
        $participants = array();

        // Format data for the Decision service
        foreach ( $contacts as $contact ) {
            $participant = array();
            $participant['id'] = $contact['id'];
            $participant['name'] = $contact['name'];
            $manifestations = array();

            foreach ( $contact['manifestations'] as $c_manifestation ) {
                $timeslot = array();

                $tsid = intval($c_manifestation['time_slot_id']);
                if ( !array_key_exists($tsid, $timeslots) ) {
                    $timeslot['id'] = $tsid;
                    $timeslot['manifestations'] = array();
                    $timeslots[$tsid] = $timeslot;
                }

                $mid = intval($c_manifestation['id']);
                if ( !array_key_exists($mid, $timeslots[$tsid]['manifestations']) ) {
                    $t_manif = array();
                    $t_manif['id'] = $mid;
                    $t_manif['gauge_free'] = $c_manifestation['gauge_free'];
                    $timeslots[$tsid]['manifestations'][$mid] = $t_manif;
                }

                $manif = array();
                $manif['id'] = intval($c_manifestation['id']);
                $manif['rank'] = $c_manifestation['rank'];
                $manif['accepted'] = $c_manifestation['accepted'];
                $manifestations[] = $manif;
            }

            $participant['manifestations'] = $manifestations;
            $participants[] = $participant;
        }

        $data = array();
        $data['timeSlots'] = $timeslots;
        $data['participants'] = $participants;

        // Call the Decision service
        $output = $service->process($data);

        // Format data for the ajax query
        $contacts = array();

        foreach ( $output['participants'] as $participant ) {
            $contact = array();
            $contact['id'] = $participant['id'];
            $contact['name'] = $participant['name'];
            $contact['manifestations'] = array();
            foreach ( $participant['manifestations'] as $manifestation ) {
                $manif = array();
                $manif['id'] = $manifestation['id'];
                $manif['rank'] = $manifestation['rank'];
                $manif['accepted'] = $manifestation['accepted'];
                $contact['manifestations'][] = $manif;
            }
            $contacts[] = $contact;
        }

        $this->json = $contacts;

        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));
    }

    protected function isDebug($debug)
    {
        if ( $debug ) {
            sfConfig::set('sf_web_debug', true);
            $this->setTemplate('debug');
            $this->getResponse()->setContentType('text/html');
            $this->setLayout('layout');
        }
    }

    protected function getDates()
    {
        $dates = Doctrine_Query::create()
            ->select('date(m.happens_at) as start, count(*) as total')
            ->from('manifestation m')
            ->leftJoin('m.Event e')
            ->leftJoin('e.MetaEvent me')
            ->innerJoin('me.MetaEventUser meu')
            ->andWhere('meu.sf_guard_user_id = ?')
            ->andWhere('date(m.happens_at) IN (SELECT DISTINCT date(starts_at) FROM OcTimeSlot)')
            ->groupBy('date(m.happens_at)')
            ->orderBy('start')
            ->execute(array($this->getUser()->getId()), Doctrine_Core::HYDRATE_SCALAR);

        return $dates;
    }

    public function executeListSnapshots(sfWebRequest $request)
    {
        sfConfig::set('sf_web_debug', false);

        $this->snapshots = Doctrine::getTable('OcSnapshot')->createQuery('s')
            ->andWhere('date(s.day) = ?', $this->day)
            ->orderBy('s.created_at DESC')
            ->execute();
    }

    public function executeLoadSnapshot(sfWebRequest $request)
    {
        $ocs = Doctrine::getTable('ocSnapshot')->createQuery('s')
            ->andWhere('s.id = ?', intval($request->getParameter('id')))
            ->fetchOne();

        if ( $ocs && $ocs->sf_guard_user_id == $this->getUser()->getId() ) {
            $this->json = unserialize($ocs->content);
        } else {
            $this->json = array();
            $this->json['error'] = 'Error';
        }

        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));
    }

    public function executeSaveOrdering(sfWebRequest $request)
    {
        if ( !( is_array($request->getParameter('rank', [])) && $request->getParameter('rank', []) ) ) {
            return sfView::NONE;
        }

        foreach ( $request->getParameter('rank', []) as $rank => $proid ) {
            Doctrine::getTable('OcProfessional')->createQuery('op')
                ->update()
                ->set('rank', $rank + 1)
                ->where('id = ?', $proid)
                ->execute();
        }

        return sfView::NONE;
    }

    protected function saveSnapshot(sfWebRequest $request)
    {
        $json = array();
        $json['error'] = 'Error';

        if ( $data = json_decode($request->getParameter('content'), true) ) {
            $ocsf = new OcSnapshotForm();
            $params = array();
            $params['name'] = $request->getParameter('name');
            $params['day'] = $request->getParameter('date');
            $params['purpose'] = $request->getParameter('purpose');
            $params['sf_guard_user_id'] = $this->getUser()->getId();
            $params['content'] = serialize($data);
            $params['_csrf_token'] = $request->getParameter('_csrf_token');
            $params['group_id'] = $this->getUser()->getGuardUser()->OcConfig->group_id;
            $params['workspace_id'] = $this->getUser()->getGuardUser()->OcConfig->workspace_id;

            $ocsf->bind($params);

            if ( $ocsf->isValid() ) {
                $ocsf->save();
                $json['error'] = 'Success';
            } else {
                foreach ( $ocsf->getErrorSchema()->getErrors() as $name => $error ) {
                    $json['message'][$name] = (string) $error;
                }
            }
        } else {
            $json['error'] = json_last_error_msg();
        }

        return $json;
    }

    public function executeSaveSnapshot(sfWebRequest $request)
    {
        $this->json = $this->saveSnapshot($request);

        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));
    }

    protected function updateOcTicket($snapshot, $createOcTransaction = false)
    {
        $sf_guard_user_id = null;
        $this->gauges = array();

        $ocConfig = $this->getUser()->getGuardUser()->OcConfig;
        $workspace = $ocConfig->Workspace;
        $group = $ocConfig->Group;

        if ( sfContext::hasInstance() )
            if ( sfContext::getInstance()->getUser() instanceof sfGuardSecurityUser )
                if ( sfContext::getInstance()->getUser()->getId() )
                    $sf_guard_user_id = sfContext::getInstance()->getUser()->getId();

        $contacts = unserialize($snapshot->content);
        $manifestations = array();

        foreach ($contacts as $contact) {
          foreach ($contact['manifestations'] as $manifestation) {
            if ( $manifestation['accepted'] != 'none' ) {
              if ( array_key_exists($manifestation['gauge_id'], $this->gauges) ) 
                  $this->gauges[$manifestation['gauge_id']]++;
              else
                  $this->gauges[$manifestation['gauge_id']] = 1;  
            }
          }
        }

        $gauges = Doctrine_Query::create()
            ->select('id, value')
            ->from('Gauge')
            ->andWhereIn('id', array_keys($this->gauges))
            ->fetchArray();

        foreach ( $gauges as $gauge ) {
            if ( $gauge['value'] < $this->gauges[$gauge['id']] ) {
                throw new liEvenementException(sprintf("Gauge %d is overloaded", $gauge['id']), 1);
            }
        }

        foreach ( $contacts as $contact ) {
            if ( count($contact['manifestations']) == 0 )
                continue;

            $oc_transaction = Doctrine::getTable('OcTransaction')->createQuery('oct')
                ->leftJoin('oct.Transaction t')
                ->leftJoin('oct.OcProfessional ocp')
                ->leftJoin('ocp.Professional p')
                ->leftJoin('p.Contact c')
                ->andWhere('ocp.id = ?', intval($contact['id']))
                ->fetchOne()
            ;

            if ( !$oc_transaction ) {
                if ( !$createOcTransaction ) {
                    continue;
                }
                $oc_pro = Doctrine::getTable('OcProfessional')->createQuery('ocp')
                    ->leftJoin('ocp.Professional p')
                    ->leftJoin('p.Contact c')
                    ->andWhere('ocp.id = ?', intval($contact['id']))
                    ->fetchOne()
                ;
                $oc_transaction = new OcTransaction();
                $oc_transaction->oc_token_id = NULL;
                $oc_transaction->OcProfessional = $oc_pro;
                $oc_transaction->save();
            }


            $oc_tickets = array();
            foreach ( $oc_transaction->OcTickets as $oc_ticket ) {
                if ( date($oc_ticket->Gauge->Manifestation->happens_at) == date($snapshot->day) )
                $oc_tickets[$oc_ticket->gauge_id] = $oc_ticket;
            }

            foreach ( $contact['manifestations'] as $contact_manifestation ) {
                if ( array_key_exists(intval($contact_manifestation['gauge_id']), $oc_tickets) ) {
                    $oc_ticket->accepted = $contact_manifestation['accepted'];
                    $oc_ticket->save();
                    unset($oc_tickets[intval($contact_manifestation['gauge_id'])]);
                } else {
                    $m_id = intval($contact_manifestation['id']);
                    if ( !array_key_exists($m_id, $manifestations) ) {
                        $manifestations[$m_id] = Doctrine::getTable('Manifestation')->FindOneById($m_id);
                    }

                    $m_gauges = $manifestations[$m_id]->Gauges->toKeyValueArray('workspace_id', 'id');

                    $oc_ticket = new Octicket();
                    $oc_ticket->sf_guard_user_id = $sf_guard_user_id;
                    $oc_ticket->automatic = true;
                    $oc_ticket->rank = 0;
                    $oc_ticket->oc_transaction_id = $oc_transaction->id;
                    $oc_ticket->price_id = $workspace->Prices[0]->id;
                    $oc_ticket->gauge_id = $m_gauges[$workspace->id];
                    $oc_ticket->accepted = $contact_manifestation['accepted'];
                    $oc_ticket->save();
                }
            }

            foreach ( $oc_tickets as $oc_ticket ) {
                if ( $oc_ticket->rank > 0 ) {
                    $oc_ticket->accepted = 'none';
                    $oc_ticket->save();
                } else {
                    $oc_ticket->delete();
                }
            }
        }

        return true;
    }

    public function executeValidate(sfWebRequest $request)
    {
        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));

        $sf_guard_user_id = null;
        $this->json = array();
        $this->json['error'] = 'Success';

        $day = date($request->getParameter('date'));

        $ocConfig = $this->getUser()->getGuardUser()->OcConfig;
        $workspace = $ocConfig->Workspace;

        $json = $this->saveSnapshot($request);

        if ( $json['error'] != 'Success' ) {
            $this->json = $json;
            return;
        }

        $snapshot = Doctrine::getTable('OcSnapshot')->getLastValid($day)->fetchOne();

        if ( !$snapshot ) {
            $this->json['message'] = 'No snapshot present';
            return;
        }

        try {
            $this->updateOcTicket($snapshot);
        } catch ( liEvenementException $e ) {
            $this->json['error'] = 'Error';
            $this->json['message'][] = $e->getMessage();
            return;
        }

        if ( sfContext::hasInstance() )
            if ( sfContext::getInstance()->getUser() instanceof sfGuardSecurityUser )
                if ( sfContext::getInstance()->getUser()->getId() )
                    $sf_guard_user_id = sfContext::getInstance()->getUser()->getId();

        $oc_transactions = Doctrine::getTable('OcTransaction')->createQuery('oct')
            ->leftJoin('oct.Transaction t')
            ->leftJoin('oct.OcTickets ock')
            ->leftJoin('ock.Gauge g')
            ->leftJoin('g.Manifestation m')
            ->leftJoin('oct.OcProfessional ocp')
            ->leftJoin('ocp.Professional p')
            ->leftJoin('p.Contact c')
            ->andWhere('date(m.happens_at) = ?', $snapshot->day)
            ->andWhere('g.workspace_id = ?', $workspace->id)
            ->andWhere('oct.oc_professional_id IS NOT NULL')
            ->execute()
        ;

        $to_save = array();

        foreach ( $oc_transactions as $oc_transaction ) {
            if ( !$oc_transaction->transaction_id && $oc_transaction->OcTickets->count() > 0 ) {
                $transaction = new Transaction();
                $transaction->with_shipment = false;
                $transaction->contact_id = $oc_transaction->OcProfessional->Professional->contact_id;
                $transaction->professional_id = $oc_transaction->OcProfessional->professional_id;

                $oc_transaction->Transaction = $transaction;
                $oc_transaction->save();
            }

            foreach ( $oc_transaction->OcTickets as $oc_ticket ) {
                $ticket = new Ticket();
                $ticket->transaction_id = $oc_transaction->transaction_id;
                $ticket->automatic = true;
                $ticket->manifestation_id = $oc_ticket->Gauge->manifestation_id;
                $ticket->gauge_id = $oc_ticket->gauge_id;
                $ticket->price_id = $oc_ticket->price_id;
                $ticket->integrated_at = date('Y-m-d H:i:s');
                $ticket->contact_id = $oc_transaction->OcProfessional->Professional->contact_id;

                // auto link tickets to member cards
                try {
                    $ticket->linkToMemberCard();
                } catch ( liMemberCardException $e ) {
                    $this->json['error'] = 'Error';
                    $this->json['message'][] = 'No member card for contact ' . $oc_transaction->OcProfessional->Professional->Contact
                        . ' - id ' . $oc_transaction->OcProfessional->Professional->contact_id;
                    continue;
                }

                $oc_transaction->Transaction->Tickets[] = $ticket;
            }

            $to_save[] = $oc_transaction->Transaction;
            //$oc_transaction->Transaction->save();
        }

        if ( $this->json['error'] == 'Success' ) {
            foreach ( $to_save as $transaction ) {
                $transaction->save();
            }
        } else {
            $snapshot = Doctrine::getTable('OcSnapshot')->getLastValid($this->day)->fetchOne();
            if ( $snapshot ) {
                $snapshot->delete();
            }
        }
    }

    public function executeValidateInitialChoices(sfWebRequest $request)
    {
        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));

        $this->json = array();
        $this->json['error'] = 'Success';

        $group_id = $this->getUser()->getGuardUser()->OcConfig->group_id;
        $workspace_id = $this->getUser()->getGuardUser()->OcConfig->workspace_id;
        $day = date($request->getParameter('date'));

        $canBeValided = $this->noTransactionsAndNoInitSnapshotExist($day);

        if ( !$canBeValided ) {
            $this->json['error'] = 'Error';
            $this->json['message'][] = "Les choix initiaux sont déjà validés.";
            return;
        }
        $request->setParameter('name', 'Validation des choix initiaux');
        $json = $this->saveSnapshot($request);

        if ( $json['error'] != 'Success' ) {
            $this->json = $json;
            $this->json['error'] = 'Error';
            $this->json['message'][] = "Une erreur est survenue";
            return;
        }

        $snapshot = Doctrine::getTable('OcSnapshot')->getLastInit($day)->fetchOne();

        if ( !$snapshot ) {
            $this->json['error'] = 'Error';
            $this->json['message'][] = 'No snapshot present';
            return;
        }

        try {
            $this->updateOcTicket($snapshot, true/* create OcTransactions */);
        } catch ( liEvenementException $e ) {
            $snapshot->delete();
            $this->json['error'] = 'Error';
            $this->json['message'][] = $e->getMessage();
            return;
        }
    }

    public function executePros(sfWebRequest $request)
    {
        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));
        $this->getContext()->getConfiguration()->loadHelpers('Url');

        $this->json = array();

        $date = $request->getParameter('date');
        if ( $date && !strtotime($date) ){
            return;
        }

        $dates = $this->getDates();
        if ( count($dates) == 0 ){
            return;
        }

        if ( !$date ){
            $date = $dates[0]['m_start'];
        }
        // add any contact in the target group that does not have an OcProfessional yet
        $qPro = Doctrine::getTable('Professional')->createQuery('p')
            ->select('p.id')
            ->leftJoin('p.OcProfessionals op')
            ->andWhere('op.id IS NULL')
            ->leftJoin('p.Groups grp')
            ->leftJoin('grp.Users gu')
            ->andWhere('? AND gu.id IS NULL OR gu.id = ?', [$this->getUser()->hasCredential('pr-group-common') || $this->getUser()->isSuperAdmin(), $this->getUser()->getId()])
            ->leftJoin('grp.OcConfigs conf')
            ->andWhere('conf.sf_guard_user_id = ?', $this->getUser()->getId())
            ->orderBy('c.name, c.firstname')
        ;
        foreach ( $qPro->fetchArray() as $i => $p ) {
            $pro = new OcProfessional;
            $pro->professional_id = $p['id'];
            $pro->save();
        }

        // get back authorized and targetted OcProfessionals and their OcTickets
        $qOcPro = Doctrine::getTable('OcProfessional')->createQuery('op')
            ->select('op.id, op.rank, p.id, t.id, t.checkout_state, c.firstname, c.name, o.name, g.id, m.id, tck.rank, tck.accepted, grp.id, grp.name, grp.picture_id, grp.display_everywhere')
            ->innerJoin('op.Professional p')
            ->leftJoin('p.Groups grp')
            ->leftJoin('p.Contact c')
            ->leftJoin('p.Organism o')
            ->leftJoin('op.OcTransactions t')
            ->leftJoin('t.OcTickets tck')
            ->leftJoin('tck.Gauge g WITH g.workspace_id = ?', $this->getUser()->getGuardUser()->OcConfig->workspace_id)
            ->leftJoin('g.Manifestation m WITH date(m.happens_at) = ?', $date)
            ->leftJoin('m.Event e')
            ->andWhere('p.id IN (SELECT gpro.professional_id FROM GroupProfessional gpro WHERE gpro.group_id = ?)', $this->getUser()->getGuardUser()->OcConfig->group_id)

            /*
              // filters tickets to keep only tickets linked to the current workspace
              ->leftJoin('g.Workspace ws')
              ->leftJoin('ws.OcConfigs oc WITH oc.sf_guard_user_id = ?', $this->getUser()->getId())
              ->andWhere('g.id IS NULL OR oc.id IS NOT NULL')

              // checks which contacts can be accessed by the user in the current context
              ->leftJoin('p.Groups grp')
              ->leftJoin('grp.Users gu')
              ->andWhere('? AND gu.id IS NULL OR gu.id = ?', [$this->getUser()->hasCredential('pr-group-common') || $this->getUser()->isSuperAdmin(), $this->getUser()->getId()])
              //->leftJoin('grp.OcConfigs conf')
              //->andWhere('conf.sf_guard_user_id = ?', $this->getUser()->getId())
             */
            ->orderBy('op.rank, c.name, c.firstname')
        ;
        $ocProfessionals = $qOcPro->fetchArray();

        foreach ( $ocProfessionals as $ocPro ) {
            $pro = array();

            $pro['id'] = $ocPro['id'];
            $pro['rank'] = $ocPro['rank'];
            $pro['name'] = $ocPro['Professional']['Contact']['firstname'] . ' ' . $ocPro['Professional']['Contact']['name'];
            $pro['organism'] = $ocPro['Professional']['Organism']['name'];
            $pro['checkout_state'] = $ocPro['OcTransactions'] ? $ocPro['OcTransactions'][0]['checkout_state'] : 'cart';
            $pro['manifestations'] = [];
            $pro['groups'] = [];
            foreach ( $ocPro['Professional']['Groups'] as $group ) {
                if ( !$group['display_everywhere'] ) {
                    continue;
                }
                $pro['groups'][] = [
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'picture' => $group['picture_id'] ? url_for('@oc_api_picture?id=' . $group['picture_id']) : null,
                ];
            }

            if ( count($ocPro['OcTransactions']) > 0 )
                foreach ( $ocPro['OcTransactions'][0]['OcTickets'] as $ticket ) {
                    $manifestation = array();
                    $manifestation['id'] = $ticket['Gauge']['Manifestation']['id'];
                    $manifestation['rank'] = $ticket['rank'];
                    $manifestation['accepted'] = $ticket['accepted'];
                    $pro['manifestations'][] = $manifestation;
                }

            $this->json[] = $pro;
        }
    }

    public function executeEvents(sfWebRequest $request)
    {
        $this->getContext()->getConfiguration()->loadHelpers(['Date', 'Array']);

        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));

        $this->json = array();
        $current = $previous = $next = null;

        $date = $request->getParameter('date');
        if ( $date && !strtotime($date) ){
            return;
        }

        $dates = $this->getDates();
        if ( count($dates) == 0 ){
            return;
        }

        if ( !$date ){
            $date = $dates[0]['m_start'];
        }

        $q = Doctrine_Query::create()
            ->select('ts.id, ts.name AS time_name, ts.starts_at AS time_start, ts.ends_at AS time_end, m.happens_at AS manif_start, m.duration AS manif_duration')
            ->addSelect('m.id AS manif_id, e.id, et.name AS event_name, et.short_name AS event_short_name, g.id AS gauge_id, g.value AS gauge')
            ->addSelect("(SELECT count(*) FROM OcTicket k WHERE k.gauge_id = g.id AND accepted IN ('algo', 'human')) AS part")
            ->distinct()
            ->from('Manifestation m')
            ->innerJoin('m.Event e')
            ->leftJoin('e.Translation et WITH et.lang = ?', $this->getUser()->getCulture())
            ->innerJoin('m.Gauges g')
            ->innerJoin('m.OcTimeSlots ts')
            ->andWhere('date(m.happens_at) = ?', $date)
            ->andWhereIn('e.meta_event_id', array_keys($this->getUser()->getMetaEventsCredentials()))
            ->andWhere('g.workspace_id = ?', $this->getUser()->getGuardUser()->OcConfig->workspace_id)
            ->orderBy('ts.starts_at, et.short_name');

        $manifestations = $q->fetchArray();

        $day = array_search($date, array_column($dates, 'm_start'));

        if ( $day !== false ) {
            $current = $dates[$day]['m_start'];

            if ( $day > 0 && $dates[$day - 1]['m_total'] > 0 )
                $previous = $dates[$day - 1]['m_start'];

            if ( $day < count($dates) - 1 && $dates[$day + 1]['m_total'] > 0 )
                $next = $dates[$day + 1]['m_start'];
        }

        $this->json['length'] = count($manifestations);
        $this->json['current']['date'] = $current;
        $this->json['current']['day'] = format_date(strtotime($current), 'EEEE d MMMM yyyy');
        $this->json['previous']['date'] = $previous;
        $this->json['previous']['day'] = $previous ? format_date(strtotime($previous), 'EEEE d MMMM yyyy') : '';
        $this->json['next']['date'] = $next;
        $this->json['next']['day'] = $next ? format_date(strtotime($next), 'EEEE d MMMM yyyy') : '';
        $this->json['manifestations'] = array();

        $json = array();
        $previous_start = '';
        $i = -1;

        foreach ( $manifestations as $manif ) {
            $start = format_date(strtotime($manif['time_start']), 'HH:mm');
            $end = format_date(strtotime($manif['time_end']), 'HH:mm');
            $range = $start . ' - ' . $end;

            if ( $previous_start != $start ) {
                $previous_start = $start;
                $i++;
                $manifestation = array();
                $manifestation['time_id'] = $manif['id'];
                $manifestation['category'] = $manif['time_name'];
                $manifestation['range'] = $range;
            }

            $event = array();
            $event['id'] = $manif['manif_id'];
            $event['name'] = $manif['event_short_name'] ? $manif['event_short_name'] : $manif['event_name'];
            $gauge = array();
            $gauge['id'] = $manif['gauge_id'];
            $gauge['part'] = $manif['part'];
            $gauge['value'] = $manif['gauge'];
            $event['gauge'] = $gauge;
            $manifestation['events'][] = $event;

            $this->json['manifestations'][$i] = $manifestation;
        }
    }

    public function executeUnlockCart(sfWebRequest $request)
    {
        $this->setTemplate('json');
        $this->isDebug($request->hasParameter('debug'));
        $this->getContext()->getConfiguration()->loadHelpers('Url');
        $proId = $request->getParameter('id');
        $this->json = array();

        // get back authorized and targetted OcProfessionals and their OcTickets
        $q = Doctrine::getTable('OcProfessional')->createQuery('op')
            ->andWhere('op.id = ?', $proId)
        ;
        $ocPro = $q->fetchOne();

        if ( !$ocPro ) {
            throw new liEvenementException(sprintf(' id %s is not a valid id for OcProfessional', $proId));
        }

        $ocTransaction = $ocPro->OcTransactions[0];
        $ocTransaction->checkout_state = 'cart';
        $ocTransaction->save();

        $this->json = [
            'id' => $ocPro->id,
            'checkout_state' => $ocTransaction->checkout_state
        ];
    }

    public function executeExportProsWithUnvalidatedCart(sfWebRequest $request)
    {
        $this->getContext()->getConfiguration()->loadHelpers(['I18N', 'CrossAppLink']);
        $ids = $request->getParameter('ids');
        $date = $request->getParameter('date');

        $group = $this->createGroup();
        $group->name = __('Unvalidated Carts Group')
            . ' - ' . $date . ' - ' . date('Y-m-d H:i:s');
        $group->save();

        $pros = $this->findAllProsByOcProIds($ids);
        $this->saveGroupProsFromPros($pros, $group->id);

        $this->redirect(cross_app_url_for('rp', 'group/show?id=' . $group->id));
    }

    public function executeExportAcceptedProsByManifestation(sfWebRequest $request)
    {
        $this->getContext()->getConfiguration()->loadHelpers(['I18N', 'CrossAppLink']);
        $ids = $request->getParameter('ids');
        $manifId = $request->getParameter('manifestationId');

        $manif = $this->findManifestationById($manifId);

        $group = $this->createGroup();
        $manifName = empty($manif->event_short_name) ? $manif->event_name : $manif->event_short_name;
        $group->name = __('Accepted Pros Group')
            . ' - ' . $manifName . ' - ' . $manif->Location->name
            . ' (' . $manif->manif_start . ') - ' . date('Y-m-d H:i:s');
        $group->save();

        $pros = $this->findAllProsByOcProIds($ids);
        $this->saveGroupProsFromPros($pros, $group->id);

        $this->redirect(cross_app_url_for('rp', 'group/show?id=' . $group->id));
    }

    private function findManifestationById($id)
    {

        $qManif = Doctrine_Query::create()
            ->select('ts.id, ts.name AS time_name, ts.starts_at AS time_start, ts.ends_at AS time_end, m.happens_at AS manif_start, m.duration AS manif_duration')
            ->addSelect('m.id AS manif_id, e.id, et.name AS event_name, et.short_name AS event_short_name, g.id AS gauge_id, g.value AS gauge')
            ->addSelect("(SELECT count(*) FROM OcTicket k WHERE k.gauge_id = g.id AND accepted IN ('algo', 'human')) AS part")
            ->distinct()
            ->from('Manifestation m')
            ->innerJoin('m.Event e')
            ->leftJoin('e.Translation et WITH et.lang = ?', $this->getUser()->getCulture())
            ->innerJoin('m.Gauges g')
            ->innerJoin('m.OcTimeSlots ts')
            ->andWhereIn('e.meta_event_id', array_keys($this->getUser()->getMetaEventsCredentials()))
            ->andWhere('g.workspace_id = ?', $this->getUser()->getGuardUser()->OcConfig->workspace_id)
            ->orderBy('ts.starts_at, et.short_name')
            ->andWhere('m.id = ?', $id)
        ;
        return $qManif->fetchOne();
    }

    private function findAllProsByOcProIds($ids = [])
    {
        $qPro = Doctrine::getTable('OcProfessional')->createQuery('op')
            ->select('op.id, p.id')
            ->innerJoin('op.Professional p')
            ->andWhereIn('op.id', $ids)
        ;

        return $qPro->execute();
    }

    private function saveGroupProsFromPros($pros, $groupId)
    {
        foreach ( $pros as $pro ) {
            $grpPro = new GroupProfessional();
            $grpPro->professional_id = $pro->professional_id;
            $grpPro->group_id = $groupId;
            $grpPro->save();
        }
    }

    private function createGroup()
    {
        $group = new Group();
        if ( $this->getUser() instanceof sfGuardSecurityUser ) {
            $group->sf_guard_user_id = $this->getUser()->getId();
        }
        return $group;
    }
}
