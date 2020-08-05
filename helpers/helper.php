<?php 
class Helper{
    public static function exists($db,$table,$attrs){
        $conditions = implode(' and ', array_map(
            function ($v, $k) { return sprintf("%s='%s'", $k, $v); },
            $attrs,
            array_keys($attrs)
        ));
        $result = $db->exec("select
        case when exists (select true from $table where $conditions)
          then 'true'
          else 'false'
        end;");
        return $result[0]['case']=='true'?true:false;
    }
    public static function getValue($db,$table,$column,$attrs,$last=false,$last_column=null){
        $conditions = implode(' and ', array_map(
            function ($v, $k) { return sprintf("%s='%s'", $k, $v); },
            $attrs,
            array_keys($attrs)
        ));
        $sql = "select $column from $table where $conditions".($last?" ORDER BY $last_column DESC limit 1":'');
        $result = $db->exec($sql);
        return $result[0][$column];
    }
    public static function deleteRecord($db,$table,$attrs){
        $conditions = implode(' and ', array_map(
            function ($v, $k) { return sprintf("%s='%s'", $k, $v); },
            $attrs,
            array_keys($attrs)
        ));
        $sql = "delete from $table where $conditions";
        $result = $db->exec($sql);
        return $result;
    }
    public static function json_resp_success($message){
        echo json_encode([
            'status'=>true,
            'message'=>$message
        ]);
    }
    public static function json_resp_success_with_data($message,$data){
        echo json_encode([
            'status'=>true,
            'message'=>$message,
            'data'=>$data
        ]);
    }
    public static function json_resp_error($message){
        echo json_encode([
            'status'=>false,
            'message'=>$message
        ]);
    }
}