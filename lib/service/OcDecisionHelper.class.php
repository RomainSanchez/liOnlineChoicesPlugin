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
        return $this->formatOutput();
    }

    /**
     * @param array $data
     */
    protected function init($data)
    {
        $this->timeSlots = [];
        $this->participants = [];
        $this->maxRank = 0;
        $this->states = [];
        foreach($data as $participant) {
            $this->addParticipant($participant);
        }
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
                    $participants[$pid]['points'] += $this->getPoints($rank);
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
        if ($iter > 1 && $this->states[$iter-2]['points'] >= $points) {
            return $this->states[$iter-2];
        }

        // compute new relative ranks
        foreach($participants as $pid => $p) {
            $prrt = 0; // previous relative ranks total
            foreach($this->states as $state) {
                $prrt += $state['participants'][$pid]['rr'];
            }
            $ir = $this->participants[$pid]['rank'];  // initial rank
            $nbTimeSlots = count($p['timeSlots']);
            $avgPoints = $nbTimeSlots ? $p['points'] / $nbTimeSlots : 0;
            $participants[$pid]['rr'] = round( ($prrt + $ir + $avgPoints / 1.62) / $iter );
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
        $iter = count($this->states);
        if (!$iter) {
            return false;
        }
        $state = $this->states[$iter-1];
        $output = [];
        foreach($this->participants as $pid => $p) {
            $manifestations = [];
            foreach($p['timeSlots'] as $tsid => $ts) {
                foreach($ts as $mid => $m) {
                    $accepted = 'none';
                    if ($m['human']) {
                        $accepted = 'human';
                    }
                    else if ($state['participants'][$pid]['timeSlots'][$tsid] == $mid) {
                        $accepted = 'algo';
                    }

                    $manifestations[] = [
                        'id' => $mid,
                        'time_slot_id' => $tsid,
                        'gauge_free' => $state['gauges'][$mid],
                        'rank' => $m['rank'],
                        'accepted' => $accepted,
                    ];
                }
            }
            $output[] = [
                'id' => $pid,
                'name' => $p['name'],
                'manifestations' => $manifestations,
            ];
        }
        return $output;
    }

    /**
     * @param int $choiceRank
     * @return int
     */
    protected function getPoints($choiceRank)
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
        return $points[$choiceRank];
    }

    /**
     * @return boolean
     * @todo
     */
    public function validateInitialData()
    {
        return true;
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
     * @param array $participant
     * @return self
     */
    protected function addParticipant($participant)
    {
        $id = $participant['id'];
        if ($this->getParticipant($id)) {
            return $this;
        }

        $timeSlots = [];
        $humanTsIds = [];
        foreach($participant['manifestations'] as $manifestation) {
            $this->addManifestation($manifestation);

            $tsid = $manifestation['time_slot_id'];
            $mid = $manifestation['id'];
            if (!isset($timeSlots[$tsid])) {
                $timeSlots[$tsid] = [];
            }

            $isHuman = $manifestation['accepted'] == 'human';
            $timeSlots[$tsid][$mid] = [
                'rank' => $manifestation['rank'],
                'human' => $isHuman,
            ];
            if ($isHuman) {
                $humanTsIds[] = $tsid;
            }

            if ($manifestation['accepted'] == 'algo') {
                $this->timeSlots[$tsid][$mid]['gauge_free']++;
            }
        }

        foreach($timeSlots as $tsid => $ts) {
            if (in_array($tsid, $humanTsIds)) {
                continue;
            }
            foreach($ts as $m) {
                $this->maxRank = max($this->maxRank, $m['rank']);
            }
        }

        $this->participants[$id] = [
            'name' => isset($participant['name']) ? $participant['name'] : '',
            'rank' => count($this->participants) + 1,
            'timeSlots' => $timeSlots,
            'humanTsIds' =>$humanTsIds,
        ];

        return $this;
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
     * @param array $manifestation
     * @return self
     */
    public function addManifestation($manifestation)
    {
        $id = $manifestation['id'];
        if ($this->getManifestation($id)) {
            return $this;
        }

        $tsid = $manifestation['time_slot_id'];
        $this->addTimeSlot(['id' => $tsid]);

        $this->timeSlots[$tsid]['manifestations'][$id] = [
            'name' => isset($manifestation['name']) ? $manifestation['name'] : '',
            'time_slot_id' => $tsid,
            'gauge_free' => $manifestation['gauge_free'],
        ];
        return $this;
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

    /**
     * @param array $timeSlot
     * @return self
     */
    public function addTimeSlot($timeSlot)
    {
        $id = $timeSlot['id'];
        if ($this->getTimeSlot($id)) {
            return $this;
        }

        $this->timeSlots[$id] = [
            'id' => $id,
            'name' => isset($timeSlot['name']) ? $timeSlot['name'] : sprintf('TS #%d', count($this->timeSlots) + 1),
            'manifestations' => [],
        ];
    }
}