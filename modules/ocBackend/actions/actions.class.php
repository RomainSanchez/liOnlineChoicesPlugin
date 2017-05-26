<?php

require_once dirname(__FILE__).'/../lib/ocBackendGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/ocBackendGeneratorHelper.class.php';

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

  public function executeIndex(sfWebRequest $request)
  {
    parent::executeIndex($request);

    $this->form = new OcSnapshotForm();
    $this->_csrf_token = $this->form->getCSRFToken();

    $dates = $this->getDates();
    if ( count($dates) == 0 )
      return;

    $date = $request->getParameter('date');
    $this->day = $date;
    if ( !$date || $date && !strtotime($date) || array_search($date, array_column($dates, 'm_start')) === false )
    {
      $this->day = $dates[0]['m_start'];
    }

    // List snapshots
    $this->snapshots = Doctrine::getTable('OcSnapshot')->createQuery('s')
      ->andWhere('s.sf_guard_user_id = ?', $this->getUser()->getId())
      ->andWhere('date(s.day) = ?', $this->day)
      ->orderBy('s.created_at DESC')
      ->execute();

  }
  
  public function executeAutoPositioning(sfWebRequest $request)
  {
    // TODO
  }

  protected function isDebug($debug)
  {
    if ( $debug )
    {
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

  public function executeLoadSnapshot(sfWebRequest $request)
  {
    $ocs = Doctrine::getTable('ocSnapshot')->findOneById(intval($request->getParameter('id')));

    if ( $ocs && $ocs->sf_guard_user_id == $this->getUser()->getId())
    {
      $this->json = unserialize($ocs->content);
    }
    else
    {
      $this->json = array();
      $this->json['error'] = 'Error';
    }
    
    $this->setTemplate('json');
    $this->isDebug($request->hasParameter('debug'));
  }
  public function executeSaveSnapshot(sfWebRequest $request)
  {
    $this->json = array();
    $this->json['error'] = 'Error';

    if ( $data = json_decode($request->getParameter('content'), true) )
    {
      $ocsf = new OcSnapshotForm();
      $params = array();
      $params['name'] = $request->getParameter('name');
      $params['day'] = $request->getParameter('day');
      $params['purpose'] = $request->getParameter('purpose');
      $params['sf_guard_user_id'] = $this->getUser()->getId();
      $params['content'] = serialize($data);
      $params['_csrf_token'] = $request->getParameter('_csrf_token');
      
      $ocsf->bind($params);
      
      if ( $ocsf->isValid() ) {
        $ocsf->save();
        $this->json['error'] = 'Success';
      } else {
        foreach ($ocsf->getErrorSchema()->getErrors() as $name => $error) {
          $this->json['message'][$name] = (string)$error;
        }
      }
    }
    else 
    {
      $this->json['message'] = json_last_error_msg();
    }
    
    $this->setTemplate('json');
    $this->isDebug($request->hasParameter('debug'));
  }

  public function executePros(sfWebRequest $request)
  {
    $this->json = array();
    
    $date = $request->getParameter('date');
    if ( $date && !strtotime($date) )
      return;

    $dates = $this->getDates();
    if ( count($dates) == 0 )
      return;

    if ( !$date )
      $date = $dates[0]['m_start'];

    $ocProfessionals = Doctrine_Query::create()
      ->select('op.id, p.id, t.id, c.firstname, c.name, g.id, m.id, k.rank, k.accepted')
      ->from('OcProfessional op')
      ->leftJoin('op.Professional p')
      ->leftJoin('p.Contact c')
      ->leftJoin('op.OcTransactions t')
      ->leftJoin('t.OcTickets k')
      ->leftJoin('k.Gauge g')
      ->leftJoin('g.Manifestation m WITH date(m.happens_at) = ?', $date)
      ->leftJoin('m.Event e')
      ->leftJoin('e.MetaEvent me')
      ->leftJoin('me.MetaEventUser meu')
      ->andWhere('meu.sf_guard_user_id = ?', $this->getUser()->getId())
      ->orderBy('c.name, c.firstname')
      ->fetchArray();

    foreach ($ocProfessionals as $ocPro)
    {
      $pro = array();
            
      $pro['id'] = $ocPro['id'];
      $pro['name'] = $ocPro['Professional']['Contact']['firstname'].' '.$ocPro['Professional']['Contact']['name'];
      $pro['manifestations'] = array();
      
      if ( count($ocPro['OcTransactions']) > 0 )
      foreach ($ocPro['OcTransactions'][0]['OcTickets'] as $ticket)
      {
        $manifestation = array();
        $manifestation['id'] = $ticket['Gauge']['Manifestation']['id'];
        $manifestation['rank'] = $ticket['rank'];
        $manifestation['accepted'] = $ticket['accepted'];
        $pro['manifestations'][] = $manifestation;
      }
      
      $this->json[] = $pro;
    }
    
    $this->setTemplate('json');
    $this->isDebug($request->hasParameter('debug'));
  }

  public function executeEvents(sfWebRequest $request)
  {
    $this->getContext()->getConfiguration()->loadHelpers(['Date', 'Array']);
    
    $this->json = array();
    $current = $previous = $next = null;
    
    $date = $request->getParameter('date');
    if ( $date && !strtotime($date) )
      return;

    $dates = $this->getDates();
    if ( count($dates) == 0 )
      return;

    if ( !$date )
      $date = $dates[0]['m_start'];
    
    $q = "
      SELECT DISTINCT ts.id, ts.name AS time_name, ts.starts_at AS time_start, ts.ends_at AS time_end, m.happens_at AS manif_start, m.duration AS manif_duration, 
        m.id AS manif_id, et.name AS event_name, et.short_name AS event_short_name, g.id AS gauge_id, g.value AS gauge,
        (SELECT count(*) FROM oc_ticket k WHERE k.gauge_id = g.id AND accepted IN ('algo', 'human')) AS part
      FROM manifestation m
      INNER JOIN event e ON e.id = m.event_id
      INNER JOIN event_translation et ON et.id = e.id
      INNER JOIN meta_event me ON me.id = e.meta_event_id
      INNER JOIN meta_event_user meu ON meu.meta_event_id = me.id
      INNER JOIN gauge g ON g.manifestation_id = m.id
      INNER JOIN oc_time_slot ts ON m.happens_at >= ts.starts_at AND m.happens_at < ts.ends_at
      WHERE date(m.happens_at) = :selected_date
      AND meu.sf_guard_user_id = :user_id
      ORDER BY ts.starts_at, et.short_name
    ";
    $pdo = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    $stmt = $pdo->prepare($q);
    $stmt->execute(array('selected_date' => $date, 'user_id' => $this->getUser()->getId()));
    $manifestations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $day = array_search($date, array_column($dates, 'm_start'));
    
    if ( $day !== false )
    {
      $current = $dates[$day]['m_start'];
      
      if ( $day > 0 && $dates[$day - 1]['m_total'] > 0 )
        $previous = $dates[$day - 1]['m_start'];
      
      if ( $day < count($dates)-1 && $dates[$day + 1]['m_total'] > 0 )
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
    
    foreach ($manifestations as $manif)
    {
      $start = format_date(strtotime($manif['time_start']), 'HH:mm');
      $end = format_date(strtotime($manif['time_end']), 'HH:mm');
      $range = $start.' - '.$end;
            
      if ( $previous_start != $start )
      {
        $previous_start = $start;
        $i++;
        $manifestation = array();
        $manifestation['time_id'] = $manif['id'];
        $manifestation['category'] = $manif['time_name'];
        $manifestation['range'] = $range;
      }

      $event = array();
      $event['id'] = $manif['manif_id'];
      $event['name'] = $manif['event_short_name'];
      $gauge = array();
      $gauge['id'] = $manif['gauge_id'];
      $gauge['part'] = $manif['part'];
      $gauge['value'] = $manif['gauge'];
      $event['gauge'] = $gauge;
      $manifestation['events'][] = $event;

      $this->json['manifestations'][$i] = $manifestation;
    }
  
    $this->setTemplate('json');
    $this->isDebug($request->hasParameter('debug'));
  }
}
