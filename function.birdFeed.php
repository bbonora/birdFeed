<?php
#CMS - CMS Made Simple
#(c)2004 by Ted Kulp (wishy@users.sf.net)
#This project's homepage is: http://cmsmadesimple.sf.net
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
// get feed


function fetchFeed($tid,$count) {
	$r = '';
	if ($tid != '' && $count > 0) {
		// fetch feed
		$c = curl_init();
		curl_setopt_array($c, array(
			CURLOPT_URL => 'http://api.twitter.com/1/statuses/user_timeline.json?screen_name=' . $tid . '&count=' . $count,
			CURLOPT_HEADER => false,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_RETURNTRANSFER => true
		));
	$r = curl_exec($c);
	curl_close($c);
		}
	
	// return JSON as array
	return (!!$r ? json_decode($r, false) : false);
}

	
// parses Twitter links
function parseTwitterLinks($str) {
	
	// parse URL
	$str = preg_replace('/(https{0,1}:\/\/[\w\-\.\/#?&=]*)/', '<a href="$1">$1</a>', $str);
	
	// parse @id
	$str = preg_replace('/@(\w+)/', '<a href="http://twitter.com/$1" class="at">@$1</a>', $str);
		
	// parse #hashtag
	$str = preg_replace('/\s#(\w+)/', ' <a href="http://twitter.com/#!/search?q=%23$1" class="hashtag">#$1</a>', $str);

	return $str;
	
}
// parse twitter dates
function parseDate($str,$dateformat) {
	
	// current datetime
	$now = new DateTime();

	$stime = new DateTime($str);

	if ($dateformat== 'friendly') {

		// friendly date format
		$diffAsU = $now->format('U') - $stime->format('U');		
		$ival = new DateTime("@$diffAsU");

        $ivalY = $ival->format("Y") - 1970;
        $ivalM = $ival->format("n");
        $ivalD = $ival->format("j");
        $ivalH = $ival->format("G");
        $ivalI = $ival->format("i"); //NOTE: this has leading zeroes
        $ivalS = $ival->format("s"); //NOTE: this has leading zeroes
        
        $yr = $ivalY;
        $mh = $ivalM + ($ivalD > 15);	

		if ($mh > 11) $yr = 1;

        $dy = $ivalD + ($ivalH > 15);
        $hr = $ivalH;
        $mn = $ivalI + ($ivalS > 29);		
				
		if ($yr > 0) {
			if ($yr == 1) $date = 'last year';
			else $date = $yr . ' years ago';
		}
		else if ($mh > 0) {
			if ($mh == 1) $date = 'last month';
			else $date = $mh . ' months ago';
		}
		else if ($dy > 0) {
			if ($dy == 1) $date = 'yesterday';
			else if ($dy < 8) $date = $dy . ' days ago';
			else if ($dy < 15) $date = 'last week';
			else $date = round($dy / 7) . ' weeks ago';
		}
		else if ($hr > 0) {
			$hr += ($ivalI > 29);
			$date = $hr . ' hour' . ($hr == 1 ? '' : 's') . ' ago';
		}
		else {
			if ($mn < 3) $date = 'just now';
			else $date = $mn . ' minutes ago';
		}

	}
	else {
		// standard PHP date format
		$date = $stime->format('U');
	}

	return $date;
}

function smarty_cms_function_birdfeed ($params) {
	
	$gCms = cmsms();
	$config = $gCms->GetConfig();
	$smarty = cmsms()->GetSmarty();

	$id = isset($params['username']) ? $params['username'] : '';
	$count = isset($params['count']) ? $params['count'] : 10;
	$dateformat = isset($params['dateformat']) ? $params['dateformat'] : 'friendly'; // choices are php or friendly
	$parselinks = isset($params['parselinks']) ? $params['parselinks'] : 1;
	// todo: add caching
	$nocache = isset($params['nocache']) ? $params['nocache'] : 1;
	$cachepath = isset($params['cachepath']) ? $params['cache'] : $config['root_path'].DIRECTORY_SEPARATOR.'tmp';

	$json = fetchFeed($id,$count);
	
	$entryarray = array();

	foreach($json as $key => $item) {
		$onerow = new stdClass();
		if(isset($parselinks)) {
			$onerow->text = parseTwitterlinks($item->text);	
		} else {
			$onerow->text = $item->text;
		}
		
		$onerow->date = parseDate($item->created_at, $dateformat);
		$onerow->img_url = $item->user->profile_image_url;
		$onerow->author = $item->name;
		$onerow->author_url = $item->user->screen_name;

		$entryarray[] = $onerow;
	}
	
	$smarty->assign('tweets',$entryarray);
	
}

