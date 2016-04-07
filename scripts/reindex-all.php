<?php

define('BASEPATH', '');

require 'vendor/autoload.php';
require 'src/database.php';


// Set up the DB connection using JI standard config
$ji_db = new PDO(
    'mysql:host=' . $db['default']['hostname'] . ';dbname=' . $db['default']['database'],
    $db['default']['username'],
    $db['default']['password']
);

// Set the correct charset for this connection
$ji_db->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
$ji_db->query('SET CHARACTER SET utf8');

$client = new Elasticsearch\Client();


try {
    $client->indices()->delete(['index' => 'ji-search']);
} catch(Exception $e) {
    echo "Failed to drop search index\n";
//    exit;
}

$eventMapping = [
    '_source' => [
        'enabled' => true
    ],
    'properties' => [
        'id' => [
            'type' => 'integer',
            'store' => 'yes',
            'index' => 'no'
        ],
        'image' => [
            'type' => 'string',
            'store' => 'yes',
            'index' => 'no',
        ],
        'title' => [
            'type' => 'string',
            'analyzer' => 'quadgrams'
        ],
        'location' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'description' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'stub' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'hashtag' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'start' => [
            'type' => 'date',
        ]
    ]
];

$talkMapping = [
    '_source' => [
        'enabled' => true
    ],
    'properties' => [
        'id' => [
            'type' => 'integer',
            'store' => 'yes',
            'index' => 'no'
        ],
        'title' => [
            'type' => 'string',
            'analyzer' => 'quadgrams'
        ],
        'description' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'speaker' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'start' => [
            'type' => 'date'
        ]
    ]
];

$speakerMapping = [
    '_source' => [
        'enabled' => true
    ],
    'properties' => [
        'id' => [
            'type' => 'integer',
            'store' => 'yes',
            'index' => 'no'
        ],
        'name' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'start' => [
            'type' => 'date'
        ]
    ]
];


$quadGramsFilter = [
    'type'      => 'ngram',
    'min_gram'  => 3,
    'max_gram'  => 10
];

$quadGramsAnalyzer = [
    'type'      => 'custom',
    'tokenizer' => 'standard',
    'filter'    => [
        'lowercase',
        'quadgrams_filter'    
    ]
]
;



$indexParams = ['index' => 'ji-search'];

$indexParams['body']['settings']['analysis']['filter']['quadgrams_filter'] = $quadGramsFilter;
$indexParams['body']['settings']['analysis']['analyzer']['quadgrams'] = $quadGramsAnalyzer;


$indexParams['body']['mappings']['events'] = $eventMapping;
$indexParams['body']['mappings']['talks'] = $talkMapping;
$indexParams['body']['mappings']['speakers'] = $speakerMapping;


// Gererate our indicies
$client->indices()->create($indexParams);

// Events
$event_sql = "  SELECT ID, url_friendly_name, event_name, event_loc, event_desc, event_stub, event_hashtag, event_start, event_icon 
                FROM events 
                WHERE active = 1 AND private = '0'";

$event_stmt = $ji_db->prepare($event_sql);
$event_stmt->execute();
while($row = $event_stmt->fetch(PDO::FETCH_ASSOC)) {
    $params = [];
    $params['body']  = [
        'title' => $row['event_name'],
        'location' => $row['event_loc'],
        'description' => $row['event_desc'],
        'stub' => $row['event_stub'],
        'hashtag' => $row['event_hashtag'],
        'start' => $row['event_start'],
        'image' => $row['event_icon']
    ];

    $params['index'] = 'ji-search';
    $params['type']  = 'events';
    $params['id']    = $row['ID'];

    echo "Trying to index event {$row['event_name']}....";
    $ret = $client->index($params);

    if(is_array($ret) && $ret['created'] === true) { 
        echo "Success.\n";
    } else {
        echo "Failed.\n";
        exit;
    }

}

$talk_sql = '   SELECT t.ID, t.talk_title, t.talk_desc, GROUP_CONCAT(s.speaker_name) AS speaker, COALESCE(t.date_given, e.event_start) AS talk_date
                FROM talks t
                INNER JOIN events e
                    ON e.ID = t.event_id
                LEFT JOIN talk_speaker s
                    ON s.talk_id = t.ID
                WHERE t.active = 1
                    AND t.event_id IS NOT NULL
                GROUP BY t.ID';
$talk_stmt = $ji_db->prepare($talk_sql);
$talk_stmt->execute();

while($row = $talk_stmt->fetch(PDO::FETCH_ASSOC)) {
    $params = [];
    $params['body'] = [
        'title' => $row['talk_title'],
        'description' => $row['talk_desc'],
        'speaker' => $row['speaker'],
        'start' => $row['talk_date']
    ];

    $params['index'] = 'ji-search';
    $params['type'] = 'talks';
    $params['id'] = $row['ID'];

    echo "Trying to index talk {$params['body']['title']}....";
    $ret = $client->index($params);

    if(is_array($ret) && $ret['created'] === true) { 
        echo "Success.\n";
    } else {
        echo "Failed.\n";
        exit;
    }
}

$speaker_sql = "SELECT DISTINCT speaker_name, speaker_id 
                FROM talk_speaker 
                WHERE speaker_id IS NOT NULL 
                ORDER BY speaker_name ASC";

$speaker_stmt = $ji_db->prepare($speaker_sql);
$speaker_stmt->execute();

while($row = $speaker_stmt->fetch(PDO::FETCH_ASSOC)) {
    $params = [];
    $params['body'] = [
        'name' => $row['speaker_name'],
        'start' => null
    ];
    $params['index'] = 'ji-search';
    $params['type'] = 'speakers';
    $params['id'] = $row['speaker_id'];
    
    echo "Trying to index speaker {$row['speaker_name']}....";
    $ret = $client->index($params);

    if(is_array($ret) && $ret['created'] === true) { 
        echo "Success.\n";
    } else {
        echo "Failed.\n";
        exit;
    }
}





