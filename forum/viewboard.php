<?php

include("../_setup.php");
include("../themes/crak/_header.php");

?>

<div class="container">
				<div class="row">
					<div class="title_wrapper">
						<div class="span6">
							<h1>Forums</h1>
						</div>
						<div class="breadcrumbs"><strong><a href="#"></a></strong></div>
					</div>
				</div>
			</div>

<div class="page normal-page container">
	<div class="row">
		<div class="span12">
			<div id="bbpress-forums">
				
				
					
<?php

$consoleObj = new ConsoleOption($mysqli);
$boardObj = new ForumBoard($mysqli);
$member = new Member($mysqli);
$postMemberObj = new Member($mysqli);

$intPostTopicCID = $consoleObj->findConsoleIDByName("Post Topic");

$categoryObj = new BasicOrder($mysqli, "forum_category", "forumcategory_id");
$categoryObj->set_assocTableName("forum_board");
$categoryObj->set_assocTableKey("forumboard_id");


if(!$boardObj->select($_GET['bID'])) {
	echo "
		<script type='text/javascript'>window.location = 'index.php';</script>
	";
	exit();
}

$boardInfo = $boardObj->get_info_filtered();


// Check Private Forum

/*if($websiteInfo['privateforum'] == 1 && !constant("LOGGED_IN")) {
	die("<script type='text/javascript'>window.location = '".$MAIN_ROOT."login.php';</script>");
}*/

$memberInfo = array();

$LOGGED_IN = false;
$NUM_PER_PAGE = 25;
if($member->select($_SESSION['btUsername']) && $member->authorizeLogin($_SESSION['btPassword'])) {
	$memberInfo = $member->get_info_filtered();
	$LOGGED_IN = true;
	$NUM_PER_PAGE = $memberInfo['topicsperpage'];
}

if($NUM_PER_PAGE == 0) {
	$NUM_PER_PAGE = 25;
}

if(!$boardObj->memberHasAccess($memberInfo)) {
	echo "
	<script type='text/javascript'>window.location = 'index.php';</script>
	";
	exit();
}

$arrTopics = $boardObj->getForumTopics();

if(!isset($_GET['pID']) || !is_numeric($_GET['pID'])) {
	$intOffset = 0;
	$_GET['pID'] = 1;
}
else {
	$intOffset = $NUM_PER_PAGE*($_GET['pID']-1);
}

$blnPageSelect = false;

// Count Pages
$NUM_OF_PAGES = ceil(count($arrTopics)/$NUM_PER_PAGE);

if($NUM_OF_PAGES == 0) {
	$NUM_OF_PAGES = 1;	
}

if($_GET['pID'] > $NUM_OF_PAGES) {

	echo "
	<script type='text/javascript'>window.location = 'viewboard.php?bID=".$_GET['bID']."';</script>
	";
	exit();
}

// Check for Next button
$dispNextPage = "";
if($_GET['pID'] < $NUM_OF_PAGES) {
	$dispNextPage = "<span style='padding-left: 10px'><b><a href='viewboard.php?bID=".$_GET['bID']."&pID=".($_GET['pID']+1)."'>Next</a> &raquo;</b></span>";
	$blnPageSelect = true;
}

// Check for Previous button
$dispPreviousPage = "";
if(($_GET['pID']-1) > 0) {
	$dispPreviousPage = "<b>&laquo; <a href='viewboard.php?bID=".$_GET['bID']."&pID=".($_GET['pID']-1)."'>Previous</a></b>";
	$blnPageSelect = true;
}


for($i=1; $i<=$NUM_OF_PAGES; $i++) {
	$selectPage = "";
	if($i == $_GET['pID']) {
		$selectPage = " selected";	
	}
	$pageoptions .= "<option value='".$i."'".$selectPage.">".$i."</option>";
}

// Subforums

