<?php

include("../_setup.php");
include("../themes/crak/_header.php");

?>

<div class="container">
				<div class="row">
					<div class="title_wrapper">
						<div class="span6">
							<h1>View Forum Post</h1>
						</div>
						<div class="breadcrumbs"><strong><a href="#"></a></strong></div>
					</div>
				</div>
			</div>

			<div class="page normal-page container">
				<div class="row">
					<div class="span12">
						<div id="bbpress-forums">
							<ul id="topic-993-replies" class="forums bbp-replies">
								<li class="bbp-header">
									<div class="bbp-reply-author">Author</div>
									<!-- .bbp-reply-author -->
									<div class="bbp-reply-content">
										Posts
									</div>
									<!-- .bbp-reply-content -->
								</li>
<?php

$btThemeObj->addHeadItem("richtexteditor1", "<script type='text/javascript' src='".$MAIN_ROOT."js/ckeditor/ckeditor.js'></script>");

$consoleObj = new ConsoleOption($mysqli);
$boardObj = new ForumBoard($mysqli);
$member = new Member($mysqli);
$postMemberObj = new Member($mysqli);
$posterRankObj = new Rank($mysqli);

$intPostTopicCID = $consoleObj->findConsoleIDByName("Post Topic");
$intManagePostsCID = $consoleObj->findConsoleIDByName("Manage Forum Posts");

$categoryObj = new BasicOrder($mysqli, "forum_category", "forumcategory_id");
$categoryObj->set_assocTableName("forum_board");
$categoryObj->set_assocTableKey("forumboard_id");

$downloadCatObj = new DownloadCategory($mysqli);
$attachmentObj = new Download($mysqli);

$downloadCatObj->selectBySpecialKey("forumattachments");

$moveTopicCID = $consoleObj->findConsoleIDByName("Move Topic");

if(!$boardObj->objTopic->select($_GET['tID'])) {
	echo "
	<script type='text/javascript'>window.location = 'index.php';</script>
	";
	exit();
}

$topicInfo = $boardObj->objTopic->get_info();
$boardObj->select($topicInfo['forumboard_id']);
$boardObj->objPost->select($topicInfo['forumpost_id']);
$boardInfo = $boardObj->get_info_filtered();

$postInfo = $boardObj->objPost->get_info_filtered();

$boardObj->objPost->select($topicInfo['lastpost_id']);
$lastPostInfo = $boardObj->objPost->get_info_filtered();

$EXTERNAL_JAVASCRIPT .= "<script type='text/javascript' src='".$MAIN_ROOT."js/ace/src-min-noconflict/ace.js' charset='utf-8'></script>";

define("RESIZE_FORUM_IMAGES", true);
include("forum_image_resize.php");

// Quick Reply

$quickReplyForm = new Form();
$btThemeObj->addHeadItem("richtext-js", $quickReplyForm->getRichtextboxJSFile());

/*if($websiteInfo['privateforum'] == 1 && !constant("LOGGED_IN")) {
	die("<script type='text/javascript'>window.location = '".$MAIN_ROOT."login.php';</script>");
}*/


$blnShowAttachments = false;
if((constant('LOGGED_IN') == true && $downloadCatObj->get_info("accesstype") == 1) || $downloadCatObj->get_info("accesstype") == 0) {
	$blnShowAttachments = true;
}

$memberInfo = array();