function smarty_cms_help_function_birdfeed() {
?>
<div id="page_tabs">
    <div id="general">
        General
    </div>
    <div id="parameter">
        Parameters
    </div>
    <div id="about">
        About
    </div>
</div>
<div class="clearb"></div>
<div id="page_content">
    <div id="general_c">
    	<h3>What does this plugin do?</h3>
    	<p>
    		This plugin retrieves tweets from a twitter account and displays them on your page. The feed is assigned to a smarty variable called {$tweets} which can then be displayed within any template using a {foreach from=$tweets item=item}  loop. The status text is parsed so that all the hash tags, links and user names are automatically converted to working links.  Dates are outputted as a unix timestamp which can easily be converted into several formats using the “date_format” string modifier.  Optionally dates can also be displayed as “friendly” i.e. one hour ago, two minutes ago, yesterday, etc, etc. 
    	</p>
    	<h3>ToDo</h3>
    	<ul>
    		<li>Add caching feature</li>
    	</ul>
    </div>
    <div id="parameter_c">
    	<h3>How do I use it?</h3>
    	<p>Just insert the tag into your template/page like:<br />
    	{birdFeed username=bbonora count=10 dateformat=friendly}
    	</p>
    	<p>Once the tag has been inserted you now have access to the "$tweets" or {$tweets} variable. For example you could do something like this:<br /><br />
    		{birdfeed username=bbonora count=10 dateformat=friendly}<br />
    		{foreach from=$tweets item=item}<br />
    		<span style="margin-left: 20px;">&lt;div class='twitterDate'&gt;{$item->date}&lt;/div&gt;<br /></span>
    		<span style="margin-left: 20px;">&lt;div class='twitterText'&gt;{$item->text}&lt;/div&gt;<br /></span>
    		<span style="margin-left: 20px;">&lt;div class='twitterAuthor'&gt;{$item->author}&lt;/div&gt;</span><br />
    		{/foreach}
    	</p>
    	<h3>Tag Parameters</h3>
    	<ul>
    		<li>username <em>(required)</em> - is required. Specify the username of the twitter timeline that you would like to display.</li>
    		<li>count <em>(optional)</em> - is optional, default setting 10. This is the number of tweets to display.</li>
    		<li>dateformat <em>(optional)</em> - is optional, default setting is 'friendly'. Possible values are "friendly" and "php". If "php" is set the date will be returned as a timestamp. The "friendly" date format will be returned as "2 hours ago", "1 week ago", etc.</li>
    		<li>parselinks <em>(optional)</em> - is optional, default setting is 1. This determines whether or not hashtags (#hash), usernames (@username) and links (http://example.com) within the tweet should be converted to actual working links. If set to 0 plan text will be returned.</li>
    	</ul>
    	<h3>Template Settings</h3>
    	<p>Incorporating BirdFeed in your template is pretty straight forward and works much the same way that most other templates in CMSMS work. Below is an example.</p>
		<p>
    		{birdfeed username=bbonora count=10 dateformat=friendly}<br />
    		{foreach from=$tweets item=item}<br />
    		<span style="margin-left: 20px;">&lt;div class='twitterDate'&gt;{$item->date}&lt;/div&gt;<br /></span>
    		<span style="margin-left: 20px;">&lt;div class='twitterText'&gt;{$item->text}&lt;/div&gt;<br /></span>
    		<span style="margin-left: 20px;">&lt;div class='twitterAuthor'&gt;{$item->author}&lt;/div&gt;</span><br />
    		{/foreach}
    	</p>

<p>The following elements are available within the "tweets" object. You can always use {$tweets|print_r} or {$tweets|var_dump} to view the raw output.</p>
<ul>
	<li>date</li>
	<li>author</li>
	<li>author_img_url</li>
	<li>author_url</li>
</ul>
    </div>
    <div id="about_c">
    	<h3>Ben Bonora</h3>
    	<p>
    		email: ben@bennyvbonora.com<br />
    		web: <a href="http://www.bennyvbonora.com" target="_blank">www.bennyvbonora.com</a>
    	</p>
    </div>
</div>
<?php	
}
function smarty_cms_about_function_birdfeed() {
?>
<p>
	Author: Ben Bonora <a href="http://www.bennyvbonora.com" title="Ben V. Bonora" target="_blank">www.bennyvbonora.com</a>
</p>
<h3>Version 1.0</h3>
<?php
}
?>