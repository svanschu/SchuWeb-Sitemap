<?php
/**
 * @version             sw.build.version
 * @copyright   Copyright (C) 2019 - 2022 Sven Schultschik. All rights reserved
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 * @author              Sven Schultschik (extensions@schultschik.de)
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Create shortcut to parameters.
$params = $this->item->params;

$live_site = substr_replace(JURI::root(), "", -1, 1);

header('Content-type: text/xml; charset=utf-8');

if ($params->get('cacheControl', 1) == 1) {
    $cacheControl = '';

    if ($params->get('cacheControlPublic', 1) == 0) {
        $cacheControl = 'private, ';
    }

    if ($params->get('cacheControlUseChangeFrequency', 1) == 1) {
        $cacheControl .= 'max-age=' . $this->changeFreq;
    } else {
        if (($maxAge = intval($params->get('cacheControlMaxAge', 0))) > 0) {
            $cacheControl .= 'max-age=' . $maxAge;
        }
    }
    header('Cache-Control: ' . $cacheControl);
} else {
    header('Cache-Control: no-cache');
}

$jinput = JFactory::getApplication()->input;

echo '<?xml version="1.0" encoding="UTF-8"?>',"\n";
if (($this->item->params->get('beautify_xml', 1) == 1) && !$this->displayer->isNews) {
    $params = '&amp;filter_showtitle=' . $jinput->getBool('filter_showtitle', 0);
    $params .= '&amp;filter_showexcluded=' . $jinput->getBool('filter_showexcluded', 0);
    $params .= ($jinput->getCmd('lang') ? '&amp;lang=' . $jinput->getCmd('lang') : '');
    echo '<?xml-stylesheet type="text/xsl" href="' . $live_site . '/index.php?option=com_schuweb_sitemap&amp;view=xml&amp;layout=xsl&amp;tmpl=component&amp;id=' . $this->item->id . ($this->isImages ? '&amp;images=1' : '') . $params . '"?>' . "\n";
}
?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"<?php echo ($this->displayer->isImages? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"':''); ?><?php echo ($this->displayer->isNews? ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"':''); ?>>

<?php echo $this->loadTemplate('items'); ?>

</urlset>