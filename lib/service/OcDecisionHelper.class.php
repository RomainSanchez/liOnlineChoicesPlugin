<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OcDecisionHelper
 *
 * @author Marcos Bezerra de Menezes <marcos.bezerra@libre-informatique.fr>
 */
class OcDecisionHelper
{
    /**
     * @var array
     */
    private $timeSlots;

    /**
     * @var array
     */
    private $participants;

    /**
     * @var array
     */
    private $states;

    /**
     * @var integer
     */
    private $maxRank = 0;

    /**
     * @var array
     */
    protected $points = [
        '1' => 10,
        '2' => 6,
        '3' => 4,
        '4' => 3,
        '5' => 2,
        '6' => 1,
        null => 0,
    ];
    
    private $options = [];

    /**
     *
     * @param array $data
     * @param int $maxIterations
     * @return array | false
     */
    public function process($data, $maxIterations = 10)
    {
        $this->init($data);
        $this->doProcess($maxIterations);

        //echo "\n================== best state:\n";
        //print_r($this->getBestState());

        return $this->formatOutput();
    }

    /**
     * @param array $data
     */
    protected function init($data)
    {
        file_put_contents('/home/beta/tmp/test.auto.json', json_encode($data, JSON_PRETTY_PRINT));
        
        $this->maxRank = 0;
        $this->states = [];

        // Init time slots
        $this->timeSlots = [];
        foreach($data['timeSlots'] as $ts) {
            $tsid = $ts['id'];
            $manifestations = [];
            foreach($ts['manifestations'] as $m) {
                $mid = $m['id'];
                $manifestations[$mid] = [
                    'name' => isset($m['name']) ? $m['name'] : "t$tsid.m$mid",
                    'time_slot_id' => $tsid,
                    'gauge_free' => $m['gauge_free'],
                ];
            }
            $this->timeSlots[$tsid] = [
                'id' => $tsid,
                'name' => isset($ts['name']) ? $ts['name'] : "t$tsid",
                'manifestations' => $manifestations,
            ];
        }

        // Init participants
        $this->participants = [];
        foreach($data['participants'] as $k => $p) {
            $pid = $p['id'];
            $timeSlots = [];
            $humanTsIds = [];
            foreach($p['manifestations'] as $m) {
                $mid = $m['id'];
                $manifestation = $this->getManifestation($mid);
                if (false === $manifestation) {
                    throw new Exception(sprintf('Manifestation id=%d not found when paring participant id=%d', $mid, $pid));
                }
                $tsid = $manifestation['time_slot_id'];
                if (!isset($timeSlots[$tsid])) {
                    $timeSlots[$tsid] = [];
                }

                $isHuman = $m['accepted'] == 'human';
                $timeSlots[$tsid][$mid] = [
                    'rank' => $m['rank'],
                    'human' => $isHuman,
                ];
                if ($isHuman) {
                    $humanTsIds[] = $tsid;
                }

                if ($m['accepted'] == 'algo') {
                    $this->timeSlots[$tsid]['manifestations'][$mid]['gauge_free']++;
                }
            }

            // Update $this->maxRank when needed
            foreach($timeSlots as $tsid => $ts) {
                if (in_array($tsid, $humanTsIds)) {
                    continue;
                }
                foreach($ts as $m) {
                    $this->maxRank = max($this->maxRank, $m['rank']);
                }
            }

            $this->participants[$pid] = [
                'name' => isset($p['name']) ? $p['name'] : "p$pid",
                'rank' => $k + 1,
                'timeSlots' => $timeSlots,
                'humanTsIds' => $humanTsIds,
            ];
        }

        //echo "\n================== timeSlots:\n";
        //print_r($this->timeSlots);
        //echo "\n================== participants:\n";
        //print_r($this->participants);
    }