$LOGGED_IN = false;
$NUM_PER_PAGE = $websiteInfo['forum_postsperpage'];
if($member->select($_SESSION['btUsername']) && $member->authorizeLogin($_SESSION['btPassword'])) {
	$memberInfo = $member->get_info_filtered();
	$LOGGED_IN = true;
	$NUM_PER_PAGE = $memberInfo['postsperpage'];
	
	if(!$member->hasSeenTopic($topicInfo['forumtopic_id'])) {
		$mysqli->query("INSERT INTO ".$dbprefix."forum_topicseen (member_id, forumtopic_id) VALUES ('".$memberInfo['member_id']."', '".$topicInfo['forumtopic_id']."')");
	}
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

$arrUpdateViewsColumn = array("views");
$newViewCount = $topicInfo['views']+1;
$arrUpdateViewsValue = array($newViewCount);
$boardObj->objTopic->update($arrUpdateViewsColumn, $arrUpdateViewsValue);

$totalPostsSQL = $mysqli->query("SELECT forumpost_id FROM ".$dbprefix."forum_post WHERE forumtopic_id = '".$topicInfo['forumtopic_id']."' ORDER BY dateposted");

$totalPosts = $totalPostsSQL->num_rows;


if(!isset($_GET['pID']) || !is_numeric($_GET['pID'])) {
	$intOffset = 0;
	$_GET['pID'] = 1;
}
else {
	$intOffset = $NUM_PER_PAGE*($_GET['pID']-1);
}

$blnPageSelect = false;

// Count Pages
$NUM_OF_PAGES = ceil($totalPosts/$NUM_PER_PAGE);

if($NUM_OF_PAGES == 0) {
	$NUM_OF_PAGES = 1;	
}

if($_GET['pID'] > $NUM_OF_PAGES) {

	echo "
	<script type='text/javascript'>window.location = 'viewtopic.php?tID=".$_GET['tID']."';</script>
	";
	exit();

}

if($boardInfo['subforum_id'] != 0) {
	$subForumObj = new ForumBoard($mysqli);
	$subForumID = $boardInfo['subforum_id'];
	$submForumBC = array();
	while($subForumID != 0) {
		$subForumObj->select($subForumID);
		$subForumInfo = $subForumObj->get_info_filtered();
		$subForumID = $subForumInfo['subforum_id'];
		$subForumBC[] = array("link" => $MAIN_ROOT."forum/viewboard.php?bID=".$subForumInfo['forumboard_id'], "value" => $subForumInfo['name']);
	}

	krsort($subForumBC);
	foreach($subForumBC as $bcInfo) {
		$breadcrumbObj->addCrumb($bcInfo['value'], $bcInfo['link']);
	}

}
$breadcrumbObj->addCrumb($boardInfo['name'], $MAIN_ROOT."forum/viewboard.php?bID=".$boardInfo['forumboard_id']);
$breadcrumbObj->addCrumb($postInfo['title']);
include($prevFolder."include/breadcrumb.php");


$blnManagePosts = false;
$dispManagePosts = "";
if($LOGGED_IN) {
	if($topicInfo['lockstatus'] == 0) {
		$dispPostReply = "<b>&raquo; <a href='".$MAIN_ROOT."members/console.php?cID=".$intPostTopicCID."&bID=".$topicInfo['forumboard_id']."&tID=".$topicInfo['forumtopic_id']."'>POST REPLY</a> &laquo;</b>";
	}
	else {
		$dispPostReply = "<b>&raquo; LOCKED &laquo;</b>";	
	}
	
	$consoleObj->select($intManagePostsCID);
	if($boardObj->memberIsMod($memberInfo['member_id']) || $member->hasAccess($consoleObj)) {
		$blnManagePosts = true;
		
		if($topicInfo['stickystatus'] == 0) {
			$dispManagePosts .= "<b>&raquo <a href='".$MAIN_ROOT."members/console.php?cID=".$intManagePostsCID."&tID=".$topicInfo['forumtopic_id']."&action=sticky'>STICKY TOPIC</a> &laquo;</b>&nbsp;&nbsp;&nbsp;";
		}
		else {
			$dispManagePosts .= "<b>&raquo <a href='".$MAIN_ROOT."members/console.php?cID=".$intManagePostsCID."&tID=".$topicInfo['forumtopic_id']."&action=sticky'>UNSTICKY TOPIC</a> &laquo;</b>&nbsp;&nbsp;&nbsp;";
		}
		
		
		if($topicInfo['lockstatus'] == 0) {
			$dispManagePosts .= "<b>&raquo <a href='".$MAIN_ROOT."members/console.php?cID=".$intManagePostsCID."&tID=".$topicInfo['forumtopic_id']."&action=lock'>LOCK TOPIC</a> &laquo;</b>&nbsp;&nbsp;&nbsp;";
		}
		else {
			$dispManagePosts .= "<b>&raquo <a href='".$MAIN_ROOT."members/console.php?cID=".$intManagePostsCID."&tID=".$topicInfo['forumtopic_id']."&action=lock'>UNLOCK TOPIC</a> &laquo;</b>&nbsp;&nbsp;&nbsp;";
		}
		
		$dispManagePosts .= "<b>&raquo <a href='javascript:void(0)' onclick='deleteTopic()'>DELETE TOPIC</a> &laquo;</b>&nbsp;&nbsp;&nbsp;";
		$dispManagePosts .= "<b>&raquo <a href='".$MAIN_ROOT."members/console.php?cID=".$moveTopicCID."&topicID=".$_GET['tID']."'>MOVE TOPIC</a> &laquo;</b>&nbsp;&nbsp;&nbsp;";
	}


}



//$boardObj->showSearchForm();

echo "
<div class='formDiv' style='background: none; border: 0px; overflow: auto'>
	<div style='float: right'>".$dispManagePosts.$dispPostReply."</div>
</div>
";

$pageSelector = new PageSelector();
$pageSelector->setPages($NUM_OF_PAGES);
$pageSelector->setCurrentPage($_GET['pID']);
$pageSelector->setLink(MAIN_ROOT."forum/viewtopic.php?tID=".$_GET['tID']."&pID=");
$pageSelector->show();

$countManagablePosts = 0;
define("SHOW_FORUMPOST", true);
$result = $mysqli->query("SELECT forumpost_id FROM ".$dbprefix."forum_post WHERE forumtopic_id = '".$topicInfo['forumtopic_id']."' ORDER BY dateposted LIMIT ".$intOffset.", ".$NUM_PER_PAGE);
while($row = $result->fetch_assoc()) {
	$boardObj->objPost->select($row['forumpost_id']);
	$boardObj->objPost->blnManageable = $blnManagePosts;
	
	if($boardObj->objPost->get_info("member_id") == $memberInfo['member_id'] || $blnManagePosts) {
		$countManagablePosts++;
		$boardObj->objPost->blnManageable = true;
	}
	
	$boardObj->objPost->show();
}

$pageSelector->show();

echo "
<div class='formDiv' style='background: none; border: 0px; overflow: auto'>
	<div style='float: right'>".$dispManagePosts.$dispPostReply."</div>
</div>

";

if(LOGGED_IN && $topicInfo['lockstatus'] == 0) {
	
	$forumConsoleObj = new ConsoleOption($mysqli);
	$postCID = $forumConsoleObj->findConsoleIDByName("Post Topic");
	$forumConsoleObj->select($postCID);
	$postReplyLink = $forumConsoleObj->getLink();
	
	$i = 1;
	$arrComponents = array(
		"message" => array(
			"type" => "richtextbox",
			"sortorder" => $i++,
			"display_name" => "Message",
			"attributes" => array("id" => "richTextarea", "style" => "width: 90%", "rows" => "10"),
			"validate" => array("NOT_BLANK")
		),
		"submit" => array(
			"type" => "submit",
			"sortorder" => $i++,
			"attributes" => array("class" => "submitButton formSubmitButton"),
			"value" => "Post"
		)
	);
	
	$arrSetupReplyForm = array(
		"name" => "forum-quick-reply",
		"components" => $arrComponents,
		"wrapper" => array(),
		"attributes" => array("method" => "post", "action" => $postReplyLink."&bID=".$boardInfo['forumboard_id']."&tID=".$topicInfo['forumtopic_id'])
	);
	
	$quickReplyForm->buildForm($arrSetupReplyForm);
	echo "

		<div class='formDiv'>
			<b>Quick Reply:</b>

			";
		
		$quickReplyForm->show();
	
	echo "
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
					
					window.location = 'viewtopic.php?tID=".$_GET['tID']."&pID='+intNewPage;
					
				});
			});
		</script>
	";
}

