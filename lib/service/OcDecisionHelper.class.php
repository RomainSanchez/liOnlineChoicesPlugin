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
    private $manifestations;

    /**
     * @var array
     */
    private $participants;

    /**
     * @var array
     */
    private $states;


    /**
     * @param array $timeSlots
     * @param array $manifestations
     * @param array $contacts
     */
    public function init($data)
    {
        $this->timeSlots = [];
        $this->manifestations = [];
        $this->participants = [];
        foreach($data as $k => $participant) {
            $this->addParticipant($participant);
        }

        $this->states = [];
    }

    public function process()
    {
        $state = [
            'number' => count($this->states) + 2,
            'participants' => [],
            'RRT' =>  0,
            'PRRT' =>  0,
            'points' =>  0,
        ];


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
        foreach ($this->participants as $p) {
            if ($participant_id == $p['id']) {
                return $p;
            }
        }
        return false;
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
        foreach ($this->participants as $p) {
            if ($participant['id'] == $p['id']) {
                return $this;
            }
        }

        $manifestations = [];
        foreach($participant['manifestations'] as $manifestation) {
            $this->addManifestation($manifestation);
            $manifestations[] = [
                'id' => $manifestation['id'],
                'rank' => $manifestation['rank'],
            ];
        }

        $this->participants[] = [
            'id' => $participant['id'],  // TODO: validate participant
            'name' => isset($participant['name']) ? $participant['name'] : '',
            'rank' => count($this->participants) + 1,
            'manifestations' => $manifestations,
        ];

        return $this;
    }

    /**
     * @param integer $manifestation_id
     * @return array | false if not found
     */
    protected function getManifestation($manifestation_id)
    {
        return isset($this->manifestations[$manifestation_id]) ? $this->manifestations[$manifestation_id] : false;
    }

    /**
     * @return array
     */
    public function getAllManifestations()
    {
        return $this->manifestations;
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

        $this->addTimeSlot(['id' => $manifestation['time_slot_id']]);

        $this->manifestations[$id] = [
            'name' => isset($manifestation['name']) ? $manifestation['name'] : '',
            'time_slot_id' => $manifestation['time_slot_id'],
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
        ];
    }
}