<?php
$configs = parse_ini_file('.env', true);
$_REGULARS = [
    'PHONE' => '/^1[34578][0-9]{9}$/',
    'REALNAME' => "/^[\x{4e00}-\x{9fa5}]{2,5}(·)?[\x{4e00}-\x{9fa5}]*$/u",
    'NICKNAME' => '/^([\x{4e00}-\x{9fa5}]|[a-zA-Z])([\x{4e00}-\x{9fa5}\s]|[a-z0-9A-Z\s]){0,9}$/u',
    'ACCOUNT' => '/[a-zA-Z][a-zA-Z_0-9]{4,19}$/',
    'PASSWORD' => '/((?=.*\d)(?=.*\D)|(?=.*[a-zA-Z])(?=.*[^a-zA-Z]))^.{5,12}$/',
    'EMAIL' => '/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i',
    'IDCARD' => '/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/'
];
$newConfig = [
    'const' => [
        // 验证类
        'VALIDATE' => [
            'PHONE' => [
                'required',
                'regex:' . $_REGULARS['PASSWORD']
            ],
            'ACCOUNT' => [
                'required',
                'regex:' . $_REGULARS['ACCOUNT']
            ],
            'EMAIL' => [
                'required',
                'regex:' . $_REGULARS['EMAIL']
            ],
            'AREA_ID' => 'required|integer',
            'ZIPCODE' => 'required|integer',
            'GENDER' => 'required|integer|in 0,1,2,3',
            'REALNAME' => [
                'required',
                'regex:' . $_REGULARS['REALNAME']
            ],
            'NICK' => [
                'required',
                'regex:' . $_REGULARS['NICKNAME']
            ],
            
            'REGIST' => [
                0 => [
                    'account' => 'required',
                    'password' => [
                        'required',
                        'regex:' . $_REGULARS['PASSWORD']
                    ]
                ],
                1 => [
                    'account' => 'required|regex:/[a-zA-Z]{3,5}$/',
                    'password' => [
                        'required',
                        'regex:' . $_REGULARS['PASSWORD']
                    ]
                ],
                2 => [
                    'phone' => 'required|integer|in:0,1,2,3',
                    'channel_id' => 'required'
                ],
                3 => [
                    'phone' => 'required|integer|in:0,1,2,3',
                    'channel_id' => 'required'
                ]
            ],
            
            'LOGIN' => [
                'account' => 'required',
                'password' => [
                    'required',
                    'regex:' . $_REGULARS['PASSWORD']
                ],
                'type' => 'required'
            ],
            
            'CHANGEPWD' => [
                'oldpassword' => [
                    'required',
                    'regex:' . $_REGULARS['PASSWORD']
                ],
                'newpassword' => [
                    'required',
                    'regex:' . $_REGULARS['PASSWORD']
                ]
            ],
            
            'RESETPWD' => [
                'password' => [
                    'required',
                    'regex:' . $_REGULARS['PASSWORD']
                ],
                'phone' => [
                    'required',
                    'regex:' . $_REGULARS['PHONE']
                ],
                'code' => [
                    'required'
                ]
            ],
            
            'FIND_PASSWORD' => [
                'account' => 'required',
                'password' => [
                    'required',
                    'regex:' . $_REGULARS['PASSWORD']
                ]
            ],
            
            'SEND_CODE' => [
                'phone' => [
                    'required',
                    'regex:' . $_REGULARS['PHONE']
                ]
            ],
            'VERIFY_CODE' => [
                'phone' => [
                    'required',
                    'regex:' . $_REGULARS['PHONE']
                ],
                'code' => 'required'
            ]
        ]
    ] + $_REGULARS

];
return array_merge($configs, $newConfig);
