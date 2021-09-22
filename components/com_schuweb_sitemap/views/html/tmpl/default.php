<?php
/**
 * @version         sw.build.version
 * @copyright       Copyright (C) 2005 - 2009 Joomla! Vargas. All rights reserved.
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 * @author          Guillermo Vargas (guille@vargas.co.cr)
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

// Create shortcut to parameters.
$params = $this->item->params;

if ($this->displayer->canEdit) {
    $live_site = JURI::root();
    if (version_compare(JVERSION, '4', 'lt'))
    {
	    JHTML::_('behavior.framework', true);
    } else {
        //TODO  Frontend logedin publish buttons don't work #75
        // https://github.com/svanschu/SchuWeb-Sitemap/issues/75
    }
    $ajaxurl = "{$live_site}index.php?option=com_schuweb_sitemap&format=json&task=ajax.editElement&action=toggleElement&".JSession::getFormToken().'=1';

    $css = '.xmapexcl img{ border:0px; }'."\n";
    $css .= '.xmapexcloff { text-decoration:line-through; }';
    //$css .= "\n.".$this->item->classname .' li {float:left;}';

    $js = "
        window.addEvent('domready',function (){
            $$('.xmapexcl').each(function(el){
                el.onclick = function(){
                    if (this && this.rel) {
                        options = JSON.decode(this.rel);
                        this.onComplete = checkExcludeResult
                        var myAjax = new Request.JSON({
                            url:'{$ajaxurl}',
                            onSuccess: checkExcludeResult.bind(this)
                        }).get({id:{$this->item->id},uid:options.uid,itemid:options.itemid});
                    }
                    return false;
                };

            });
        });
        checkExcludeResult = function (response) {
            //this.set('class','xmapexcl xmapexcloff');
            var imgs = this.getElementsByTagName('img');
            if (response.result == 'OK') {
                var state = response.state;
                if (state==0) {
                    imgs[0].src='{$live_site}/components/com_schuweb_sitemap/assets/images/unpublished.png';
                } else {
                    imgs[0].src='{$live_site}/components/com_schuweb_sitemap/assets/images/tick.png';
                }
            } else {
                alert('The element couldn\\'t be published or upublished!');
            }
        }";

    $doc = JFactory::getDocument();
    $doc->addStyleDeclaration ($css);
    $doc->addScriptDeclaration ($js);
}
?>
<div id="SchuWeb_Sitemap">
<?php if ($params->get('show_page_heading', 1) && $params->get('page_heading') != '') : ?>
    <h1>
        <?php echo $this->escape($params->get('page_heading')); ?>
    </h1>
<?php endif; ?>

<?php if ($params->get('showintro', 1) )  : ?>
    <?php echo $this->item->introtext; ?>
<?php endif; ?>

    <?php echo $this->loadTemplate('items'); ?>

<?php if ($params->get('include_link', 1) )  : ?>
    <div class="muted" style="font-size:10px;width:100%;clear:both;text-align:center;">Powered by <a target="_blank" href="http://extensions.schultschik.com/">SchuWeb Sitemap</a></div>
<?php endif; ?>

    <span class="article_separator">&nbsp;</span>
</div>