if($blnManagePosts) {
	echo "
		<div id='confirmDeleteTopicDiv' style='display: none'>
			<p align='center' class='main'>
				Are you sure you want to delete this topic?<br><br>
				All posts will be deleted within the topic as well.
			</p>
		</div>
		<script type='text/javascript'>
			function deleteTopic() {
			
				$(document).ready(function() {
	
					$('#confirmDeleteTopicDiv').dialog({
						title: 'Delete Topic - Confirm Delete',
						show: 'scale',
						zIndex: 99999,
						width: 400,
						resizable: false,
						modal: true,
						buttons: {
							'Yes': function() {
								$(this).dialog('close');
								window.location = '".$MAIN_ROOT."members/console.php?cID=".$intManagePostsCID."&tID=".$topicInfo['forumtopic_id']."&action=delete'
							},
							'Cancel': function() {
								$(this).dialog('close');
							}
						}
					
					});
				
				});
	
			}
		</script>
	";
}


if($countManagablePosts > 0) {
	echo "
	
	<div id='confirmDeleteDiv' style='display: none'>
			<p align='center' class='main'>
				Are you sure you want to delete this post?<br><br>
			</p>
		</div>
		<script type='text/javascript'>
			function deletePost(intPostID) {
			
				$(document).ready(function() {
	
					$('#confirmDeleteDiv').dialog({
						title: 'Delete Post - Confirm Delete',
						show: 'scale',
						zIndex: 99999,
						width: 400,
						resizable: false,
						modal: true,
						buttons: {
							'Yes': function() {
								$(this).dialog('close');
								window.location = '".$MAIN_ROOT."members/console.php?cID=".$intManagePostsCID."&pID='+intPostID+'&action=delete'
							},
							'Cancel': function() {
								$(this).dialog('close');
							}
						}
					
					});
				
				});
	
			}
		</script>
	";
	
}

