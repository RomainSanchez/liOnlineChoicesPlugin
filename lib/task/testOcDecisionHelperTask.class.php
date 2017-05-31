<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of testOcDecisionHelperTask
 *
 * @author Marcos Bezerra de Menezes <marcos.bezerra@libre-informatique.fr>
 */
class testOcDecisionHelperTask extends sfBaseTask
{

    private $service;

    public function configure()
    {
        $this->namespace = 'e-venement';
        $this->name = 'test-oc-decision-helper';
        $this->briefDescription = 'Test the Online Choices Decision Helper service';
        $this->detailedDescription = "";

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'default'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
            new sfCommandOption('random', null, sfCommandOption::PARAMETER_NONE, 'Random data'),
        ));

        $this->addArguments(array(
            new sfCommandArgument('input', sfCommandArgument::OPTIONAL, 'The json file to parse'),
        ));
    }

    public function execute($arguments = array(), $options = array())
    {
        sfContext::createInstance($this->configuration, $options['env']);

        // get the service we want to test
        $this->service = sfContext::createInstance($this->configuration)->getContainer()->get('oc_decision_helper');
        if ($arguments['input']) {
            $data = $this->getSampleDataFromFile($arguments['input']);
        }
        elseif ($options['random']) {
            $data = $this->getRandomData();
        }
        else {
            $data = $this->getSampleData();
        }

        $output = $this->service->process($data);
//        print_r($output);
        print "\n";
        $state = $this->service->getLastState();
        $this->displayState($data, $state);
        print "\n";
//        $randomData = $this->getRandomData();
//        print_r($randomData);
    }

    protected function getSampleDataFromFile($file)
    {
        if (!is_file($file)) {
            throw new \Exception('File not found: ' . $file);
        }
        $str = file_get_contents($file);
        return json_decode($str, true);
    }

    /**
     * @return array [$timeSlots, $manifestations, $contacts]
     */
    protected function getSampleData()
    {
        return [
            ['id' => 1, 'name' => 'Ambassadeur A', 'manifestations' => [
                    ['id' => 1, 'time_slot_id' => 1, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 2, 'time_slot_id' => 1, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 3, 'time_slot_id' => 1, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                    ['id' => 4, 'time_slot_id' => 2, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 5, 'time_slot_id' => 2, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                ]],
            ['id' => 2, 'name' => 'Ambassadeur B', 'manifestations' => [
                    ['id' => 1, 'time_slot_id' => 1, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 2, 'time_slot_id' => 1, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 3, 'time_slot_id' => 1, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                    ['id' => 5, 'time_slot_id' => 2, 'gauge_free' => 2, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 6, 'time_slot_id' => 2, 'gauge_free' => 3, 'rank' => 2, 'accepted' => 'none'],
                ]],
            ['id' => 3, 'name' => 'Ambassadeur C', 'manifestations' => [
                    ['id' => 1, 'time_slot_id' => 1, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'human'],
                    ['id' => 2, 'time_slot_id' => 1, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 3, 'time_slot_id' => 1, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                    ['id' => 4, 'time_slot_id' => 2, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 5, 'time_slot_id' => 2, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 6, 'time_slot_id' => 2, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                ]],
            ['id' => 4, 'name' => 'Ambassadeur D', 'manifestations' => [
                    ['id' => 1, 'time_slot_id' => 1, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 2, 'time_slot_id' => 1, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 3, 'time_slot_id' => 1, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                    ['id' => 4, 'time_slot_id' => 2, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 5, 'time_slot_id' => 2, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 6, 'time_slot_id' => 2, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                ]],
            ['id' => 5, 'name' => 'Ambassadeur E', 'manifestations' => [
                    ['id' => 1, 'time_slot_id' => 1, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 2, 'time_slot_id' => 1, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 3, 'time_slot_id' => 1, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                    ['id' => 4, 'time_slot_id' => 2, 'gauge_free' => 1, 'rank' => 1, 'accepted' => 'none'],
                    ['id' => 5, 'time_slot_id' => 2, 'gauge_free' => 2, 'rank' => 2, 'accepted' => 'none'],
                    ['id' => 6, 'time_slot_id' => 2, 'gauge_free' => 3, 'rank' => 3, 'accepted' => 'none'],
                ]],
        ];
    }

    protected function getRandomData()
    {
        $nbParticipants = 50;
        $nbTimeSlots = 2;
        $nbManifestations = 3;
        $gauge_free = 5;

        $data = [];
        for ($pid = 1; $pid <= $nbParticipants; $pid++) {
            $participant = [
                'id' => $pid,
                'name' => "Participant $pid",
            ];
            $manifestations = [];
            for ($tsid = 0; $tsid < $nbTimeSlots; $tsid++) {
                if (rand(1, 100) < 50) {
                    continue;
                }
                $availableMids = range($tsid * $nbManifestations, ($tsid + 1) * $nbManifestations - 1);
                $mids = (array)array_rand($availableMids, rand(1, $nbManifestations));
                shuffle($mids);
                foreach ($availableMids as $k => $mid) {
                    if (in_array($k, $mids)) {
                        $manifestations[] = [
                            'id' => $mid + 1,
                            'time_slot_id' => $tsid + 1,
                            'gauge_free' => $gauge_free,
                            'rank' => array_search($k, $mids) + 1,
                            'accepted' => 'none',
                        ];
                    }
                }

            }
            $participant['manifestations'] = $manifestations;
            $data[] = $participant;
        }
        return $data;
    }

    protected function displayState($data, $state)
    {
        print "\n\n";

        print_r($this->service->getAllManifestations());

        $mask = "| %-20.20s| %5.5s | %5.5s ||";

        $manifestations = [];
        foreach ($this->service->getAllManifestations() as $mid => $m) {
            $manifestations[$mid] = [
                'name' => sprintf("t%d.m%d", $m['time_slot_id'], $mid),
                'tsid' => $m['time_slot_id']
            ];
            $mask .= " %5.5s |";
        }
        $mask .= "\n";

        $line = [
            'Participant',
            'IR',
            'RR',
        ];
        foreach ($manifestations as $manifestation) {
            $line[] = $manifestation['name'];
        }
        vprintf($mask, $line);

        $hline = [
            '---------------------------',
            '---------------------------',
            '---------------------------',
        ];
        foreach ($manifestations as $manifestation) {
            $hline[] = '---------------------------';
        }
        vprintf($mask, $hline);

        foreach ($data as $k => $participant) {
            $pid = $participant['id'];
            $line = [
                $participant['name'],
                $k+1,
                $state['participants'][$pid]['rr'],
            ];
            foreach ($manifestations as $mid => $manifestation) {
                $rank = '';
                foreach ($participant['manifestations'] as $m) {
                    if ($m['id'] == $mid) {
                        $rank = $m['rank'];
                        $tsid = $manifestation['tsid'];
                        if ($m['accepted'] == 'human') {
                            $rank = '[' . $rank . ']';
                        }
                        elseif (isset($state['participants'][$pid]['timeSlots'][$tsid]) && $state['participants'][$pid]['timeSlots'][$tsid] == $mid) {
                            $rank = '*' . $rank . '*';
                        }
                        break;
                    }
                }
                $line[] = $rank;
            }
            vprintf($mask, $line);
        }

        printf("\n\nIteration #%d\n", $state['iteration']);
        printf("Points total: %d\n\n", $state['points']);
    }

}