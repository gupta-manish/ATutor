<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2008 by Greg Gay, Joel Kronenberg & Heidi Hazelton*/
/* Adaptive Technology Resource Centre / University of Toronto			*/
/* http://atutor.ca														*/
/*																		*/
/* This program is free software. You can redistribute it and/or		*/
/* modify it under the terms of the GNU General Public License			*/
/* as published by the Free Software Foundation.						*/
/************************************************************************/
if (!defined('AT_INCLUDE_PATH')) { exit; }
require(AT_INCLUDE_PATH.'lib/admin_categories.inc.php');

$cats	= array();
$cats[0] = _AT('cats_uncategorized');

$sql = "SELECT cat_id, cat_name FROM ".TABLE_PREFIX."course_cats";
$result = mysql_query($sql,$db);
while($row = mysql_fetch_array($result)) {
	$cats[$row['cat_id']] = $row['cat_name'];
}

if ($_GET['reset_filter']) { unset($_GET); }

$page_string = '';

if (isset($_GET['access']) && in_array($_GET['access'], array('public','private','protected'))) {
	$page_string .= SEP.'access='.$_GET['access'];
	$sql_access = "='{$_GET['access']}'";
} else {
	$sql_access     = '<>-1';
	$_GET['access'] = '';
}

if (isset($_GET['category']) && ($_GET['category'] > -1)) {
	$_GET['category'] = intval($_GET['category']);
	$page_string .= SEP.'category='.$_GET['category'];
	$sql_category = '='.$_GET['category'];
} else {
	$sql_category     = '<>-1';
	$_GET['category'] = -1; // all (because 0 = uncategorized)
}

if (isset($_GET['include']) && $_GET['include'] == 'one') {
	$checked_include_one = ' checked="checked"';
	$page_string .= SEP.'include=one';
} else {
	$_GET['include'] = 'all';
	$checked_include_all = ' checked="checked"';
	$page_string .= SEP.'include=all';
}

if (!empty($_GET['search'])) {
	$page_string .= SEP.'search='.urlencode($stripslashes($_GET['search']));
	$search = $addslashes($_GET['search']);
	$search = explode(' ', $search);

	if ($_GET['include'] == 'all') {
		$predicate = 'AND ';
	} else {
		$predicate = 'OR ';
	}

	$sql_search = '';
	foreach ($search as $term) {
		$term = trim($term);
		$term = str_replace(array('%','_'), array('\%', '\_'), $term);
		if ($term) {
			$term = '%'.$term.'%';
			$sql_search .= "((title LIKE '$term') OR (description LIKE '$term')) $predicate";
		}
	}
	$sql_search = '('.substr($sql_search, 0, -strlen($predicate)).')';
} else {
	$sql_search = '1';
}

$sql	= "SELECT COUNT(course_id) AS cnt FROM ".TABLE_PREFIX."courses WHERE access $sql_access AND cat_id $sql_category AND $sql_search AND hide=0";
$result = mysql_query($sql, $db);
$row = mysql_fetch_assoc($result);
$num_results = $row['cnt'];

$sql	= "SELECT * FROM ".TABLE_PREFIX."courses WHERE access $sql_access AND cat_id $sql_category AND $sql_search AND hide=0 ORDER BY title";
$courses_result = mysql_query($sql, $db);

// get the categories <select>, if there are any.
// we need ob_start/ob_clean, because select_categories() outputs directly.
// we do this so that if there are no categories, then the option doesn't appear.
ob_start();
select_categories(get_categories(), 0, $_GET['category'], false);
$categories_select = ob_get_contents();
ob_clean();

$has_categories = false;
if ($categories_select != '<option value="0"></option>') {
	$has_categories = true;
}

