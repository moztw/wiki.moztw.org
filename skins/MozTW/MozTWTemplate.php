<?php
/**
 * Mozilla Taiwan Skin for mediawiki 1.25+
 *
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */

class MozTWTemplate extends QuickTemplate {
	/**
	 * Template filter callback for MonoBook skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php $this->text('lang') ?>" lang="<?php $this->text('lang') ?>" dir="<?php $this->text('dir') ?>">
  <head>
    <meta http-equiv="Content-Type" content="<?php $this->text('mimetype') ?>; charset=<?php $this->text('charset') ?>" />
    <?php $this->html('headlinks') ?>
    <title><?php $this->text('pagetitle') ?></title>
    <style type="text/css" media="screen,projection">/*<![CDATA[*/ @import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/main.css"; /*]]>*/</style>
    <?php if($this->data['jsvarurl'  ]) { ?><script type="text/javascript" src="<?php $this->text('jsvarurl'  ) ?>"></script><?php } ?>
    <?php if($this->data['usercss'   ]) { ?><style type="text/css"><?php              $this->html('usercss'   ) ?></style><?php    } ?>
    <?php if($this->data['userjs'    ]) { ?><script type="text/javascript" src="<?php $this->text('userjs'    ) ?>"></script><?php } ?>
    <?php if($this->data['userjsprev']) { ?><script type="text/javascript"><?php      $this->html('userjsprev') ?></script><?php   } ?>
  </head>

