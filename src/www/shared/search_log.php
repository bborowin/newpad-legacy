<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>Search history</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
  <script type="text/javascript" language="javascript" src="http://www.datatables.net/release-datatables/media/js/jquery.dataTables.js"></script>
  <style type="text/css" title="currentStyle">
    @import "http://www.datatables.net/release-datatables/media/css/demo_table.css";
  </style>

  <script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
      $('#example').dataTable( {
        "aaSorting": [[3,'desc']],
        "bProcessing": true,
        "bInfo": false,  
        "bFilter": false,
        "bLengthChange": false,
        "bAutoWidth": true,
        "aoColumns" : [
            { sWidth: '3em', sClass: "center" },
            { sWidth: '3em', sClass: "center" },
            { sWidth: '14em', sClass: "center" },
            { sWidth: '14em', sClass: "center" }
        ],
        "iDisplayLength": 25,
        "sAjaxSource": 'search_log_data.php?city=<? echo $_GET["city"]; ?>'
      } );
    } );
  </script> 
</head>
<body>
<p>
Search history for <? echo ucfirst($_GET["city"]); ?>
</p>
<p>
<?
include 'qualifier.php';
include 'sql.php';

$q = new Qualifier(false);
$city = $q->Get("city");
$s = new Sql($city);
$query = "SELECT count(*) FROM (SELECT userId, count(*) FROM $city.Searches WHERE userId > 2 GROUP BY userId HAVING count(*) > 1) a;";
$count = $s->GetScalar($query);
echo $count . ' returning users</br>';
?>
</p>
<div style="position:absolute; left:1em; margin-top:1em;">
  <table cellpadding="0" cellspacing="0" border="0" class="display" id="example">
    <thead>
      <tr>
        <th>user</th>
        <th>searches</th>
        <th>first visit</th>
        <th>last visit</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
</div>
</body>
</html>