    /**
     * @param int $maxIterations
     * @return array
     */
    protected function doProcess($maxIterations = 10)
    {
        $iter = count($this->states) + 1;
        if ($iter > $maxIterations) {
            
            // TODO remove this test part
            /*
            $arr = [];
            foreach ( $this->states[1]['participants'] as $p ){
                $arr[$p['id']] = $p['rr'];
            }
            print_r($arr);
            */
            
            return $iter > 1 ? $this->states[$iter-2] : null;
        }

        // initialize participants
        $participants = [];
        foreach($this->participants as $id => $p) {
            $timeSlots = $p['timeSlots'];
            foreach($p['humanTsIds'] as $tsid) {
                unset($timeSlots[$tsid]);
            }
            $participants[$id] = [
                'id' => $id,
                'rr' => $iter > 1 ? $this->states[$iter-2]['participants'][$id]['rr'] : 0,
                'timeSlots' => $timeSlots,
                'points' => 0,
            ];
        }
        
        // sort participants by previous relative rank then by initial rank
        if ($iter > 1) {
            uasort($participants, function($a, $b) {
                if($a['rr'] == $b['rr']) {
                    return $this->participants[$a['id']]['rank'] < $this->participants[$b['id']]['rank'] ? -1 : 1;
                }
                return $a['rr'] > $b['rr'] ? -1 : 1;
            });
        }
        
        // initialize gauges
        $gauges = [];
        foreach ($this->getAllManifestations() as $mid => $manifestation) {
            $gauges[$mid] = $manifestation['gauge_free'];
        }
        
        $this->doProcessRaw($participants, $gauges);
        $points = $this->doProcessPoints($participants);
        if ( !$this->getOption('noUpgrade', false) ) {
            $this->upgradeUnlukies($participants);
        }
        
        $this->states[] = [
            'iteration' => $iter,
            'points' => $points,
            'participants' => $participants,
            'gauges' => $gauges,
        ];

        return $this->doProcess($maxIterations);
    }

    protected function doProcessRaw(array &$participants, array $gauges)
    {
        // Do the job...
        $tsids = [];
        foreach($participants as $pid => $p) {
            $tsids[$pid] = [];
            for ($rank = 1; $rank <= $this->maxRank; $rank++) {
                foreach ($this->getAllManifestations() as $mid => $manifestation) {
                    $tsid = $manifestation['time_slot_id'];
                    if ( in_array($tsid, $tsids[$pid]) ){
                        continue;
                    }
                    if (!isset($p['timeSlots'][$tsid][$mid])) {
                        continue;
                    }
                    if ($gauges[$mid] == 0 && is_array($p['timeSlots'][$tsid])) {
                        unset($participants[$pid]['timeSlots'][$tsid][$mid]);
                        continue;
                    }
                    if ($p['timeSlots'][$tsid][$mid]['rank'] != $rank) {
                        continue;
                    }
                    $participants[$pid]['timeSlots'][$tsid] = $mid;
                    $participantRank = $this->participants[$pid]['rank'];
                    $participants[$pid]['points'] += $this->getPoints($rank, $participantRank, count($p['timeSlots']));
                    $gauges[$mid]--;
                    $tsids[$pid][] = $tsid;
                }
            }
        }
        
        return $this;
    }
    
    protected function doProcessPoints(array &$participants)
    {
        // compute state total points
        $points = 0;
        foreach($participants as $pid => $p) {
            $points += $participants[$pid]['rr'] = $p['points'];
        }
        
        return $points;
    }
    
    protected function upgradeUnlukies(array &$participants)
    {
        // try to upgrade participants with bad luck...
        $lasts = [];
        $i = -1;
        foreach ( $participants as $pid => $p ) {
            $i++;
            $last[] = &$participants[$pid];
            if ( $i < 2 ) {
                continue;
            }
            if ( $last[$i-1]['rr'] < $last[$i]['rr'] ) {
                $first = $last[$i-2]['rr'];
                $last[$i-2]['rr'] = $last[$i]['rr'];
                $last[$i]['rr']   = $last[$i-1]['rr'];
                $last[$i-1]['rr'] = $first; // aka last[$i-2]['rr'];
            }
        }

        return $this;
    }
    
    /**
     * @param int $choiceRank
     * @param int $participantRank
     * @return float
     */
    protected function getPoints($choiceRank, $participantRank, $timeSlotsCount)
    {
        if ($choiceRank > 6) {
            return 0;
        }
        $N = count($this->participants);
        return $this->points[$choiceRank]/$timeSlotsCount * ( 1 + ($N-$participantRank) / (pow($N,2)/2) );
    }

