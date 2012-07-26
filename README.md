<h3>What does this plugin do?</h3>
      <p>
    		This plugin retrieves tweets from a twitter account and displays them on your page. The feed is assigned to a smarty variable called {$tweets} which can then be displayed within any template using a {foreac from=$tweets item=item}  loop. The status text is parsed so that all the hash tags, links and user names are automatically converted to working links.  Dates are outputted as a unix timestamp which can easily be converted into several formats using the “date_format” string modifier.  Optionally dates can also be displayed as “friendly” i.e. one hour ago, two minutes ago, yesterday, etc, etc. 
    	</p>
    	<h3>ToDo</h3>
    	<ul>
    		<li>Add caching feature</li>
    	</ul>