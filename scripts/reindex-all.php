<?php

require 'vendor/autoload.php';

$db = new PDO("mysql:host=localhost;dbname=joindin", "root", NULL);

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
            'analyzer' => 'trigrams'
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
            'analyzer' => 'trigrams'
        ],
        'description' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
        'speaker' => [
            'type' => 'string',
            'analyzer' => 'standard'
        ],
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
    ]
];


$triGramsFilter = [
    'type'      => 'ngram',
    'min_gram'  => 3,
    'max_gram'  => 3
];

$triGramsAnalyzer = [
    'type'      => 'custom',
    'tokenizer' => 'standard',
    'filter'    => [
        'lowercase',
        'trigrams_filter'    
    ]
]
;



$indexParams = ['index' => 'ji-search'];

$indexParams['body']['settings']['analysis']['filter']['trigrams_filter'] = $triGramsFilter;
$indexParams['body']['settings']['analysis']['analyzer']['trigrams'] = $triGramsAnalyzer;


$indexParams['body']['mappings']['events'] = $eventMapping;
$indexParams['body']['mappings']['talks'] = $talkMapping;
$indexParams['body']['mappings']['speakers'] = $speakerMapping;


// Gererate our indicies
$client->indices()->create($indexParams);

// Events
$event_sql = "  SELECT ID, url_friendly_name, event_name, event_loc, event_desc, event_stub, event_hashtag, event_start, event_icon 
                FROM events 
                WHERE active = 1 AND private = '0'";

$event_stmt = $db->prepare($event_sql);
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

$talk_sql = '   SELECT t.ID, t.talk_title, t.talk_desc, t.speaker  
                FROM talks t
                INNER JOIN events e
                    ON e.ID = t.event_id
                WHERE t.active = 1
                    AND t.event_id IS NOT NULL';
$talk_stmt = $db->prepare($talk_sql);
$talk_stmt->execute();

while($row = $talk_stmt->fetch(PDO::FETCH_ASSOC)) {
    $params = [];
    $params['body'] = [
        'title' => $row['talk_title'],
        'description' => $row['talk_desc'],
        'speaker' => $row['speaker'],
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

$speaker_sql = "SELECT DISTINCT speaker_name, speaker_id, 'linked' AS state 
                FROM talk_speaker 
                WHERE speaker_id IS NOT NULL 
                UNION ALL 
                SELECT speaker_name,  ID AS speaker_id, 'unlinked' AS state
                FROM talk_speaker 
                WHERE speaker_id IS NULL 
                ORDER BY speaker_name ASC";

$speaker_stmt = $db->prepare($speaker_sql);
$speaker_stmt->execute();

while($row = $speaker_stmt->fetch(PDO::FETCH_ASSOC)) {
    $params = [];
    $params['body'] = [
        'name' => $row['speaker_name']
    ];
    $params['index'] = 'ji-search';
    $params['type'] = 'speakers';
    $params['id'] = sprintf('%s%d', (($row['state'] == 'linked') ? '' : 'u'), $row['speaker_id']);
    
    echo "Trying to index speaker {$row['speaker_name']}....";
    $ret = $client->index($params);

    if(is_array($ret) && $ret['created'] === true) { 
        echo "Success.\n";
    } else {
        echo "Failed.\n";
        exit;
    }
}





