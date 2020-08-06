<?php

// Kickstart the framework
$f3=require('lib/base.php');
$f3->set('AUTOLOAD','helpers/');
$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<8.0)
	trigger_error('PCRE version is out of date');

// Load configuration
$f3->config('config.ini');

$db = DB::connect();
$data = json_decode(file_get_contents("php://input"));
header('Content-Type: application/json');
$f3->route('POST /addUser',function($f3) use ($db,$data) {
    $username = $data->username;
    $password = $data->password;
    $group_name = $data->group_name;
    $last_id = $db->exec("SELECT * from users ORDER BY creation_date DESC limit 1;")[0]['user_id'];
    while (Helper::exists($db,'users', ['user_id'=>$last_id]))
    {
        $last_id++;
    }
    $group_id = $db->exec("SELECT * from groups where group_name='$group_name' limit 1;")[0]['group_id'];
    if(!$group_id)
        return Helper::json_resp_error('این گروه وجود ندارد');
    if(!Helper::exists($db,'normal_users',['normal_username'=>$username])){
        $db->exec( "insert into users values($last_id,0,1.00,$group_id,CURRENT_TIMESTAMP)");
        $db->exec( "insert into normal_users values($last_id,'$username','$password')");
        return Helper::json_resp_success('با موفقیت انجام شد');
    }else{
        return Helper::json_resp_error('این نام کاربری قبلا استفاده شده است');
    }
});

$f3->route('POST /checkUser',function($f3) use ($db,$data) {
    $username = $data->username;
    $password = $data->password;
    if(Helper::exists($db,'normal_users',['normal_username'=>$username,'normal_password'=>$password])){
        return Helper::json_resp_success('این نام کاربری و رمز عبور وجود دارد');
    }else{
        return Helper::json_resp_error('این نام کاربری و رمز عبور وجود ندارد');
    }
});

$f3->route('POST /changeUserGroup',function($f3) use ($db,$data) {
    $username = $data->username;
    $password = $data->password;
    $group_name = $data->group_name;
    $user_id = Helper::getValue($db,'normal_users','user_id',['normal_username'=>$username,'normal_password'=>$password]);
    if($user_id){
        $group_id = $db->exec("SELECT * from groups where group_name='$group_name' limit 1;")[0]['group_id'];
        if(!$group_id)
            return Helper::json_resp_error('این گروه وجود ندارد');
        if(Helper::getValue($db,'users','group_id',['user_id'=>$user_id])==$group_id)
            return Helper::json_resp_error('قبلا این گروه برای این کاربر تنظیم شده است');
        $old_group_name = Helper::getValue($db,'user_audit_log','new_value',['object_id'=>$user_id,'attr_name'=>'group'],true,'change_time');
        $db->begin();
        $db->exec( "update users set group_id=$group_id where user_id=$user_id");
        $db->exec("select insert_user_audit_log(0,'t',$user_id,'group','$old_group_name','$group_name')");
        $db->commit();
        return Helper::json_resp_success('با موفقیت انجام شد');
    }else{
        return Helper::json_resp_error('این نام کاربری و رمز عبور وجود ندارد');
    }
});

$f3->route('POST /checkUsername',function() use($data,$db){
    $username = $data->username;
    if(Helper::exists($db,'normal_users',['normal_username'=>$username]))
        return Helper::json_resp_success('این نام کاربری وجود دارد');
    else
        return Helper::json_resp_error('این نام کاربری وجود ندارد');
});

$f3->route('POST /checkUsernameAndPassword',function() use($data,$db){
    $password = $data->password;
    $username = $data->username;
    if(Helper::exists($db,'normal_users',['normal_password'=>$password,'normal_username'=>$username]))
        return Helper::json_resp_success('این کاربر وجود دارد');
    else
        return Helper::json_resp_error('این کاربر وجود ندارد');
});

$f3->route('POST /changeAccountPassword',function() use($data,$db){
    $username = $data->username;
    $password = $data->password;
    if(!Helper::exists($db,'normal_users',['normal_username'=>$username]))
        return Helper::json_resp_error('این نام کاربری وجود ندارد');
    if(strlen($password)<=3)
        return Helper::json_resp_error('رمز عبور باید حداقل سه کاراکتر داشته باشد');
    $db->exec("update normal_users set normal_password='$password' where normal_username='$username'");
    return Helper::json_resp_success('با موفقیت انجام شد');
});

$f3->route('POST /getFirstLoginTime',function() use($data,$db){
    $username = $data->username;
    $password = $data->password;
    if(!Helper::exists($db,'normal_users',['normal_username'=>$username]))
        return Helper::json_resp_error('این نام کاربری وجود ندارد');
    $user_id = Helper::getValue($db,'normal_users','user_id',['normal_username'=>$username,'normal_password'=>$password]);
    $first_login = Helper::getValue($db,'user_attrs','attr_value',['user_id'=>$user_id,'attr_name'=>'first_login']);
    return Helper::json_resp_success_with_data('با موفقیت انجام شد',$first_login);
});

$f3->route('POST /getAllAccounts',function() use($data,$db){
    $users = $db->exec("SELECT
	nu.user_id,
	nu.normal_username \"username\",
	nu.normal_password \"password\",
	gr.group_name,
	ua.attr_value \"first_login\"
FROM
	normal_users AS nu
	INNER JOIN users AS u ON nu.user_id = u.user_id
	INNER JOIN groups AS gr ON u.group_id = gr.group_id
	full JOIN user_attrs AS ua ON ua.user_id=u.user_id
	where ua.attr_name='first_login' or not exists(select * from user_attrs where user_id=ua.user_id) ORDER BY u.creation_date DESC");
    return Helper::json_resp_success_with_data('لیست تمامی اکانت ها',$users);
});

$f3->route('POST /deleteAccount',function() use($data,$db){
    $username = $data->username;
    $password = $data->password;
    if(!Helper::exists($db,'normal_users',['normal_username'=>$username,'normal_password'=>$password]))
        return Helper::json_resp_error('این اکانت وجود ندارد');
    $user_id = Helper::getValue($db,'normal_users','user_id',['normal_username'=>$username,'normal_password'=>$password]);
    if(!Helper::exists($db,'users',['user_id'=>$user_id]))
        return Helper::json_resp_error('این اکانت وجود ندارد');
    Helper::deleteRecord($db,'normal_users',['user_id'=>$user_id]);
    Helper::deleteRecord($db,'user_attrs',['user_id'=>$user_id]);
    Helper::deleteRecord($db,'users',['user_id'=>$user_id]);
    return Helper::json_resp_success('با موفقیت انجام شد');
});

$f3->route('POST /getUserId',function() use($data,$db){
    $username = $data->username;
    $password = $data->password;
    if(!Helper::exists($db,'normal_users',['normal_username'=>$username,'normal_password'=>$password]))
        return Helper::json_resp_error('این اکانت وجود ندارد');
    $user_id = Helper::getValue($db,'normal_users','user_id',['normal_username'=>$username,'normal_password'=>$password]);
    return Helper::json_resp_success_with_data('با موفقیت انجام شد',$user_id);
});

\Middleware::instance()->before('GET|HEAD|POST|PUT|OPTIONS /*', function($f3) use($data){
    if($data->_token!=$f3->get('_token')){
        echo Helper::json_resp_error("شما به این روت دسترسی ندارید");
        exit(0);
    }
});
\Middleware::instance()->run();
$f3->run();
