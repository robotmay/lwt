<?php

/**************************************************************
"Learning with Texts" (LWT) is released into the Public Domain.
This applies worldwide.
In case this is not legally possible, any entity is granted the
right to use this work for any purpose, without any conditions, 
unless such conditions are required by law.

Developed by J. Pierre in 2011.
***************************************************************/

/**************************************************************
Call: edit_texttags.php?....
      ... markaction=[opcode] ... do actions on marked text tags
      ... allaction=[opcode] ... do actions on all text tags
      ... del=[wordid] ... do delete
      ... op=Save ... do insert new 
      ... op=Change ... do update
      ... new=1 ... display new text tag screen 
      ... chg=[wordid] ... display edit screen 
      ... sort=[sortcode] ... sort 
      ... page=[pageno] ... page  
      ... query=[tagtextfilter] ... tag text filter    
Manage tags
***************************************************************/

include "connect.inc.php";
include "settings.inc.php";
include "utilities.inc.php";

$currentsort = processDBParam("sort",'currenttexttagsort','1',1);

$currentpage = processSessParam("page","currenttexttagpage",'1',1);
$currentquery = processSessParam("query","currenttexttagquery",'',0);

$wh_query = convert_string_to_sqlsyntax(str_replace("*","%",$currentquery));
$wh_query = ($currentquery != '') ? (' and (T2Text like ' . $wh_query . ' or T2Comment like ' . $wh_query . ')') : '';

pagestart('My Text Tags',true);

$message = '';

// MARK ACTIONS

if (isset($_REQUEST['markaction'])) {
	$markaction = $_REQUEST['markaction'];
	$message = "Multiple Actions: 0";
	if (isset($_REQUEST['marked'])) {
		if (is_array($_REQUEST['marked'])) {
			$l = count($_REQUEST['marked']);
			if ($l > 0 ) {
				$list = "(" . $_REQUEST['marked'][0];
				for ($i=1; $i<$l; $i++) $list .= "," . $_REQUEST['marked'][$i];
				$list .= ")";
				if ($markaction == 'del') {
					$message = runsql('delete from tags2 where T2ID in ' . $list, "Deleted");
					runsql("DELETE texttags FROM (texttags LEFT JOIN tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL",'');
					runsql("DELETE archtexttags FROM (archtexttags LEFT JOIN tags2 on AgT2ID = T2ID) WHERE T2ID IS NULL",'');
					adjust_autoincr('tags2','T2ID');
				}
			}
		}
	}
}


// ALL ACTIONS 

if (isset($_REQUEST['allaction'])) {
	$allaction = $_REQUEST['allaction'];
	if ($allaction == 'delall') {
		$message = runsql('delete from tags2 where (1=1) ' . $wh_query, "Deleted");
		runsql("DELETE texttags FROM (texttags LEFT JOIN tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL",'');
		runsql("DELETE archtexttags FROM (archtexttags LEFT JOIN tags2 on AgT2ID = T2ID) WHERE T2ID IS NULL",'');
		adjust_autoincr('tags2','T2ID');
	}
}

// DEL

elseif (isset($_REQUEST['del'])) {
	$message = runsql('delete from tags2 where T2ID = ' . $_REQUEST['del'], "Deleted");
	runsql("DELETE texttags FROM (texttags LEFT JOIN tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL",'');
	runsql("DELETE archtexttags FROM (archtexttags LEFT JOIN tags2 on AgT2ID = T2ID) WHERE T2ID IS NULL",'');
	adjust_autoincr('tags2','T2ID');
}

// INS/UPD

elseif (isset($_REQUEST['op'])) {

	// INSERT
	
	if ($_REQUEST['op'] == 'Save') {
	
		$message = runsql('insert into tags2 (T2Text, T2Comment) values(' . 
			convert_string_to_sqlsyntax($_REQUEST["T2Text"]) . ', ' .
			convert_string_to_sqlsyntax_nonull($_REQUEST["T2Comment"]) . ')', "Saved");

	}	
	
	// UPDATE
	
	elseif ($_REQUEST['op'] == 'Change') {

		$message = runsql('update tags2 set T2Text = ' . 
			convert_string_to_sqlsyntax($_REQUEST["T2Text"]) . ', T2Comment = ' . 
			convert_string_to_sqlsyntax_nonull($_REQUEST["T2Comment"]) . ' where T2ID = ' . $_REQUEST["T2ID"], "Updated");

	}

}

