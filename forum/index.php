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
			<div class="breadcrumbs"><strong><a href="../">Home</a> / Forums</strong></div>
		</div>
	</div>
</div>
			
<div class="page normal-page container">
	<div class="row">
		<div class="span12">
			<div id="bbpress-forums">
				<ul id="forums-list-0" class="bbp-forums">
					<li class="bbp-header">
						<ul class="forum-titles">
							<li class="bbp-forum-info">Forum</li>
							<li class="bbp-forum-topic-count">Topics</li>
							<li class="bbp-forum-reply-count">Posts</li>
							<li class="bbp-forum-freshness">Freshness</li>
						</ul>
					</li>
					<!-- .bbp-header -->
					
					<li class="bbp-body">
<?php

$consoleObj = new ConsoleOption($mysqli);
$boardObj = new ForumBoard($mysqli);
$subForumObj = new ForumBoard($mysqli);
$member = new Member($mysqli);
$postMemberObj = new Member($mysqli);

$categoryObj = new BasicOrder($mysqli, "forum_category", "forumcategory_id");
$categoryObj->set_assocTableName("forum_board");
$categoryObj->set_assocTableKey("forumboard_id");

// Check Private Forum
/*if($websiteInfo['privateforum'] == 1 && !constant("LOGGED_IN")) {
	die("<script type='text/javascript'>window.location = '".$MAIN_ROOT."login.php';</script>");
}*/

$memberInfo = array();

$LOGGED_IN = false;
if($member->select($_SESSION['btUsername']) && $member->authorizeLogin($_SESSION['btPassword'])) {
	$memberInfo = $member->get_info_filtered();
	$LOGGED_IN = true;
}

//$boardObj->showSearchForm();

// Latest Post
$arrLatestPostInfo = array("time" => 0, "id" => 0);

$result = $mysqli->query("SELECT forumcategory_id FROM ".$dbprefix."forum_category ORDER BY ordernum DESC");
while($row = $result->fetch_assoc()) {
	$arrForumCats[] = $row['forumcategory_id'];
	
	$categoryObj->select($row['forumcategory_id']);
	$catInfo = $categoryObj->get_info_filtered();
	$arrBoards = $categoryObj->getAssociateIDs("AND subforum_id = '0' ORDER BY sortnum", true);
	$dispBoards = "";
	foreach($arrBoards as $boardID) {
		
		$boardObj->select($boardID);
		
		if($boardObj->memberHasAccess($memberInfo)) {
			$boardInfo = $boardObj->get_info_filtered();
			$arrForumTopics = $boardObj->getForumTopics();
			
			$newTopicBG = "";
			$dispNewTopicIMG = "";
			
			if($LOGGED_IN && $boardObj->hasNewTopics($memberInfo['member_id'])) {
				$dispNewTopicIMG = " <img style='margin-left: 5px' src='".$MAIN_ROOT."themes/".$THEME."/images/forum-new.png' title='New Posts!'>";
				$newTopicBG = " boardNewPostBG";
			}
			
			// Get Last Post Display Info
			if(count($arrForumTopics) > 0) {
				$boardObj->objPost->select($arrForumTopics[0]);
				$firstPostInfo = $boardObj->objPost->get_info_filtered();
				
				$boardObj->objTopic->select($firstPostInfo['forumtopic_id']);
				$lastPostID = $boardObj->objTopic->get_info("lastpost_id");
				
				$boardObj->objPost->select($lastPostID);
				$lastPostInfo = $boardObj->objPost->get_info_filtered();
				
				if($lastPostInfo['dateposted'] > $arrLatestPostInfo['time']) {
					$arrLatestPostInfo['time'] = $lastPostInfo['dateposted'];
					$arrLatestPostInfo['id'] = $lastPostInfo['forumpost_id'];
				}
				
				
				$postMemberObj->select($lastPostInfo['member_id']);
				
				$dispLastPost = "<a href='viewtopic.php?tID=".$firstPostInfo['forumtopic_id']."#".$lastPostID."' title='".$firstPostInfo['title']."'>".getPreciseTime($lastPostInfo['dateposted'])."</a>
						<p class='bbp-topic-meta'>
							<span class='bbp-topic-freshness-author'><a href='#' title='' class='bbp-author-avatar' rel='nofollow'>
							<img alt='' src='http://1.gravatar.com/avatar/38d93eff4c0db34aa79f07cf9ad1a89c?s=14&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D14&amp;r=G' class='avatar avatar-14 photo' height='14' width='14'></a>&nbsp;<a href='#' title='' class='bbp-author-name' rel='nofollow'>".$postMemberObj->getMemberLink()."</a></span>
						</p>";
			}
			else
			{
				$dispLastPost = "No Topics
								<p class='bbp-topic-meta'>
									<span class='bbp-topic-freshness-author'></span>
								</p>";
			}
			
			$dispTopicCount = $boardObj->countTopics();
			$dispPostCount = $boardObj->countPosts();
			
			$arrDispSubForums = array();
			$arrSubForums = $boardObj->getSubForums();
		
			foreach($arrSubForums as $value) {
				$subForumObj->select($value);
				$subForumInfo = $subForumObj->get_info_filtered();
				
				$arrDispSubForums[] = "<a href='".$MAIN_ROOT."forum/viewboard.php?bID=".$value."'>".$subForumInfo['name']."</a>";
			}
			
			
			/*$dispSubForums = "";
			if(count($arrDispSubForums) > 0) {
				$dispSubForums = "<br><br><b>Sub-Forums:</b><br>&nbsp;&nbsp;".implode("&nbsp;&nbsp;<b>|</b>&nbsp;&nbsp;", $arrDispSubForums);	
			}*/
			
			$dispBoards .= "
				<ul class='post-963 forum type-forum status-publish hentry loop-item-0 odd bbp-forum-status-open bbp-forum-visibility-publish instock'>
					<li class='bbp-forum-info'>
						<i class='icon-comments'></i>
						<a class='bbp-forum-title' href='viewboard.php?bID=".$boardInfo['forumboard_id']."' title='".$boardInfo['name']."'>".$boardInfo['name']."</a>
						<div class='bbp-forum-content'></div>
					</li>
					<li class='bbp-forum-topic-count'>".$dispTopicCount."</li>
					<li class='bbp-forum-reply-count'>".$dispPostCount."</li>
					<li class='bbp-forum-freshness'>
						".$dispLastPost."
					</li>
				</ul>
			";
		}
	}
	
	if($dispBoards != "") 
	{
		echo $dispBoards;
	}
}

if($result->num_rows == 0) {
	echo "
		
		<div class='shadedBox' style='width: 40%; margin: 20px auto'>
			<p class='main' align='center'>
				No boards have been made yet!
			</p>
		</div>
	
	";
}

?>

					</li>
					
					
					<!-- .bbp-body -->
					<li class="bbp-footer">
						<div class="tr">
							<p class="td colspan4">&nbsp;</p>
						</div>
						<!-- .tr -->
					</li>
					<!-- .bbp-footer -->
				</ul>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>
			
<?php include("../themes/crak/_footer.php"); ?>