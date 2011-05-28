<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <link href='http://fonts.googleapis.com/css?family=Ubuntu&subset=cyrillic,latin' rel='stylesheet' type='text/css' />
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
  <script type="text/javascript" language="javascript" src="http://www.datatables.net/release-datatables/media/js/jquery.dataTables.js"></script>
  <title>Newpad user statistics</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <link href="http://www.datatables.net/release-datatables/media/css/demo_table.css" rel='stylesheet' type='text/css' />
  <style type="text/css" >
    body {
      overflow-x:hidden;
      font-family : 'Ubuntu', sans-serif;
      margin:0px;
      padding:1em;
    }
  </style>
</head>  
<body>

<script type="text/javascript" charset="utf-8">
  $(document).ready(function() {
    $('#example').dataTable( {
      "aaSorting": [[0,'asc']],
      "bProcessing": true,
      "bInfo": false,  
      "bFilter": false,
      "bLengthChange": false,
      "bAutoWidth": true,
      "aoColumns" : [
          { sWidth: '10em', sClass: "center" },
          { sWidth: '4em', sClass: "center" },
          { sWidth: '4em', sClass: "center" },
          { sWidth: '4em', sClass: "center" },
          { sWidth: '4em', sClass: "center" },
          { sWidth: '4em', sClass: "center" }
      ],
      "iDisplayLength": 25
    } );
  } );
</script> 

<div style="position:absolute; left:1em; margin-top:1em;">
  <table cellpadding="0" cellspacing="0" border="0" class="display" id="example">
    <thead>
      <tr>
        <th>city</th>
        <th>visitors</th>
        <th>searched</th>
        <th>returned</th>
        <th>bounce</th>
        <th>retention</th>
      </tr>
    </thead>
    <tbody>

<?php
include 'sql.php';

$s = new Sql("main");

$cities = $s->GetList("SELECT name FROM main.Cities WHERE name NOT IN ('newyork') ORDER BY name;");

//$vt = 0;
$tt = 0;
$st = 0;
$ut = 0;
foreach($cities as $city) 
{
  $query = "select count(*) from (SELECT userId from $city.Searches group by userId) a;";
  $tried = $s->GetScalar($query);
  $tt += $tried;
  
  $query = "select count(*) from (SELECT userId from $city.Flags group by userId) a;";
  $searched = $s->GetScalar($query);
  $st += $searched;
  
  $query = "select count(*) from (select userId, UserVisits(userId, 10) as visits, max(ts) from $city.Flags group by userId having visits > 1 order by userId) bz;";
  $users = $s->GetScalar($query);
  $ut += $users;
  
  if($tried > 0) echo "<tr><td>" . ucfirst($city) . "</td><td>$tried</td><td>$searched</td><td>$users</td><td>" . number_format(100-100*$searched/$tried, 2, '.', '') . "%</td><td>" . number_format(100*$users/$tried, 2, '.', '') . "%</td></tr>";
}

if($tt > 0) echo "<tr><td>~ Totals:</td><td>$tt</td><td>$st</td><td>$ut</td><td>" . number_format(100-100*$st/$tt, 2, '.', '') . "%</td><td>" . number_format(100*$ut/$tt, 2, '.', '') . "%</td></tr>";
?>

    </tbody>
  </table>
</div>
</body>
</html>
