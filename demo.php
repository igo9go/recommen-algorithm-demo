<?php
// gcount，每个星球的人数统计 
// 格式为 ： 群组id - 用户人数

// mcount，每个用户加入的星球数统计
// 格式为 ：用户id - 加入星球数

// nmlist，每个用户加入的星球列表
// 格式为 ：用户id - 星球1，星球2，星球3，

// glist，每个星球的用户列表
// 格式为：星球id - 用户1，用户2，用户3，

set_time_limit(0);
error_reporting(E_ALL || ~E_NOTICE);

$now = time();

function getarr($fname)
{
    $fd = fopen($fname,"r");
    $seed = 0 ;
    while($str = trim(fgets($fd))){
        $arr = explode("_",$str);
        $key = $arr[0];
        $value = $arr[1];
        $listarr[$key] = $value;
        $seed++;
        if($seed > 2000) break;
    }
    return ($listarr);
}

function putskip($now,$key)
{
    $skip = time()-$now;
    echo "$key-$skip".PHP_EOL;
}

$garr=getarr("gcount");
$marr=getarr("mcount");
$mlarr=getarr("nmlist");
$glarr=getarr("glist");

 putskip($now,"START");

require 'conn.php';

$seed=0;$seed2=0;

foreach ($glarr as $gid=>$value)
{   
    $seed++;
    $seed2++;
    $v=$garr[$gid];
    if ($v<5 or $v>1000000) continue;

    $listarr=explode(",",$value);  //星球中的用户列表
    foreach($listarr as $key2=>$mid){
        if ($marr[$mid]==1 or $marr[$mid]>1000) continue; //过滤加入1或大于1000的人

        $gidlist=explode(",",$mlarr[$mid]); //每个人的星球列表
        foreach ($gidlist as $key3=>$togid) {
            if (trim($togid)=="") continue;
            if ($gid==$togid) continue;
            if ($garr[$togid]>1000000) continue;        //星球的人数大于1000000或小于2的过滤
            if ($garr[$togid]<2) continue;

            $sumlist[$gid][$togid]++;
            $tmpright=1/(sqrt($marr[$mid]+1)*sqrt($garr[$togid]+1));
            $tmpright2=1/(50+$garr[$togid]);
            $rlist[$gid][$togid]+=$tmpright;
            $nrlist[$gid][$togid]+=$tmpright2;
        }
    }
       
    $value=$rlist[$gid];
    $sql="delete from group_relate where fromgid=$gid";
    mysql_query($sql);
    $tmpseed=0;
    arsort($value);
    foreach ($value as $togid=>$rights) {
        $tmpseed++;
        $rights=$rlist[$gid][$togid];
        $rights2=$nrlist[$gid][$togid];
        if ($tmpseed==1) {
            $zright=$rights; //归一化
            $zright2=$rights2;
        }
        
        $tmpsum=$sumlist[$gid][$togid];
        if ($tmpseed>30) break;
        if ($tmpsum<2) continue;
        $rights=$rights/$zright*10000;
       
        $nrights=$rights2/$zright2*10000;
        $sql="insert into group_relate (fromgid,togid,sums,rights,nrights) values ('$gid','$togid','$tmpsum','$rights','$nrights')";
        mysql_query($sql);
       
        //echo "$sql\n";
       
        //echo mysql_error();
       
        //echo "$gid $togid $rights $tmpsum \n";
       
    }
       
    unset($rlist[$gid]);
    unset($nrlist[$gid]);
    unset($sumlist[$gid]);
       
    if ($seed==1000) { 
        putskip($now,$seed2); $seed=0;
    }
}


// 表结构为

// id 自加1主键
// fromgid 星球id 
// togid 相关星球id
// sums 共同用户数
// rights 相关权值 //生效的是这个，sums和nrights用于对比不同策略的效果
// nrights 对比权值


// 1、查询星球相关的
//  $str="select * from group_relate where fromgid=$gid order by rights desc";
// 这个不用解释


// 2、基于用户加入的星球，查询推荐的
// （$gids 是用户已加入的星球列表）
// $str="select distinct(togid) as tgid,sum(rights) as sumrights from group_relate where fromgid in($gids) and togid not in ($gids) group by tgid order by s    umrights desc limit 20";
?>