// NEW

if (isset($_REQUEST['new'])) {
	
	?>

	<h4>New Tag</h4>
	<form name="newtag" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<table class="tab3" cellspacing="0" cellpadding="5">
	<tr>
	<td class="td1 right">Tag:</td>
	<td class="td1"><input class="notempty setfocus noblanksnocomma" type="text" name="T2Text" data_info="Tag" value="" maxlength="20" size="20" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Comment:</td>
	<td class="td1"><textarea class="textarea-noreturn checklength" data_maxlength="200" data_info="Comment" name="T2Comment" cols="40" rows="3"></textarea></td>
	</tr>
	<tr>
	<td class="td1 right" colspan="2">
	<input type="button" value="Cancel" onclick="location.href='edit_texttags.php';" /> 
	<input type="submit" name="op" value="Save" /></td>
	</tr>
	</table>
	</form>
	
	<?php
	
}

// CHG

elseif (isset($_REQUEST['chg'])) {
	
	$sql = 'select * from tags2 where T2ID = ' . $_REQUEST['chg'];
	$res = mysql_query($sql);		
	if ($res == FALSE) die("Invalid Query: $sql");
	if ($record = mysql_fetch_assoc($res)) {
?>
		<h4>Edit Tag</h4>
		<form name="edittag" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $_REQUEST['chg']; ?>" method="post">
		<input type="hidden" name="T2ID" value="<?php echo $record['T2ID']; ?>" />
		<table class="tab3" cellspacing="0" cellpadding="5">
		<tr>
		<td class="td1 right">Tag:</td>
		<td class="td1"><input data_info="Tag" class="notempty setfocus noblanksnocomma" type="text" name="T2Text" value="<?php echo tohtml($record['T2Text']); ?>" maxlength="20" size="20" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Comment:</td>
		<td class="td1"><textarea class="textarea-noreturn checklength" data_maxlength="200" data_info="Comment" name="T2Comment" cols="40" rows="3"><?php echo tohtml($record['T2Comment']); ?></textarea></td>
		</tr>
		<tr>
		<td class="td1 right" colspan="2">
		<input type="button" value="Cancel" onclick="location.href='edit_texttags.php#rec<?php echo $_REQUEST['chg']; ?>';" /> 
		<input type="submit" name="op" value="Change" /></td>
		</tr>
		</table>
		</form>
<?php
	}
	mysql_free_result($res);
}

// DISPLAY

else {
	
	echo error_message_with_hide($message,0);
	
	get_texttags(1);   // refresh tags cache

	$sql = 'select count(T2ID) as value from tags2 where (1=1) ' . $wh_query;
	$recno = get_first_value($sql);
	if ($debug) echo $sql . ' ===&gt; ' . $recno;
	
	$maxperpage = getSettingWithDefault('set-tags-per-page');

	$pages = $recno == 0 ? 0 : (intval(($recno-1) / $maxperpage) + 1);
	
	if ($currentpage < 1) $currentpage = 1;
	if ($currentpage > $pages) $currentpage = $pages;
	$limit = 'LIMIT ' . (($currentpage-1) * $maxperpage) . ',' . $maxperpage;

	$sorts = array('T2Text','T2Comment','T2ID desc');
	$lsorts = count($sorts);
	if ($currentsort < 1) $currentsort = 1;
	if ($currentsort > $lsorts) $currentsort = $lsorts;
	
?>
<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1"><img src="icn/plus-button.png" title="New" alt="New" /> New Text Tag ...</a></p>

<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="icn/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="{location.href='edit_texttags.php?page=1&amp;query=';}" /></th>
</tr>
<tr>
<td class="td1 center" colspan="4">
Tag Text or Comment:
<input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value; location.href='edit_texttags.php?page=1&amp;query=' + val;}" />&nbsp;
<input type="button" value="Clear" onclick="{location.href='edit_texttags.php?page=1&amp;query=';}" />
</td>
</tr>
<?php if($recno > 0) { ?>
<tr>
<th class="th1" colspan="1" nowrap="nowrap">
<?php echo $recno; ?> Tag<?php echo ($recno==1?'':'s'); ?>
</th><th class="th1" colspan="2" nowrap="nowrap">
<?php makePager ($currentpage, $pages, 'edit_texttags.php', 'form1'); ?>
</th><th class="th1" nowrap="nowrap">
Sort Order:
<select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='edit_texttags.php?page=1&amp;sort=' + val;}"><?php echo get_tagsort_selectoptions($currentsort); ?></select>
</th></tr>
<?php } ?>
</table>
</form>