    /**
     * @return array | false
     */
    protected function formatOutput()
    {
        $state = $this->getBestState();
        if (!$state) {
            return false;
        }

        $timeSlots = [];
        foreach ($this->timeSlots as $tsid => $ts) {
            $manifestations = [];
            foreach ($ts['manifestations'] as $mid => $m) {
                $manifestations[] = [
                    'id' => $mid,
                    'name' => $m['name'],
                    'gauge_free' => $state['gauges'][$mid],
                ];
            }
            $timeSlots[] = [
                'id' => $tsid,
                'name' => $ts['name'],
                'manifestations' => $manifestations,
            ];
        }

        $participants = [];
        foreach($this->participants as $pid => $p) {
            $manifestations = [];
            foreach($p['timeSlots'] as $tsid => $ts) {
                foreach($ts as $mid => $m) {
                    $accepted = 'none';
                    if ($m['human']) {
                        $accepted = 'human';
                    }
                    else if (isset($state['participants'][$pid]['timeSlots'][$tsid])
                        && $state['participants'][$pid]['timeSlots'][$tsid] == $mid) {
                        $accepted = 'algo';
                    }
                    $manifestations[] = [
                        'id' => $mid,
                        'rank' => $m['rank'],
                        'accepted' => $accepted,
                    ];
                }
            }
            $participants[] = [
                'id' => $pid,
                'name' => $p['name'],
                'manifestations' => $manifestations,
            ];
        }

        $output = [
            'timeSlots' => $timeSlots,
            'participants' => $participants,
        ];

        //echo "\n================== output:\n";
        //print_r($output);

        return $output;
    }
    
    public function setOption($option, $value)
    {
        if ( $value === NULL ) {
            unset($this->options[$option]);
        }
        $this->options[$option] = $value;
        return $this;
    }

    public function getOption($option, $defaultValue)
    {
        if ( !isset($this->options[$option]) ) {
            return $defaultValue;
        }
        
        return $this->options[$option];
    }

    /**
     * @return boolean
     * @todo
     */
    public function validateInitialData()
    {
        return true; // TODO
    }

    /**
     * @return array
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @return array
     */
    public function getState($i)
    {
        return $this->states[$i-1];
    }

    /**
     * @return array
     */
    public function getBestState()
    {
        $maxPoints = -1;
        $bestStateId = -1;
        foreach($this->states as $k => $state) {
            if ($state['points'] > $maxPoints) {
                $bestStateId = $k;
                $maxPoints = $state['points'];
            }
        }
        return $bestStateId >= 0 ? $this->states[$bestStateId] : [];
    }

    /**
     * @return array
     */
    public function getLastState()
    {
        $c = count($this->states);
        return $c ? $this->states[$c-1] : [];
    }

    /**
     * @param integer $participant_id
     * @return array | false if not found
     */
    public function getParticipant($participant_id)
    {
        return isset($this->participants[$participant_id]) ? $this->participants[$participant_id] : false;
    }

    /**
     * @return array
     */
    public function getAllParticipants()
    {
        return $this->participants;
    }

    /**
     * @param integer $manifestation_id
     * @return array | false if not found
     */
    public function getManifestation($manifestation_id)
    {
        foreach ($this->timeSlots as $ts) {
            if (isset($ts['manifestations'][$manifestation_id])) {
                return $ts['manifestations'][$manifestation_id];
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getAllManifestations()
    {
        $manifestations = [];
        foreach ($this->timeSlots as $ts) {
            foreach($ts['manifestations'] as $mid => $manif) {
                $manifestations[$mid] = $manif;
            }
        }
        return $manifestations;
    }

    /**
     * @param integer $time_slot_id
     * @return array | false if not found
     */
    public function getTimeSlot($time_slot_id)
    {
        return isset($this->timeSlots[$time_slot_id]) ? $this->timeSlots[$time_slot_id] : false;
    }

    /**
     * @return array
     */
    public function getAllTimeSlots()
    {
        return $this->timeSlots;
    }

}
