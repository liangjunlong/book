<?php
/**
 * 生成mysql数据字典
 */
//配置数据库
$dbserver = "127.0.0.1";
$dbusername = "root";
$dbpassword = "root";
$database = "admin_p";
$db_press = 'mdhr_';
$db_port = '3306';
//其他配置
$title = '数据字典';
$mysql_conn = @mysqli_connect("$dbserver", "$dbusername", "$dbpassword", $database, $db_port) or die("Mysql connect is error.");

mysqli_set_charset($mysql_conn, 'utf8');
$table_result = mysqli_query($mysql_conn, 'show tables');
//取得所有的表名
while ($row = mysqli_fetch_array($table_result)) {
    if ($db_press) {
        $len = strlen($db_press);

        if (substr($row[0], 0, $len) == $db_press) {
            $tables[]['TABLE_NAME'] = $row[0];
        }
    } else {
        $tables[]['TABLE_NAME'] = $row[0];
    }

}

//循环取得所有表的备注及表中列消息
foreach ($tables AS $k => $v) {
    $sql = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.TABLES ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$database}'";
    $table_result = mysqli_query($mysql_conn, $sql);
    while ($t = mysqli_fetch_array($table_result)) {
        $tables[$k]['TABLE_COMMENT'] = $t['TABLE_COMMENT'];
    }
    $sql = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$database}'";
    $fields = array();
    $field_result = mysqli_query($mysql_conn, $sql);
    while ($t = mysqli_fetch_array($field_result)) {
        $fields[] = $t;
    }
    $tables[$k]['COLUMN'] = $fields;
}
mysqli_close($mysql_conn);
$html = '';
//循环所有表
foreach ($tables AS $k => $v) {
    //$html .= '<p><h2>'. $v['TABLE_COMMENT'] . ' </h2>';
    $html .= '<table border="1" cellspacing="0" cellpadding="0" align="center">';
    $html .= '<caption>' . $v['TABLE_NAME'] . ' ' . $v['TABLE_COMMENT'] . '</caption>';
    $html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th>
   <th>允许非空</th>
   <th>自动递增</th><th>备注</th></tr>';
    $html .= '';
    foreach ($v['COLUMN'] AS $f) {
        $html .= '<tr><td>' . $f['COLUMN_NAME'] . '</td>';
        $html .= '<td>' . $f['COLUMN_TYPE'] . '</td>';
        $html .= '<td> ' . $f['COLUMN_DEFAULT'] . '</td>';
        $html .= '<td> ' . $f['IS_NULLABLE'] . '</td>';
        $html .= '<td>' . ($f['EXTRA'] == 'auto_increment' ? '是' : ' ') . '</td>';
        $html .= '<td> ' . $f['COLUMN_COMMENT'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></p>';
}
//输出
echo '<html>
<head>
<meta http-equiv="Content-Type" c />
<title>' . $title . '</title>
<style>
body,td,th {font-family:"宋体"; font-size:12px;}
table{border-collapse:collapse;border:1px solid #CCC;background:#efefef;}
table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
table th{text-align:left; font-weight:bold;height:26px; line-height:26px; font-size:12px; border:1px solid #CCC;}
table td{height:20px; font-size:12px; border:1px solid #CCC;background-color:#fff;}
.c1{ width: 120px;}
.c2{ width: 120px;}
.c3{ width: 70px;}
.c4{ width: 80px;}
.c5{ width: 80px;}
.c6{ width: 270px;}
</style>
</head>
<body>';
echo '<h1>' . $title . '</h1>';
echo $html;
echo '</body></html>';
?>