?>
						
						
						
							
								
								<!-- .bbp-header -->
								<li class="bbp-body">
									<div class="bbp-reply-header">
										<div class="bbp-meta">
											<span class="bbp-reply-post-date">August 30, 2013 at 1:46 pm</span>
											<a href="#" class="bbp-reply-permalink">#993</a>
											<span class="bbp-admin-links"></span>
										</div>
										<!-- .bbp-meta -->
									</div>
									<!-- #post-993 -->
									<div class="post-993 topic type-topic status-publish hentry odd bbp-parent-forum-965 bbp-parent-topic-993 bbp-reply-position-1 user-id-1 topic-author instock">
										<div class="bbp-reply-author">
											<a href="#" title="View admin's profile" class="bbp-author-avatar" rel="nofollow"><img alt="" src="http://1.gravatar.com/avatar/38d93eff4c0db34aa79f07cf9ad1a89c?s=80&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D80&amp;r=G" class="avatar avatar-80 photo"></a><br><a href="#" title="View admin's profile" class="bbp-author-name" rel="nofollow">admin</a><br>
											<div class="bbp-author-role">Keymaster</div>
										</div>
										<!-- .bbp-reply-author -->
										<div class="bbp-reply-content">
											<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse tincidunt lacinia lacus, eget malesuada neque viverra et. Pellentesque malesuada urna ac magna dictum ultricies. Sed egestas magna eget urna porttitor, in tristique dui molestie. Nulla at rutrum est. Integer vitae neque ipsum. Phasellus volutpat nulla urna, eu mattis nibh egestas eget. Curabitur suscipit lectus a facilisis vulputate. Integer ut enim dictum, porttitor est id, placerat lorem. Maecenas quis vestibulum leo. Duis vulputate ut dui sed pretium.</p>
											<p>Nulla vehicula, urna nec pretium elementum, lorem odio fringilla dolor, vitae volutpat metus urna eu augue. Aenean gravida dui elit, ut elementum elit pretium vitae. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Pellentesque aliquam nibh eget nisi hendrerit, sit amet commodo sem aliquet. Sed tincidunt lorem lorem, in pellentesque velit auctor ut. Pellentesque imperdiet eros at ante dictum aliquam. Integer et nulla lobortis, tristique purus accumsan, sollicitudin dui. Curabitur sollicitudin mi vel auctor auctor. Pellentesque placerat tincidunt magna quis condimentum.</p>
											<p>In hac habitasse platea dictumst. Duis malesuada imperdiet lacus sed bibendum. Vivamus sed purus quis enim feugiat laoreet quis eget arcu. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam et ante vel magna laoreet mattis sed ac mi. Donec id sollicitudin odio. Vestibulum nec ipsum convallis, sagittis sapien tincidunt, posuere arcu. Integer vitae malesuada nisi. Nullam venenatis, dolor quis blandit interdum, lorem erat porta nibh, lobortis tempor ligula nulla eu arcu. Cras quis nisi sit amet lacus sagittis eleifend vel eu quam. Proin tempor, lectus eget porta ullamcorper, sem lectus vulputate arcu, viverra lacinia ipsum lorem et lacus. Etiam sodales ante sit amet sagittis vestibulum. Morbi venenatis vestibulum aliquet. Suspendisse potenti.</p>
										</div>
										<!-- .bbp-reply-content -->
									</div>
									<!-- .reply -->
								</li>
								
								<li class="bbp-body">
									<div class="bbp-reply-header">
										<div class="bbp-meta">
											<span class="bbp-reply-post-date">August 30, 2013 at 1:46 pm</span>
											<a href="#" class="bbp-reply-permalink">#993</a>
											<span class="bbp-admin-links"></span>
										</div>
										<!-- .bbp-meta -->
									</div>
									<!-- #post-993 -->
									<div class="post-993 topic type-topic status-publish hentry odd bbp-parent-forum-965 bbp-parent-topic-993 bbp-reply-position-1 user-id-1 topic-author instock">
										<div class="bbp-reply-author">
											<a href="#" title="View admin's profile" class="bbp-author-avatar" rel="nofollow"><img alt="" src="http://1.gravatar.com/avatar/38d93eff4c0db34aa79f07cf9ad1a89c?s=80&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D80&amp;r=G" class="avatar avatar-80 photo"></a><br><a href="#" title="View admin's profile" class="bbp-author-name" rel="nofollow">admin</a><br>
											<div class="bbp-author-role">Keymaster</div>
										</div>
										<!-- .bbp-reply-author -->
										<div class="bbp-reply-content">
											<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse tincidunt lacinia lacus, eget malesuada neque viverra et. Pellentesque malesuada urna ac magna dictum ultricies. Sed egestas magna eget urna porttitor, in tristique dui molestie. Nulla at rutrum est. Integer vitae neque ipsum. Phasellus volutpat nulla urna, eu mattis nibh egestas eget. Curabitur suscipit lectus a facilisis vulputate. Integer ut enim dictum, porttitor est id, placerat lorem. Maecenas quis vestibulum leo. Duis vulputate ut dui sed pretium.</p>
											<p>Nulla vehicula, urna nec pretium elementum, lorem odio fringilla dolor, vitae volutpat metus urna eu augue. Aenean gravida dui elit, ut elementum elit pretium vitae. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Pellentesque aliquam nibh eget nisi hendrerit, sit amet commodo sem aliquet. Sed tincidunt lorem lorem, in pellentesque velit auctor ut. Pellentesque imperdiet eros at ante dictum aliquam. Integer et nulla lobortis, tristique purus accumsan, sollicitudin dui. Curabitur sollicitudin mi vel auctor auctor. Pellentesque placerat tincidunt magna quis condimentum.</p>
											<p>In hac habitasse platea dictumst. Duis malesuada imperdiet lacus sed bibendum. Vivamus sed purus quis enim feugiat laoreet quis eget arcu. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam et ante vel magna laoreet mattis sed ac mi. Donec id sollicitudin odio. Vestibulum nec ipsum convallis, sagittis sapien tincidunt, posuere arcu. Integer vitae malesuada nisi. Nullam venenatis, dolor quis blandit interdum, lorem erat porta nibh, lobortis tempor ligula nulla eu arcu. Cras quis nisi sit amet lacus sagittis eleifend vel eu quam. Proin tempor, lectus eget porta ullamcorper, sem lectus vulputate arcu, viverra lacinia ipsum lorem et lacus. Etiam sodales ante sit amet sagittis vestibulum. Morbi venenatis vestibulum aliquet. Suspendisse potenti.</p>
										</div>
										<!-- .bbp-reply-content -->
									</div>
									<!-- .reply -->
								</li>
								
								<li class="bbp-body">
									<div class="bbp-reply-header">
										<div class="bbp-meta">
											<span class="bbp-reply-post-date">August 30, 2013 at 1:46 pm</span>
											<a href="#" class="bbp-reply-permalink">#993</a>
											<span class="bbp-admin-links"></span>
										</div>
										<!-- .bbp-meta -->
									</div>
									<!-- #post-993 -->
									<div class="post-993 topic type-topic status-publish hentry odd bbp-parent-forum-965 bbp-parent-topic-993 bbp-reply-position-1 user-id-1 topic-author instock">
										<div class="bbp-reply-author">
											<a href="#" title="View admin's profile" class="bbp-author-avatar" rel="nofollow"><img alt="" src="http://1.gravatar.com/avatar/38d93eff4c0db34aa79f07cf9ad1a89c?s=80&amp;d=http%3A%2F%2F1.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536%3Fs%3D80&amp;r=G" class="avatar avatar-80 photo"></a><br><a href="#" title="View admin's profile" class="bbp-author-name" rel="nofollow">admin</a><br>
											<div class="bbp-author-role">Keymaster</div>
										</div>
										<!-- .bbp-reply-author -->
										<div class="bbp-reply-content">
											<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse tincidunt lacinia lacus, eget malesuada neque viverra et. Pellentesque malesuada urna ac magna dictum ultricies. Sed egestas magna eget urna porttitor, in tristique dui molestie. Nulla at rutrum est. Integer vitae neque ipsum. Phasellus volutpat nulla urna, eu mattis nibh egestas eget. Curabitur suscipit lectus a facilisis vulputate. Integer ut enim dictum, porttitor est id, placerat lorem. Maecenas quis vestibulum leo. Duis vulputate ut dui sed pretium.</p>
											<p>Nulla vehicula, urna nec pretium elementum, lorem odio fringilla dolor, vitae volutpat metus urna eu augue. Aenean gravida dui elit, ut elementum elit pretium vitae. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Pellentesque aliquam nibh eget nisi hendrerit, sit amet commodo sem aliquet. Sed tincidunt lorem lorem, in pellentesque velit auctor ut. Pellentesque imperdiet eros at ante dictum aliquam. Integer et nulla lobortis, tristique purus accumsan, sollicitudin dui. Curabitur sollicitudin mi vel auctor auctor. Pellentesque placerat tincidunt magna quis condimentum.</p>
											<p>In hac habitasse platea dictumst. Duis malesuada imperdiet lacus sed bibendum. Vivamus sed purus quis enim feugiat laoreet quis eget arcu. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam et ante vel magna laoreet mattis sed ac mi. Donec id sollicitudin odio. Vestibulum nec ipsum convallis, sagittis sapien tincidunt, posuere arcu. Integer vitae malesuada nisi. Nullam venenatis, dolor quis blandit interdum, lorem erat porta nibh, lobortis tempor ligula nulla eu arcu. Cras quis nisi sit amet lacus sagittis eleifend vel eu quam. Proin tempor, lectus eget porta ullamcorper, sem lectus vulputate arcu, viverra lacinia ipsum lorem et lacus. Etiam sodales ante sit amet sagittis vestibulum. Morbi venenatis vestibulum aliquet. Suspendisse potenti.</p>
										</div>
										<!-- .bbp-reply-content -->
									</div>
									<!-- .reply -->
								</li>
								
								
								<!-- .bbp-body -->
								
								<!-- .bbp-footer -->
							</ul>
							<!-- #topic-993-replies -->
							
							<div class="bbp-pagination">
								<div class="bbp-pagination-count">
									Viewing 1 post (of 1 total)
								</div>
								<div class="bbp-pagination-links">
								</div>
							</div>
							<div id="no-reply-993" class="bbp-no-reply">
								<div class="bbp-template-notice">
									<p>You must be logged in to reply to this topic.</p>
								</div>
							</div>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

<?php include("../themes/crak/_footer.php"); ?>