$subForumObj = new ForumBoard($mysqli);
$arrSubForums = $boardObj->getSubForums();
$dispSubForums = "";
foreach($arrSubForums as $boardID) {
	
	$subForumObj->select($boardID);
	
	if($subForumObj->memberHasAccess($memberInfo)) {
		$subForumInfo = $subForumObj->get_info_filtered();
		$arrForumTopics = $subForumObj->getForumTopics();
		
		$newTopicBG = "";
		$dispNewTopicIMG = "";
		
		if($LOGGED_IN && $subForumObj->hasNewTopics($memberInfo['member_id'])) {
			$dispNewTopicIMG = " <img style='margin-left: 5px' src='".$MAIN_ROOT."themes/".$THEME."/images/forum-new.png' title='New Posts!'>";
			$newTopicBG = " boardNewPostBG";
		}
		
		// Get Last Post Display Info
		if(count($arrForumTopics) > 0) {
			$subForumObj->objPost->select($arrForumTopics[0]);
			$firstPostInfo = $subForumObj->objPost->get_info_filtered();
			
			$subForumObj->objTopic->select($firstPostInfo['forumtopic_id']);
			$lastPostID = $subForumObj->objTopic->get_info("lastpost_id");
			
			$subForumObj->objPost->select($lastPostID);
			$lastPostInfo = $subForumObj->objPost->get_info_filtered();
			
			$postMemberObj->select($lastPostInfo['member_id']);
			
			$dispLastPost = "<div class='boardLastPostTitle'><a href='viewtopic.php?tID=".$firstPostInfo['forumtopic_id']."#".$lastPostID."' title='".$firstPostInfo['title']."'>".$firstPostInfo['title']."</a></div>by ".$postMemberObj->getMemberLink()."<br>".getPreciseTime($lastPostInfo['dateposted']);
		}
		else {
			$dispLastPost = "<div style='text-align: center'>No Posts</div>";	
		}
		
		$dispTopicCount = $subForumObj->countTopics();
		$dispPostCount = $subForumObj->countPosts();
		
		$arrDispMoreSubForums = array();
		$arrMoreSubForums = $subForumObj->getSubForums();
	
		foreach($arrMoreSubForums as $value) {
			$subForumObj->select($value);
			$subForumInfo = $subForumObj->get_info_filtered();
			
			$arrDispMoreSubForums[] = "<a href='".$MAIN_ROOT."forum/viewboard.php?bID=".$value."'>".$subForumInfo['name']."</a>";
		}
		
		
		/*$dispMoreSubForums = "";
		if(count($arrDispMoreSubForums) > 0) {
			$dispMoreSubForums = "<br><br><b>111Sub-Forums:</b><br>&nbsp;&nbsp;".implode("&nbsp;&nbsp;<b>|</b>&nbsp;&nbsp;", $arrDispMoreSubForums);	
			
		}*/
		
		$subForumObj->select($boardID);
		$subForumInfo = $subForumObj->get_info_filtered();
		/*$dispSubForums .= "
			<tr class='boardRows".$newTopicBG."'>
				<td>TEST</td>
				<td class='boardName dottedLine".$newTopicBG."'><a href='viewboard.php?bID=".$subForumInfo['forumboard_id']."'>".$subForumInfo['name']."</a>".$dispNewTopicIMG."<br><span class='boardDescription'>".$subForumInfo['description'].$dispMoreSubForums."</span></td>
				<td class='dottedLine boardLastPost".$newTopicBG."'>".$dispLastPost."</td>
				<td class='dottedLine boardTopicCount".$newTopicBG."' align='center'>".$dispTopicCount."<span id='forumPageTopicCount' style='display: none'> Topics</span></td>
				<td class='dottedLine boardTopicCount".$newTopicBG."' align='center'>".$dispPostCount."<span id='forumPagePostCount' style='display: none'> Posts</span></td>
			
			</tr>
		";*/
		
		$dispSubForums .= "
				<ul class='post-963 forum type-forum status-publish hentry loop-item-0 odd bbp-forum-status-open bbp-forum-visibility-publish instock'>
					<li class='bbp-forum-info'>
						<i class='icon-comments'></i>
						<a class='bbp-forum-title' href='viewboard.php?bID=".$subForumInfo['forumboard_id']."' title='".$subForumInfo['name']."'>".$subForumInfo['name']."</a>
						<div class='bbp-forum-content'></div>
					</li>
					<li class='bbp-forum-topic-count'>".$dispTopicCount."</li>
					<li class='bbp-forum-reply-count'>".$dispPostCount."</li>
				</ul>
			";
		
	}

}

if($boardInfo['subforum_id'] != 0) {
	$subForumID = $boardInfo['subforum_id'];
	$submForumBC = array();
	while($subForumID != 0) {
		$subForumObj->select($subForumID);
		$subForumInfo = $subForumObj->get_info_filtered();
		$subForumID = $subForumInfo['subforum_id'];
		$subForumBC[] = array("link" => $MAIN_ROOT."forum/viewboard.php?bID=".$subForumInfo['forumboard_id'], "value" => $subForumInfo['name']);
	}

	krsort($subForumBC);

}

