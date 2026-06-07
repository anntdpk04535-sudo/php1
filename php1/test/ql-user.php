<?php

session_start();

class qlUser {
public $users;
 public function __construct() {
    $this->users = array();
    if(isset($_SESSION['users'])) {
        $_SESSION['users'] = [
    [
    [
        'username' => 'admin',
        'password' => '123456',
        'name'     => 'admin'
    ],
    [
        'username' => 'tranvanb',
        'password' => 'mk456789',
        'name'     => 'Trần Văn B'
    ],
    [
        'username' => 'lethic',
        'password' => 'lethic_pwd',
        'name'     => 'Lê Thị C'
    ],
    [
        'username' => 'phamvand',
        'password' => 'dpham_2026',
        'name'     => 'Phạm Văn D'
    ],
    [
        'username' => 'hoangthie',
        'password' => 'hoangE!@#',
        'name'     => 'Hoàng Thị E'
    ]
]
];
    } else {
        $this->users = $_SESSION['users'];
    }
}
public function insert($user) {
    
foreach ($$this->users as $item) {
    
if($item['username'] == $user->username) {

echo "username tồn tại";

}

}

}

}

?>