<?php
if ($recno==0) {
?>
<p>No tags found.</p>
<?php
} else {
?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab1" cellspacing="0" cellpadding="5">
<tr><th class="th1 center" colspan="2">
Multi Actions <img src="icn/lightning.png" title="Multi Actions" alt="Multi Actions" />
</th></tr>
<tr><td class="td1 center" colspan="2">
<b>ALL</b> <?php echo ($recno == 1 ? '1 Tag' : $recno . ' Tags'); ?>:&nbsp; 
<select name="allaction" onchange="allActionGo(document.form2, document.form2.allaction,<?php echo $recno; ?>);"><?php echo get_alltagsactions_selectoptions(); ?></select>
</td></tr>
<tr><td class="td1 center">
<input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
<input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
</td>
<td class="td1 center">Marked Tags:&nbsp; 
<select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo get_multipletagsactions_selectoptions(); ?></select>
</td></tr></table>

<table class="sortable tab1"  cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 sorttable_nosort">Actions</th>
<th class="th1 clickable">Tag Text</th>
<th class="th1 clickable">Tag Comment</th>
<th class="th1 clickable">Texts<br />With Tag</th>
<th class="th1 clickable">Arch.Texts<br />With Tag</th>
</tr>

<?php

$sql = 'select T2ID, T2Text, T2Comment from tags2 where (1=1) ' . $wh_query . ' order by ' . $sorts[$currentsort-1] . ' ' . $limit;
if ($debug) echo $sql;
$res = mysql_query($sql);		
if ($res == FALSE) die("Invalid Query: $sql");
while ($record = mysql_fetch_assoc($res)) {
	$c = get_first_value('select count(*) as value from texttags where TtT2ID=' . $record['T2ID']);
	$ca = get_first_value('select count(*) as value from archtexttags where AgT2ID=' . $record['T2ID']);
	echo '<tr>';
	echo '<td class="td1 center"><a name="rec' . $record['T2ID'] . '"><input name="marked[]" type="checkbox" class="markcheck" value="' . $record['T2ID'] . '" ' . checkTest($record['T2ID'], 'marked') . ' /></a></td>';
	echo '<td class="td1 center" nowrap="nowrap">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['T2ID'] . '"><img src="icn/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?del=' . $record['T2ID'] . '"><img src="icn/minus-button.png" title="Delete" alt="Delete" /></a>&nbsp;</td>';
	echo '<td class="td1 center">' . tohtml($record['T2Text']) . '</td>';
	echo '<td class="td1 center">' . tohtml($record['T2Comment']) . '</td>';
	echo '<td class="td1 center">' . ($c > 0 ? '<a href="edit_texts.php?page=1&amp;query=&amp;tag12=0&amp;tag2=&amp;tag1=' . $record['T2ID'] . '">' . $c . '</a>' : '0' ) . '</td>';
	echo '<td class="td1 center">' . ($ca > 0 ? '<a href="edit_archivedtexts.php?page=1&amp;query=&amp;tag12=0&amp;tag2=&amp;tag1=' . $record['T2ID'] . '">' . $ca . '</a>' : '0' ) . '</td>';
	echo '</tr>';
}
mysql_free_result($res);

?>
</table>
</form>

<?php if( $pages > 1) { ?>
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" nowrap="nowrap">
<?php echo $recno; ?> Tag<?php echo ($recno==1?'':'s'); ?>
</th><th class="th1" nowrap="nowrap">
<?php makePager ($currentpage, $pages, 'edit_texttags.php', 'form1'); ?>
</th></tr></table>
<?php } ?>

<?php
}

}

pageend();

?>