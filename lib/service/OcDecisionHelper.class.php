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
     *
     * @param array $data
     * @param int $maxIterations
     * @return array | false
     */
    public function process($data, $maxIterations = 7)
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
    protected function doProcess($maxIterations = 7)
    {
        $iter = count($this->states) + 1;
        if ($iter > $maxIterations) {
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
                return $a['rr'] < $b['rr'] ? -1 : 1;
            });
        }

        // initialize gauges
        $gauges = [];
        foreach ($this->getAllManifestations() as $mid => $manifestation) {
            $gauges[$mid] = $manifestation['gauge_free'];
        }

        // Do the job...
        for ($rank = 1; $rank <= $this->maxRank; $rank++) {
            foreach ($this->getAllManifestations() as $mid => $manifestation) {
                $tsid = $manifestation['time_slot_id'];
                foreach($participants as $pid => $p) {
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
                    $participants[$pid]['points'] += $this->getPoints($rank, $participantRank);
                    $gauges[$mid]--;
                }
            }
        }

        // compute state total points
        $points = 0;
        foreach($participants as $pid => $p) {
            $points += $p['points'];
        }

        // If no improvement, return last state
//        if ($iter > 1 && $this->states[$iter-2]['points'] >= $points) {
//            return $this->states[$iter-2];
//        }

        // compute new relative ranks
        foreach($participants as $pid => $p) {
            $prrt = 0; // previous relative ranks total
            foreach($this->states as $state) {
                $prrt += $state['participants'][$pid]['rr'];
            }
            $ir = $this->participants[$pid]['rank'];  // initial rank
            $nbTimeSlots = count($p['timeSlots']);
            $avgPoints = $nbTimeSlots ? $p['points'] / $nbTimeSlots : 0;
            $participants[$pid]['rr'] = round( ($prrt + $ir + $avgPoints / 1.62) / $iter, 4 );
        }

        $this->states[] = [
            'iteration' => $iter,
            'points' => $points,
            'participants' => $participants,
            'gauges' => $gauges,
        ];

        return $this->doProcess($maxIterations);
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

    /**
     * @param int $choiceRank
     * @param int $participantRank
     * @return float
     */
    protected function getPoints($choiceRank, $participantRank)
    {
        if ($choiceRank > 6) return 0;
        $points = [
            '1' => 10,
            '2' => 6,
            '3' => 4,
            '4' => 3,
            '5' => 2,
            '6' => 1,
        ];
        $N = count($this->participants);
        return $points[$choiceRank] * ( 1 + ($N - $participantRank) / $N * 3);
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