  <body <?php if($this->data['body_ondblclick']) { ?>ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
        <?php if($this->data['nsclass'        ]) { ?>class="secondLevel sectionWiki <?php      $this->text('nsclass')         ?>"<?php } ?>>
<div id="header">
<h1><a href="https://moztw.org/" title="Return to home page, moztw.org" accesskey="1">Mozilla Taiwan (MozTW)</a></h1>
 <ul>
  <li id="menu_aboutus"><a href="https://moztw.org/about" title="About this website">關於本站</a></li>
  <li id="menu_contribute"><a title="了解我們正在做什麼，看看您能幫上什麼忙！" href="https://moztw.org/contribute">參與我們</a></li>
  <li id="menu_planet"><a title="MozTW 社群成員閒聊區" href="https://planet.moztw.org/">摩茲星球</a></li>
  <li id="menu_wiki"><a href="https://wiki.moztw.org/" title="Mozilla Taiwan 共筆系統">共筆系統</a></li>
  <li id="menu_forum"><a href="https://forum.moztw.org/" title="Mozilla 華語社群討論區">討論區</a></li>
  <li id="menu_products"><a href="https://moztw.org/dl" title="Moztw.org 提供的中文化軟體一覽">下載</a></li>
 </ul>
</div>
<div class="key-point" style="background:#FCF4C4;margin:1em 3em;padding:5px 30px; font-size: 1.5em;">
<h2>舊文件</h2>
<p>此處文件僅供參考，請自行考量時效性與適用程度，其他庫藏文件請參考<a href="https://moztw.org/docs/" title="更舊的文件">文件頁面</a>；<br>我們亟需您的協助，進行共筆系統搬移、及文件整理工作，詳情請查閱<a title="了解我們正在做什麼，看看您能幫上什麼忙！" href="https://moztw.org/contribute">參與我們</a>。</p>
</div>
  <div id="globalWrapper">
      <div id="column-content">
	<div id="content">
	<div id="p-cactions" class="portlet">
	<h5>Views</h5>
	<ul>
	<?php foreach($this->data['content_actions'] as $key => $action) {
	    ?><li id="ca-<?php echo htmlspecialchars($key) ?>"
	    <?php if($action['class']) { ?>class="<?php echo htmlspecialchars($action['class']) ?>"<?php } ?>
	    ><a href="<?php echo htmlspecialchars($action['href']) ?>"><?php
	    echo htmlspecialchars($action['text']) ?></a></li><?php
	    } ?>
	    </ul>
	    </div>
	  <a name="top" id="contentTop"></a>
	  <?php if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>
	  <h1 class="firstHeading"><?php $this->text('title') ?></h1>
	  <div id="bodyContent">
	    <h3 id="siteSub"><?php $this->msg('tagline') ?></h3>
	    <div id="contentSub"><?php $this->html('subtitle') ?></div>
	    <?php if($this->data['undelete']) { ?><div class="contentSub"><?php $this->html('undelete') ?></div><?php } ?>
	    <?php if($this->data['newtalk'] ) { ?><div class="usermessage"><?php $this->html('newtalk') ?></div><?php } ?>
	    <!-- start content -->
	    <?php $this->html('bodytext') ?>
	    <?php if($this->data['catlinks']) $this->html('catlinks'); ?>
	    <!-- end content -->
	    <div class="visualClear"></div>
	  </div>
	</div>
      </div>
      <div id="column-one">
	<div class="portlet" id="p-personal">
	  <h5><?php $this->msg('personaltools') ?></h5>
	  <div class="pBody">
	    <ul>
	    <?php foreach($this->data['personal_urls'] as $key => $item) {
	       ?><li id="pt-<?php echo htmlspecialchars($key) ?>"><a href="<?php
	       echo htmlspecialchars($item['href']) ?>"<?php
	       if(!empty($item['class'])) { ?> class="<?php
	       echo htmlspecialchars($item['class']) ?>"<?php } ?>><?php
	       echo htmlspecialchars($item['text']) ?></a></li><?php
	    } ?>
	    </ul>
	  </div>
	</div>
	<script type="text/javascript"> if (window.isMSIE55) fixalpha(); </script>
	<div class="portlet" id="p-nav">
	  <h5><?php $this->msg('navigation') ?></h5>
	  <div class="pBody">
	    <ul>
	      <?php foreach($this->data['sidebar']['navigation'] as $navlink) { ?>
	      <li id="<?php echo htmlspecialchars($navlink['id'])
	        ?>"><a href="<?php echo htmlspecialchars($navlink['href']) ?>"><?php 
	        echo htmlspecialchars($navlink['text']) ?></a></li><?php } ?>
	    </ul>
	  </div>
	</div>
	<div id="p-search" class="portlet">
	  <h5><label for="searchInput"><?php $this->msg('search') ?></label></h5>
	  <div class="pBody">
	    <form name="searchform" action="https://wiki.moztw.org/index.php" id="searchform">
	      <input id="searchInput" name="search" type="text"
	        <?php if($this->haveMsg('accesskey-search')) {
	          ?>accesskey="<?php $this->msg('accesskey-search') ?>"<?php }
	        if( isset( $this->data['search'] ) ) {
	          ?> value="<?php $this->text('search') ?>"<?php } ?> />
	      <input type='submit' name="go" class="searchButton" id="searchGoButton"
	        value="<?php $this->msg('go') ?>"
	        />&nbsp;<input type='submit' name="fulltext"
	        class="searchButton"
	        value="<?php $this->msg('search') ?>" />
	    </form>
	  </div>
	</div>
	<div class="portlet" id="p-tb">
	  <h5><?php $this->msg('toolbox') ?></h5>
	  <div class="pBody">
	    <ul>
		  <?php if($this->data['notspecialpage']) { foreach( array( 'whatlinkshere', 'recentchangeslinked' ) as $special ) { ?>
		  <li id="t-<?php echo $special?>"><a href="<?php
		    echo htmlspecialchars($this->data['nav_urls'][$special]['href']) 
		    ?>"><?php echo $this->msg($special) ?></a></li>
		  <?php } } ?>
	      <?php if($this->data['feeds']) { ?><li id="feedlinks"><?php foreach($this->data['feeds'] as $key => $feed) {
	        ?><span id="feed-<?php echo htmlspecialchars($key) ?>"><a href="<?php
	        echo htmlspecialchars($feed['href']) ?>"><?php echo htmlspecialchars($feed['text'])?></a>&nbsp;</span>
	        <?php } ?></li><?php } ?>
	      <?php foreach( array('contributions', 'emailuser', 'upload', 'specialpages') as $special ) { ?>
	      <?php if($this->data['nav_urls'][$special]) {?><li id="t-<?php echo $special ?>"><a href="<?php
	        echo htmlspecialchars($this->data['nav_urls'][$special]['href'])
	        ?>"><?php $this->msg($special) ?></a></li><?php } ?>
	      <?php } ?>
	    </ul>
	  </div>
	</div>
	<?php if( $this->data['language_urls'] ) { ?><div id="p-lang" class="portlet">
	  <h5><?php $this->msg('otherlanguages') ?></h5>
	  <div class="pBody">
	    <ul>
	      <?php foreach($this->data['language_urls'] as $langlink) { ?>
	      <li>
	      <a href="<?php echo htmlspecialchars($langlink['href'])
	        ?>"><?php echo $langlink['text'] ?></a>
	      </li>
	      <?php } ?>
	    </ul>
	  </div>
	</div>
	<?php } ?>
      </div><!-- end of the left (by default at least) column -->
      <div class="visualClear"></div>
      <div id="footer">
    <?php if($this->data['poweredbyico']) { ?><div id="f-poweredbyico"><?php $this->html('poweredbyico') ?></div><?php } ?>
    <?php if($this->data['footericons']) { ?><div id="f-copyrightico"><img src="<?php echo($this->data['footericons']['copyright']['copyright']['src']); ?>" /></div><?php } ?>
	<ul id="f-list">
	  <?php if($this->data['lastmod'   ]) { ?><li id="f-lastmod"><?php    $this->html('lastmod')    ?></li><?php } ?>
	  <?php if($this->data['viewcount' ]) { ?><li id="f-viewcount"><?php  $this->html('viewcount')  ?></li><?php } ?>
	  <?php if($this->data['credits'   ]) { ?><li id="f-credits"><?php    $this->html('credits')    ?></li><?php } ?>
	  <?php if($this->data['copyright' ]) { ?><li id="f-copyright"><?php  $this->html('copyright')  ?></li><?php } ?>
	  <?php if($this->data['about'     ]) { ?><li id="f-about"><?php      $this->html('about')      ?></li><?php } ?>
	  <?php if($this->data['disclaimer']) { ?><li id="f-disclaimer"><?php $this->html('disclaimer') ?></li><?php } ?>
	</ul>
	</div></div>
      </div></div>
    </div>
    <?php $this->html('reporttime') ?>
    <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-1035080-1', 'auto');
    ga('send', 'pageview');
    </script>
  </body>
</html>
<?php
	}
}

?>
