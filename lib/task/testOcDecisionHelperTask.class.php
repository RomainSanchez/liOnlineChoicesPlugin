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

        $output = $this->service->process($data, 10);
        print "\n";
        $state = $this->service->getBestState();
        $this->displayState($state);
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
            'timeSlots' => [
                ['id' => 1, 'manifestations' => [
                        ['id' => 1, 'gauge_free' => 1],
                        ['id' => 2, 'gauge_free' => 2],
                        ['id' => 3, 'gauge_free' => 3],
                    ]
                ],
                ['id' => 2, 'manifestations' => [
                        ['id' => 4, 'gauge_free' => 1],
                        ['id' => 5, 'gauge_free' => 2],
                        ['id' => 6, 'gauge_free' => 3],
                    ]
                ],
            ],
            'participants' => [
                ['id' => 1, 'name' => 'Ambassadeur A', 'manifestations' => [
                        ['id' => 1,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 2,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 3,  'rank' => 3, 'accepted' => 'none'],
                        ['id' => 4,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 5,  'rank' => 2, 'accepted' => 'none'],
                    ]],
                ['id' => 2, 'name' => 'Ambassadeur B', 'manifestations' => [
                        ['id' => 1,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 2,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 3,  'rank' => 3, 'accepted' => 'none'],
                        ['id' => 5,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 6,  'rank' => 2, 'accepted' => 'none'],
                    ]],
                ['id' => 3, 'name' => 'Ambassadeur C', 'manifestations' => [
                        ['id' => 1,  'rank' => 1, 'accepted' => 'human'],
                        ['id' => 2,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 3,  'rank' => 3, 'accepted' => 'none'],
                        ['id' => 4,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 5,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 6,  'rank' => 3, 'accepted' => 'none'],
                    ]],
                ['id' => 4, 'name' => 'Ambassadeur D', 'manifestations' => [
                        ['id' => 1,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 2,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 3,  'rank' => 3, 'accepted' => 'none'],
                        ['id' => 4,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 5,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 6,  'rank' => 3, 'accepted' => 'none'],
                    ]],
                ['id' => 5, 'name' => 'Ambassadeur E', 'manifestations' => [
                        ['id' => 1,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 2,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 3,  'rank' => 3, 'accepted' => 'none'],
                        ['id' => 4,  'rank' => 1, 'accepted' => 'none'],
                        ['id' => 5,  'rank' => 2, 'accepted' => 'none'],
                        ['id' => 6,  'rank' => 3, 'accepted' => 'none'],
                    ]],
            ]
        ];
    }

    protected function getRandomData()
    {
        $nbParticipants = 50;
        $nbTimeSlots = 2;
        $nbManifestations = 3;
        $gauge_free_max = 10;

        $timeSlots = [];
        for ($tsid = 1; $tsid <= $nbTimeSlots; $tsid++) {
            $manifestations = [];
            for ($mid = ($tsid-1) * $nbManifestations + 1; $mid <= $tsid * $nbManifestations; $mid++) {
                $manifestations[] = ['id' => $mid, 'gauge_free' => rand(0, $gauge_free_max)];
            }
            $timeSlots[] = ['id' => $tsid, 'manifestations' => $manifestations];
        }

        $participants = [];
        for ($pid = 1; $pid <= $nbParticipants; $pid++) {
            $manifestations = [];
            for ($tsid = 0; $tsid < $nbTimeSlots; $tsid++) {
                if (rand(1, 100) < 30) {
                    continue;
                }
                $availableMids = range($tsid * $nbManifestations, ($tsid + 1) * $nbManifestations - 1);
                $mids = (array)array_rand($availableMids, rand(1, $nbManifestations));
                shuffle($mids);
                foreach ($availableMids as $k => $mid) {
                    if (in_array($k, $mids)) {
                        $manifestations[] = [
                            'id' => $mid + 1,
                            'rank' => array_search($k, $mids) + 1,
                            'accepted' => 'none',
                        ];
                    }
                }

            }
            $participants[] = [
                'id' => $pid,
                'name' => "Participant $pid",
                'manifestations' => $manifestations,
            ];
        }

        $data = ['timeSlots' => $timeSlots, 'participants' => $participants];
        //print_r($data); die();
        return $data;
    }

    protected function displayState($state)
    {
        print "\n\n";

        // Line mask
        $mask = "| %-20.20s| %5.5s | %5.5s ||";
        $manifestations = $this->service->getAllManifestations();
        foreach ($manifestations as $mid => $m) {
            $mask .= " %5.5s |";
        }
        $mask .= "| %5.5s |\n";

        // HEADER
        $line = ['', '', ''];
        foreach ($manifestations as $manifestation) {
            $line[] = $manifestation['name'];
        }
        $line[] = "";
        vprintf($mask, $line);

        $line = [
            'Participant',
            'IR',
            'RR',
        ];
        foreach ($manifestations as $manifestation) {
            $line[] = sprintf('[%d]', $manifestation['gauge_free']);
        }
        $line[] = "Pts";
        vprintf($mask, $line);
        $hline = array_fill(1, 4 + count($manifestations), '---------------------------');
        vprintf($mask, $hline);

        // BODY
        $participants = $this->service->getAllParticipants();
        foreach ($participants as $pid => $p) {
            $line = [
                $p['name'],
                $p['rank'],
                $state['participants'][$pid]['rr'],
            ];
            foreach ($manifestations as $mid => $manifestation) {
                $rank = '';
                $tsid = $manifestation['time_slot_id'];
                if (isset($p['timeSlots'][$tsid][$mid])) {
                    $rank = $p['timeSlots'][$tsid][$mid]['rank'];
                    if ($p['timeSlots'][$tsid][$mid]['human']) {
                        $rank = '[' . $rank . ']';
                    }
                    elseif (isset($state['participants'][$pid]['timeSlots'][$tsid]) && $state['participants'][$pid]['timeSlots'][$tsid] == $mid) {
                        $rank = '*' . $rank . '*';
                    }
                }
                $line[] = $rank;
            }
            $line[] = $state['participants'][$pid]['points'];
            vprintf($mask, $line);
        }

        printf("\n\nIteration #%d\n", $state['iteration']);
        printf("Points total: %f\n\n", $state['points']);
    }

}