/*$boardObj->showSearchForm();
echo "
<table class='forumTable'>
";*/

if($dispSubForums != "") {

	echo "	
		<ul id='bbp-forum-965' class='bbp-topics'>
					<li class='bbp-header'>
						<ul class='forum-titles'>
							<li class='bbp-topic-title'>Sub Boards</li>
							<li class='bbp-topic-voice-count'>Topics</li>
							<li class='bbp-topic-reply-count'>Posts</li>
						</ul>
					</li>
					
					<li class='bbp-body'>
	";
	
	echo $dispSubForums;
}

$pageSelector = new PageSelector();
$pageSelector->setPages($NUM_OF_PAGES);
$pageSelector->setCurrentPage($_GET['pID']);
$pageSelector->setLink(MAIN_ROOT."forum/viewboard.php?bID=".$_GET['bID']."&pID=");



			/*if(LOGGED_IN && $boardObj->memberHasAccess($memberInfo, true)) { 
				echo "<p style='margin-top: 0px'><b>&raquo; <a href='".$MAIN_ROOT."members/console.php?cID=".$intPostTopicCID."&bID=".$boardInfo['forumboard_id']."'>NEW TOPIC</a> &laquo;</b></p>"; 
			}*/
		
		
		//$pageSelector->show();
		
$arrPageTopics = $boardObj->getForumTopics(" ft.stickystatus DESC, fp.dateposted DESC", " LIMIT ".$intOffset.", ".$NUM_PER_PAGE);

if (count($arrPageTopics) > 0)
{
	echo "	
		<ul id='bbp-forum-965' class='bbp-topics'>
					<li class='bbp-header'>
						<ul class='forum-titles'>
							<li class='bbp-topic-title'>Topics</li>
							<li class='bbp-topic-voice-count'>Replies</li>
							<li class='bbp-topic-reply-count'>Views</li>
							<li class='bbp-topic-freshness'>Freshness</li>
						</ul>
					</li>
					
					<li class='bbp-body'>
	";
}