require(AT_INCLUDE_PATH.'header.inc.php');
?>
<fieldset class="group_form"><legend class="group_form"><?php echo _AT('filter'); ?></legend>
	<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<div class="input-form">
			<div class="row">
				<h3><?php echo _AT('results_found', $num_results); ?></h3>
			</div>
			<div class="row">
				<?php echo _AT('access'); ?><br />
				<input type="radio" name="access" value="private" id="s1" <?php if ($_GET['access'] == 'private') { echo 'checked="checked"'; } ?> /><label for="s1"><?php echo _AT('private'); ?></label> 

				<input type="radio" name="access" value="protected" id="s2" <?php if ($_GET['access'] == 'protected') { echo 'checked="checked"'; } ?> /><label for="s2"><?php echo _AT('protected'); ?></label>

				<input type="radio" name="access" value="public" id="s3" <?php if ($_GET['access'] == 'public') { echo 'checked="checked"'; } ?> /><label for="s3"><?php echo _AT('public'); ?></label>

				<input type="radio" name="access" value="" id="s" <?php if ($_GET['access'] == '') { echo 'checked="checked"'; } ?> /><label for="s"><?php echo _AT('all'); ?></label>
			</div>

		<?php if ($has_categories): ?>
			<div class="row">
				<label for="category"><?php echo _AT('category'); ?></label><br/>
				<select name="category" id="category">
					<option value="-1">- - - <?php echo _AT('cats_all'); ?> - - -</option>
					<option value="0" <?php if ($_GET['category'] == 0) { echo 'selected="selected"'; } ?>>- - - <?php echo _AT('cats_uncategorized'); ?> - - -</option>
					<?php echo $categories_select; ?>
				</select>
			</div>
		<?php endif; ?>

			<div class="row">
				<label for="search"><?php echo _AT('search'); ?> (<?php echo _AT('title').', '._AT('description'); ?>)</label><br />

				<input type="text" name="search" id="search" size="40" value="<?php echo htmlspecialchars($_GET['search']); ?>" />
				<br/>
				<?php echo _AT('search_match'); ?>:
				<input type="radio" name="include" value="all" id="match_all" <?php echo $checked_include_all; ?> /><label for="match_all"><?php echo _AT('search_all_words'); ?></label> 
				<input type="radio" name="include" value="one" id="match_one" <?php echo $checked_include_one; ?> /><label for="match_one"><?php echo _AT('search_any_word'); ?></label>
			</div>

			<div class="row buttons">
				<input type="submit" name="filter" value="<?php echo _AT('filter'); ?>"/>
				<input type="submit" name="reset_filter" value="<?php echo _AT('reset_filter'); ?>"/>
			</div>
		</div>
	</form>
</fieldset>
	<ul style=" padding: 0px; margin: 0px">
	<?php while ($row = mysql_fetch_assoc($courses_result)): ?>
		<li style="list-style: none; width: 80%">
			<dl class="browse-course">
				<dt>
					<?php if ($row['icon']) { // if a course icon is available, display it here.  
						$style_for_title = 'style="height: 79px;"'; 

						//Check if this is a custom icon, if so, use get_course_icon.php to get it
						//Otherwise, simply link it from the images/
						$path = AT_CONTENT_DIR.$row['course_id']."/custom_icons/";
		                if (file_exists($path.$row['icon'])) {
							if (defined('AT_FORCE_GET_FILE') && AT_FORCE_GET_FILE) {
								$course_icon = 'get_course_icon.php/?id='.$row['course_id'];
							} else {
								$course_icon = 'content/' . $row['course_id'] . '/';
							}
						} else {
							$course_icon = 'images/courses/'.$row['icon'];
						}
					?>
						<a href="<?php echo url_rewrite('bounce.php?course='.$row['course_id'], true); ?>"><img src="<?php echo $course_icon; ?>" class="headicon" alt="" /></a>	
					<?php } ?>
				</dt>
				<dd><h3 <?php echo $style_for_title; ?>><a href="<?php echo url_rewrite('bounce.php?course='.$row['course_id'], true); ?>"><?php echo $row['title']; ?></a></h3></dd>
				
			<?php if ($row['description']): ?>
				<dt><?php echo _AT('description'); ?></dt>
				<dd><?php echo nl2br($row['description']); ?>&nbsp;</dd>
			<?php endif; ?>

			<?php if ($has_categories): ?>
				<dt><?php echo _AT('category'); ?></dt>
				<dd><a href="<?php echo $_SERVER['PHP_SELF'].'?'.$page_string.SEP; ?>category=<?php echo $row['cat_id']; ?>"><?php echo $cats[$row['cat_id']]; ?></a>&nbsp;</dd>
			<?php endif; ?>
				
				<dt><?php echo _AT('instructor'); ?></dt>
				<dd><a href="<?php echo AT_BASE_HREF; ?>contact_instructor.php?id=<?php echo $row['course_id']; ?>"><?php echo get_display_name($row['member_id']); ?></a></dd>

				<dt><?php echo _AT('access'); ?></dt>
				<dd><?php echo _AT($row['access']); ?></dd>
			</dl>
		</li>
	<?php endwhile; ?>
	</ul>
<?php require(AT_INCLUDE_PATH.'footer.inc.php'); ?>