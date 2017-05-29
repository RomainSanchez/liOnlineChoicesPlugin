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
     * @param array $timeSlots
     * @param array $manifestations
     * @param array $contacts
     */
    public function init($data)
    {
        $this->timeSlots = [];
        $this->participants = [];
        $this->maxRank = 0;
        foreach($data as $participant) {
            $this->addParticipant($participant);
        }
        $this->states = [];
    }

    /**
     * @param int $maxIterations
     * @return array
     */
    public function process($maxIterations = 7)
    {
        $iter = count($this->states) + 1;
        if ($iter > $maxIterations) {
            return $iter > 1 ? $this->states[$iter-2] : null;
        }
        $state = [
            'iteration' => $iter,
            'participants' => [],
            'RRTotal' =>  0,
            'PRRT' =>  0,
            'points' =>  0,
        ];

        // initialize participants
        $participants = [];
        foreach($this->participants as $id => $p) {
            $participants[$id] = [
                'id' => $id,
                'rr' => $iter > 1 ? $this->states[$iter-2]['participants'][$id]['rr'] : 0,
                'timeSlots' => $p['timeSlots'],
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
                    if ($p['timeSlots'][$tsid][$mid] != $rank) {
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
        ];
        return $this->process($maxIterations);
    }

    /**
     * @param int $choiceRank
     * @return int
     */
    private function getPoints($choiceRank)
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
    private function validateInitialData()
    {
        return true;
    }

    /**
     * @param integer $participant_id
     * @return array | false if not found
     */
    protected function getParticipant($participant_id)
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
        foreach($participant['manifestations'] as $manifestation) {
            $this->addManifestation($manifestation);

            $tsid = $manifestation['time_slot_id'];
            $mid = $manifestation['id'];
            if (!isset($timeSlots[$tsid])) {
                $timeSlots[$tsid] = [];
            }

            $timeSlots[$tsid][$mid] = $manifestation['rank'];
            $this->maxRank = max($this->maxRank, $manifestation['rank']);
        }

        $this->participants[$id] = [
            'name' => isset($participant['name']) ? $participant['name'] : '',
            'rank' => count($this->participants) + 1,
            'timeSlots' => $timeSlots,
        ];

        return $this;
    }

    /**
     * @param integer $manifestation_id
     * @return array | false if not found
     */
    protected function getManifestation($manifestation_id)
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
    protected function addManifestation($manifestation)
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
    protected function getTimeSlot($time_slot_id)
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
    protected function addTimeSlot($timeSlot)
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