foreach($arrPageTopics as $postID) {
	
	$boardObj->objPost->select($postID);
	$postInfo = $boardObj->objPost->get_info_filtered();

	$boardObj->objTopic->select($postInfo['forumtopic_id']);
	$topicInfo = $boardObj->objTopic->get_info();
	
	$postMemberObj->select($postInfo['member_id']);
	$dispTopicPoster = $postMemberObj->getMemberLink();
	
	$boardObj->objPost->select($topicInfo['lastpost_id']);
	$lastPostInfo = $boardObj->objPost->get_info_filtered();
	
	$postMemberObj->select($lastPostInfo['member_id']);
	$dispLastPoster = $postMemberObj->getMemberLink();
	
	$dispTopicIconsIMG = "";
	$newTopicBG = "";
	
	$showNewTopic = true;
	if($websiteInfo['forum_newindicator'] != 0) {
		$showNewTopic = ($lastPostInfo['dateposted']+(60*60*24*$websiteInfo['forum_newindicator'])) > time();
	}
	
	if($LOGGED_IN && !$member->hasSeenTopic($topicInfo['forumtopic_id']) && $showNewTopic) {
		$newTopicBG = " boardNewPostBG";
		$dispTopicIconsIMG = " <img style='margin-left: 5px' src='".$MAIN_ROOT."themes/".$THEME."/images/forum-new.png' title='New Posts!'>";
	}
	
	if($topicInfo['stickystatus'] == 1) {
		$newTopicBG = " boardNewPostBG";
		$dispTopicIconsIMG .= " <img src='".$MAIN_ROOT."themes/".$THEME."/images/forum-sticky.png' title='Sticky' style='margin-left: 5px'>";
	}
	
	if($topicInfo['lockstatus'] == 1) {
		$newTopicBG = " boardNewPostBG";
		$dispTopicIconsIMG .= " <img src='".$MAIN_ROOT."themes/".$THEME."/images/forum-locked.png' title='Locked' style='margin-left: 5px'>";
	}
	
	
	/*echo "
		<tr class='boardRows".$newTopicBG."'>
			<td class='boardName dottedLine".$newTopicBG."'><a href='viewtopic.php?tID=".$postInfo['forumtopic_id']."'>".$postInfo['title']."</a>".$dispTopicIconsIMG."<br><span class='boardDescription'>by ".$dispTopicPoster." - ".getPreciseTime($postInfo['dateposted'])."</span></td>
			<td class='boardTopicCount dottedLine".$newTopicBG."' align='center'>".$topicInfo['replies']."<span id='forumPagePostCount' style='display: none'> Replies</span></td>
			<td class='boardTopicCount dottedLine".$newTopicBG."' align='center'>".$topicInfo['views']."<span id='forumPagePostCount' style='display: none'> Views</span></td>
			<td class='boardLastPost dottedLine".$newTopicBG."'>by ".$dispLastPoster."<br>".getPreciseTime($lastPostInfo['dateposted'])."</td>
		</tr>
	";*/
	
	echo "
		<ul class='post-993 topic type-topic status-publish hentry odd bbp-parent-forum-965 user-id-1 instock'>
							<li class='bbp-topic-title'>
								<i class='icon-comment'></i>
								<a class='bbp-topic-permalink' href='viewtopic.php?tID=".$postInfo['forumtopic_id']."' title='".$postInfo['title']."'>".$postInfo['title']."</a>
								<p class='bbp-topic-meta'>
									<span class='bbp-topic-started-by'>Started by: <a href='#' title='' class='bbp-author-avatar' rel='nofollow'><img alt='' src='http://1.gravatar.com/avatar/38d93eff4c0db34aa79f07cf9ad1a89c?s=14&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D14&amp;r=G' class='avatar avatar-14 photo'></a>&nbsp;<a href='#' title='' class='bbp-author-name' rel='nofollow'>".$dispTopicPoster."</a></span>
								</p>
							</li>
							<li class='bbp-topic-voice-count'>".$topicInfo['replies']."</li>
							<li class='bbp-topic-reply-count'>".$topicInfo['views']."</li>
							<li class='bbp-topic-freshness'>
								<a href='#' title='Curabitur sollicitudin mi vel auctor auctor'>".getPreciseTime($lastPostInfo['dateposted'])."</a>
								<p class='bbp-topic-meta'>
									<span class='bbp-topic-freshness-author'><a href='#' title='' class='bbp-author-avatar' rel='nofollow'><img alt='' src='http://1.gravatar.com/avatar/38d93eff4c0db34aa79f07cf9ad1a89c?s=14&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D14&amp;r=G' class='avatar avatar-14 photo'></a>&nbsp;<a href='#' title='' class='bbp-author-name' rel='nofollow'>".$dispLastPoster."</a></span>
								</p>
							</li>
						</ul>
	";
	
}

		if(LOGGED_IN) {
			echo "
				<p style='margin-top: 0px'><b>&raquo; <a href='".$MAIN_ROOT."members/console.php?cID=".$intPostTopicCID."&bID=".$boardInfo['forumboard_id']."'>NEW TOPIC</a> &laquo;</b></p>
			";
		}

	$pageSelector->show();


if(count($arrTopics) == 0) {
	
	echo "
		<div class='shadedBox' style='width: 40%; margin: 20px auto'>
			<p class='main' align='center'>
				<i>No Posts Yet!</i><br>
				<a href='".$MAIN_ROOT."members/console.php?cID=".$intPostTopicCID."&bID=".$_GET['bID']."'>Be the first!</a>
			</p>
		</div>
	";	
	
}

if($blnPageSelect) {
	echo "
		<script type='text/javascript'>
			$(document).ready(function() {
				$('#btnPageSelectTop, #btnPageSelectBottom').click(function() {
					
					var jqPageSelect = \"#pageSelectBottom\";
					var intNewPage = 0;
					
					if($(this).attr('id') == \"btnPageSelectTop\") {
						jqPageSelect = \"#pageSelectTop\";
					}
					
					intNewPage = $(jqPageSelect).val();
					
					window.location = 'viewboard.php?bID=".$_GET['bID']."&pID='+intNewPage;
					
				});
			});
		</script>
	";
}

?>
					</li>
					
					
					<li class="bbp-footer">
						<div class="tr">
							<p>
								<span class="td colspan4">&nbsp;</span>
							</p>
						</div>
						<!-- .tr -->
					</li>
				</ul>
				<!-- #bbp-forum-965 -->
				
				
				<div id="no-topic-0" class="bbp-no-topic">
					<div class="bbp-template-notice">
						<p>You must be logged in to create new topics.</p>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>
			
			
			
<?php include("../themes/crak/_footer.php"); ?>