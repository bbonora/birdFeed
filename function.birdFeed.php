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
			CURLOPT_URL => 'http://twitter.com/statuses/user_timeline/' . $tid . '.json?count=' . $count,
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
		$ival = $now->diff($stime);
				
		$yr = $ival->y;
		$mh = $ival->m + ($ival->d > 15);
		if ($mh > 11) $yr = 1;
		$dy = $ival->d + ($ival->h > 15);
		$hr = $ival->h;
		$mn = $ival->i + ($ival->s > 29);
				
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
			$hr += ($ival->i > 29);
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

function smarty_cms_function_birdFeed ($params) {
	
	$gCms = cmsms();
	$config = $gCms->GetConfig();
	$smarty = cmsms()->GetSmarty();

	$id = isset($params['tid']) ? $params['tid'] : '';
	$count = isset($params['count']) ? $params['count'] : 10;
	$dateformat = isset($params['dateformat']) ? $params['dateformat'] : 'friendly'; // choices are php or friendly
	$parselinks = isset($params['parslinks']) ? $params['parselinks'] : 1;
	// todo: add caching
	$nocache = isset($params['nocache']) ? $params['nocache'] : 1;
	$cachepath = isset($params['cachepath']) ? $params['cache'] : $config['root_path'].DIRECTORY_SEPARATOR.'tmp';

	$json = fetchFeed($id,$count);
	
	$entryarray = array();

	foreach($json as $key => $item) {
		$onerow = new stdClass();

		$onerow->text = parseTwitterlinks($item->text);
		$onerow->date = parseDate($item->created_at, $dateformat);
		$onerow->img_url = $item->user->profile_image_url;
		$onerow->author = $item->name;
		$onerow->author_url = $item->user->screen_name;

		$entryarray[] = $onerow;
	}
	
	$smarty->assign('tweets',$entryarray);
	
}

function smarty_cms_help_function_birdFeed() {
?>
<div id="page_tabs">
    <div id="general">
        General
    </div>
    <div id="parameter">
        Parameter
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
    		This plugin retrieves tweets from a twitter account and displays them on your page. The feed is assigned to a smarty variable called {$tweets} which can then be displayed within any template using a {foreac from=$tweets item=item}  loop. The status text is parsed so that all the hash tags, links and user names are automatically converted to working links.  Dates are outputted as a unix timestamp which can easily be converted into several formats using the “date_format” string modifier.  Optionally dates can also be displayed as “friendly” i.e. one hour ago, two minutes ago, yesterday, etc, etc. 
    	</p>
    	<h3>ToDo</h3>
    	<ul>
    		<li>Add caching feature</li>
    	</ul>
    </div>
    <div id="parameter_c">
    	Parameters
    </div>
    <div id="about_c">
    	about
    </div>
</div>
<?php	
}
function smarty_cms_about_function_birdFeed() {
?>
<p>
	Author: Ben Bonora <a href="http://www.bennyvbonora.com" title="Ben V. Bonora" target="_blank">www.bennyvbonora.com</a>
</p>
<h3>Version 1.0</h3>
<?php
}
?>