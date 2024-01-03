# Sitemaps
    Sitemaps are an easy way for webmasters to inform search engines about pages on their sites that are available for crawling. In its simplest form, a Sitemap is an XML file that lists URLs for a site along with additional metadata about each URL (when it was last updated, how often it usually changes, and how important it is, relative to other URLs in the site) so that search engines can more intelligently crawl the site.

    Web crawlers usually discover pages from links within the site and from other sites. Sitemaps supplement this data to allow crawlers that support Sitemaps to pick up all URLs in the Sitemap and learn about those URLs using the associated metadata. Using the Sitemap protocol does not guarantee that web pages are included in search engines, but provides hints for web crawlers to do a better job of crawling your site.

    Sitemap 0.90 has a wide adoption, including support from Google, Yahoo!, and Microsoft.
Quote: [sitemaps.org](https://www.sitemaps.org/)

## Types of sitemaps
SchuWeb Sitemap supports 3 types of sitemaps. The standard sitemap, the news sitemap and the images sitemaps.

### News sitemap
The news sitemap collects every new content which is not older than two days. If you don't have content newer than two days, it will not be in this sitemap.

This type is defined and used by Google: [developers.google.com](https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap)

### Images sitemap
The images sitemap lists every content which includes images and collects the direct URLs to those images.

This type is defined and used by Google: [developers.google.com](https://developers.google.com/search/docs/crawling-indexing/sitemaps/image-sitemaps)

## Change frequency and priority
A real good explanation can be found at [slickplan.com](https://slickplan.com/blog/xml-sitemap-priority-changefreq)

But if you want the sitemap only for Google, they don't matter. Google ignors those values.